<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MigrateMysqlToPostgres extends Command
{
    protected $signature = 'data:migrate-mysql-to-pg
        {--tables= : Comma-separated list of tables to copy. Defaults to all user tables}
        {--from= : Source MySQL table name (overrides --tables when provided)}
        {--to= : Target PostgreSQL table name (required when --from is provided)}
        {--truncate : Truncate target tables before copying}
        {--chunk=2000 : Chunk size for copying rows}
        {--skip=migrations,failed_jobs,personal_access_tokens : Comma-separated tables to skip}
        {--allow-truncation : Allow lossy truncation of overlength strings}
        {--users-skip-duplicate-emails : Skip duplicate emails in users table}';

    protected $description = 'Copy data from MySQL to PostgreSQL with chunking, triggers disabled, sequence reset.';

    protected bool $allowTruncation = false;
    protected bool $usersSkipDuplicateEmails = false;

    public function handle(): int
    {
        $mysql = DB::connection('mysql');
        $pg = DB::connection('pgsql_migrate');

        $chunkSize = max(1, (int) $this->option('chunk'));
        $explicitTables = $this->option('tables');
        $skip = $this->parseCsvOption((string) $this->option('skip'));

        $this->allowTruncation = (bool) $this->option('allow-truncation');
        $this->usersSkipDuplicateEmails = (bool) $this->option('users-skip-duplicate-emails');

        $from = $this->option('from');
        $to = $this->option('to');

        if ($from !== null && $from !== '') {
            if ($to === null || $to === '') {
                $this->error('When using --from you must also provide --to.');
                return self::FAILURE;
            }
            $pairs = [['from' => $from, 'to' => $to]];
        } else {
            $tableNames = $explicitTables
                ? $this->parseCsvOption($explicitTables)
                : $this->discoverMySqlTables($mysql)->diff($skip)->values()->all();

            if (empty($tableNames)) {
                $this->warn('No tables selected for migration.');
                return self::SUCCESS;
            }

            $pairs = collect($tableNames)->map(fn($t) => [
                'from' => $t,
                'to' => $t
            ])->all();
        }

        if ($this->option('truncate')) {
            $this->info('Truncating target tables...');
            foreach (array_reverse($pairs) as $pair) {
                $this->truncatePgTable($pg, $pair['to']);
            }
        }

        $this->setPgTriggers($pg, disable: true);

        try {
            foreach ($pairs as $pair) {
                $this->copyTable($mysql, $pg, $pair['from'], $pair['to'], $chunkSize);
            }
        } finally {
            $this->setPgTriggers($pg, disable: false);
        }

        $this->resetPgSequences($pg);

        $this->info('Migration completed successfully.');
        return self::SUCCESS;
    }

    protected function discoverMySqlTables(ConnectionInterface $mysql): Collection
    {
        $db = $mysql->getDatabaseName();

        $rows = $mysql->select("
            SELECT TABLE_NAME
            FROM information_schema.tables
            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$db]);

        return collect($rows)->pluck('TABLE_NAME')->filter(fn($name) => is_string($name));
    }

    protected function copyTable(ConnectionInterface $mysql, ConnectionInterface $pg, string $from, string $to, int $chunkSize): void
    {
        $this->line("Migrating: {$from} → {$to}");

        if (!$mysql->getSchemaBuilder()->hasTable($from)) {
            $this->warn(" - Source {$from} missing");
            return;
        }
        if (!$pg->getSchemaBuilder()->hasTable($to)) {
            $this->warn(" - Target {$to} missing");
            return;
        }

        $columns = $this->getSharedColumns($mysql, $pg, $from, $to);
        if (empty($columns)) {
            $this->warn(" - No shared columns");
            return;
        }

        // اضافه کردن ستون‌های date و date_modified برای تبدیل
        $mysqlCols = $this->listColumnsMySql($mysql, $from);
        $pgCols = $this->listColumnsPg($pg, $to);

        $columnsToRead = $columns;
        if (in_array('date', $mysqlCols, true) && !in_array('date', $pgCols, true) && in_array('created_at', $pgCols, true)) {
            $columnsToRead[] = 'date';
        }
        if (in_array('date_modified', $mysqlCols, true) && !in_array('date_modified', $pgCols, true) && in_array('updated_at', $pgCols, true)) {
            $columnsToRead[] = 'date_modified';
        }
        // اضافه کردن id برای تبدیل به user_id
        if (in_array('id', $mysqlCols, true) && !in_array('id', $pgCols, true) && in_array('user_id', $pgCols, true)) {
            $columnsToRead[] = 'id';
        }
        $columnsToRead = array_unique($columnsToRead);

        $hasId = $this->tableHasColumn($mysql, $from, 'id');
        $limits = $this->getPgColumnLimits($pg, $to);

        $copied = 0;

        if ($hasId) {
            $lastId = 0;
            while (true) {
                $rows = $mysql->table($from)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit($chunkSize)
                    ->get($columnsToRead);

                if ($rows->isEmpty()) break;

                $payload = $rows->map(function ($r) use ($limits, $from, $pgCols) {
                    $row = $this->applyColumnLimits((array) $r, $limits, $from);
                    return $this->postProcessRow($row, $pgCols);
                })->all();

                // لیست ستون‌های نهایی بعد از تبدیل
                $finalColumns = $this->getFinalColumns($columns, $pgCols);
                $this->insertRows($pg, $to, $finalColumns, $payload);
                $copied += count($payload);
                // استفاده از user_id اگر وجود دارد، در غیر این صورت از id
                $lastRow = Arr::last($payload);
                $lastId = isset($lastRow['user_id']) ? (int) $lastRow['user_id'] : (isset($lastRow['id']) ? (int) $lastRow['id'] : 0);
            }
        } else {
            $offset = 0;
            while (true) {
                $rows = $mysql->table($from)->offset($offset)->limit($chunkSize)->get($columnsToRead);
                if ($rows->isEmpty()) break;

                $payload = $rows->map(function ($r) use ($limits, $from, $pgCols) {
                    $row = $this->applyColumnLimits((array) $r, $limits, $from);
                    return $this->postProcessRow($row, $pgCols);
                })->all();

                // لیست ستون‌های نهایی بعد از تبدیل
                $finalColumns = $this->getFinalColumns($columns, $pgCols);
                $this->insertRows($pg, $to, $finalColumns, $payload);
                $copied += count($payload);
                $offset += $chunkSize;
            }
        }

        $this->info(" - Copied {$copied} rows");
    }

    protected function getSharedColumns(ConnectionInterface $mysql, ConnectionInterface $pg, string $from, string $to): array
    {
        $mysqlCols = $this->listColumnsMySql($mysql, $from);
        $pgCols = $this->listColumnsPg($pg, $to);

        return array_values(array_intersect($mysqlCols, $pgCols));
    }

    protected function listColumnsMySql(ConnectionInterface $mysql, string $table): array
    {
        $db = $mysql->getDatabaseName();

        $rows = $mysql->select("
            SELECT COLUMN_NAME
            FROM information_schema.columns
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$db, $table]);

        return collect($rows)->pluck('COLUMN_NAME')->all();
    }

    protected function listColumnsPg(ConnectionInterface $pg, string $table): array
    {
        $schema = $pg->selectOne("SELECT current_schema() AS s")->s ?? 'public';

        $rows = $pg->select("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
            ORDER BY ordinal_position
        ", [$schema, $table]);

        return collect($rows)->pluck('column_name')->all();
    }

    protected function getPgColumnLimits(ConnectionInterface $pg, string $table): array
    {
        $schema = $pg->selectOne("SELECT current_schema() AS s")->s ?? 'public';

        $rows = $pg->select("
            SELECT column_name, data_type, character_maximum_length
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
        ", [$schema, $table]);

        $limits = [];
        foreach ($rows as $row) {
            $limits[$row->column_name] = [
                'type' => $row->data_type,
                'max'  => $row->character_maximum_length
            ];
        }
        return $limits;
    }

    protected function applyColumnLimits(array $row, array $limits, string $table): array
    {
        foreach ($limits as $col => $meta) {
            if (!isset($row[$col])) continue;

            $dataType = $meta['type'] ?? null;

            // تبدیل مقادیر خالی برای ستون‌های عددی به NULL
            if (in_array($dataType, ['bigint', 'integer', 'smallint', 'numeric', 'decimal', 'real', 'double precision'], true)) {
                if ($row[$col] === '' || (is_string($row[$col]) && trim($row[$col]) === '')) {
                    $row[$col] = null;
                    continue;
                }
            }

            $max = $meta['max'];
            if ($max !== null && is_string($row[$col])) {
                if (mb_strlen($row[$col]) > $max) {
                    if (!$this->allowTruncation) {
                        throw new \RuntimeException("Overflow: {$table}.{$col}");
                    }
                    $row[$col] = mb_substr($row[$col], 0, $max);
                }
            }
        }
        return $row;
    }

    /**
     * 🔥 تبدیل date → created_at
     * 🔥 تبدیل date_modified → updated_at
     * برای تمام جدول‌ها
     */
    protected function postProcessRow(array $row, array $pgCols): array
    {
        /**
         * 🔥 تبدیل date → created_at
         * 🔥 تبدیل date_modified → updated_at
         */
        if (array_key_exists('date', $row) && in_array('created_at', $pgCols, true)) {
            if (!array_key_exists('created_at', $row)) {
                $row['created_at'] = $row['date'];
            }
            // حذف date فقط اگر در جدول مقصد وجود نداشته باشد
            if (!in_array('date', $pgCols, true)) {
                unset($row['date']);
            }
        }

        if (array_key_exists('date_modified', $row) && in_array('updated_at', $pgCols, true)) {
            if (!array_key_exists('updated_at', $row)) {
                $row['updated_at'] = $row['date_modified'];
            }
            // حذف date_modified فقط اگر در جدول مقصد وجود نداشته باشد
            if (!in_array('date_modified', $pgCols, true)) {
                unset($row['date_modified']);
            }
        }
        // تبدیل id → user_id
        if (array_key_exists('id', $row) && in_array('user_id', $pgCols, true)) {
            if (!array_key_exists('user_id', $row)) {
                $row['user_id'] = $row['id'];
            }
            // حذف id فقط اگر در جدول مقصد وجود نداشته باشد
            if (!in_array('id', $pgCols, true)) {
                unset($row['id']);
            }
        }

        /**
         * 🔥 تبدیل level:
         * اگر مقدار 'admin' باشد => 0
         * اگر مقدار 'user' یا هر مقدار دیگر باشد => 1
         */
        if (array_key_exists('level', $row)) {
            $v = strtolower(trim((string)$row['level']));

            if ($v === 'admin') {
                $row['level'] = 0;
            } else {
                // user یا سایر مقدارها
                $row['level'] = 1;
            }
        }

        return $row;
    }

    /**
     * محاسبه لیست ستون‌های نهایی برای insert
     * بعد از تبدیل date → created_at و date_modified → updated_at
     */
    protected function getFinalColumns(array $originalColumns, array $pgCols): array
    {
        $final = [];

        foreach ($originalColumns as $col) {
            // اگر ستون در جدول مقصد وجود دارد، اضافه کن
            if (in_array($col, $pgCols, true)) {
                $final[] = $col;
            }
        }

        // اضافه کردن created_at و updated_at اگر در جدول مقصد وجود دارند
        if (in_array('created_at', $pgCols, true) && !in_array('created_at', $final, true)) {
            $final[] = 'created_at';
        }
        if (in_array('updated_at', $pgCols, true) && !in_array('updated_at', $final, true)) {
            $final[] = 'updated_at';
        }
        // اضافه کردن user_id اگر در جدول مقصد وجود دارد
        if (in_array('user_id', $pgCols, true) && !in_array('user_id', $final, true)) {
            $final[] = 'user_id';
        }

        return $final;
    }


    protected function insertRows(ConnectionInterface $pg, string $table, array $columns, array $rows): void
    {
        if (empty($rows)) return;

        if ($table === 'users' && $this->usersSkipDuplicateEmails && in_array('email', $columns, true)) {
            $this->insertWithConflictSkip($pg, $table, $columns, $rows, ['email']);
            return;
        }

        $pg->table($table)->insert($rows);
    }

    protected function insertWithConflictSkip(ConnectionInterface $pg, string $table, array $columns, array $rows, array $conflictCols): void
    {
        $columnList = implode(', ', array_map(fn($c) => "\"{$c}\"", $columns));

        $placeholders = [];
        $bindings = [];

        foreach ($rows as $row) {
            $ph = [];
            foreach ($columns as $col) {
                $ph[] = '?';
                $bindings[] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $ph) . ')';
        }

        $conflicts = implode(', ', array_map(fn($c) => "\"{$c}\"", $conflictCols));

        $sql = "INSERT INTO \"{$table}\" ({$columnList}) VALUES "
            . implode(', ', $placeholders)
            . " ON CONFLICT ({$conflicts}) DO NOTHING";

        $pg->statement($sql, $bindings);
    }

    protected function tableHasColumn(ConnectionInterface $conn, string $table, string $column): bool
    {
        return in_array($column, $this->listColumnsMySql($conn, $table), true);
    }

    protected function truncatePgTable(ConnectionInterface $pg, string $table): void
    {
        $pg->unprepared("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
    }

    protected function setPgTriggers(ConnectionInterface $pg, bool $disable): void
    {
        $role = $disable ? 'replica' : 'origin';
        $pg->unprepared("SET session_replication_role = '{$role}'");
    }

    protected function resetPgSequences(ConnectionInterface $pg): void
    {
        $pg->unprepared(<<<SQL
DO $$
DECLARE r record;
BEGIN
  FOR r IN
    SELECT c.relname AS seq, t.relname AS tbl, a.attname AS col
    FROM pg_class c
    JOIN pg_depend d ON d.objid = c.oid AND d.deptype = 'a'
    JOIN pg_class t ON d.refobjid = t.oid
    JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.refobjsubid
    WHERE c.relkind = 'S'
  LOOP
    EXECUTE format(
      'SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %I), 1))',
      r.seq, r.col, r.tbl
    );
  END LOOP;
END $$;
SQL);
    }

    protected function parseCsvOption(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

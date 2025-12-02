<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrateDataAdvanced extends Command
{
    protected $signature = 'migrate:advanced
                            {--test : Test mode - migrate only 100 records}
                            {--resume : Resume from last checkpoint}
                            {--verify : Verify data after migration}
                            {--connection=mysql_old : Old MySQL connection}';

    protected $description = 'Advanced data migration with resume capability and verification';

    private $checkpointFile = 'storage/migration_checkpoint.json';
    private $errorLog = 'storage/migration_errors.log';
    private $stats = [
        'categories' => ['migrated' => 0, 'errors' => 0],
        'books' => ['migrated' => 0, 'errors' => 0],
        'contents' => ['migrated' => 0, 'errors' => 0],
        'user_books' => ['migrated' => 0, 'errors' => 0],
    ];

    public function handle(): int
    {
        $this->info('🚀 Starting Advanced Migration...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            // بررسی checkpoint
            $checkpoint = $this->loadCheckpoint();

            // مراحل migration
            $steps = [
                'categories' => !$checkpoint['categories']['completed'],
                'books' => !$checkpoint['books']['completed'],
                'contents' => !$checkpoint['contents']['completed'],
                'user_books' => !$checkpoint['user_books']['completed'],
            ];

            if ($steps['categories']) {
                $this->migrateCategories($checkpoint['categories']['last_id'] ?? 0);
                $checkpoint['categories']['completed'] = true;
                $this->saveCheckpoint($checkpoint);
            }

            if ($steps['books']) {
                $this->migrateBooks($checkpoint['books']['last_id'] ?? 0);
                $checkpoint['books']['completed'] = true;
                $this->saveCheckpoint($checkpoint);
            }

            if ($steps['contents']) {
                $this->migrateContents($checkpoint['contents']['last_id'] ?? 0);
                $checkpoint['contents']['completed'] = true;
                $this->saveCheckpoint($checkpoint);
            }

            if ($steps['user_books']) {
                $this->migrateUserBooks($checkpoint['user_books']['last_id'] ?? 0);
                $checkpoint['user_books']['completed'] = true;
                $this->saveCheckpoint($checkpoint);
            }

            // Verification
            if ($this->option('verify')) {
                $this->verify();
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info('✅ Migration completed successfully!');
            $this->displayStats($duration);

            // پاک کردن checkpoint
            if (file_exists($this->checkpointFile)) {
                unlink($this->checkpointFile);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            $this->saveCheckpoint($checkpoint ?? []);
            return 1;
        }
    }

    private function migrateCategories(int $startId): void
    {
        $this->info('📂 Migrating Categories...');

        $query = DB::connection($this->option('connection'))
            ->table('ci_category')
            ->where('type', 'post')
            ->where('id', '>', $startId)
            ->orderBy('id');

        if ($this->option('test')) {
            $query->limit(100);
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);


$query->chunk(100, function ($categories) use ($bar) {
    foreach ($categories as $cat) {
        try {
            DB::connection('pgsql')->table('categories')->updateOrInsert(
                ['id' => $cat->id],
                [
                    'name' => $cat->name,
                    'slug' => Str::slug($cat->name . '-' . $cat->id),
                    'description' => $cat->description,
                    'parent_id' => $cat->parent > 0 ? $cat->parent : null,
                    'image' => $cat->pic,
                    'icon' => $cat->icon,
                    'position' => $cat->position,
                    'is_active' => true,
                    'type' => 'book',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Subscription plans
            $this->migrateSubscriptionPlans($cat);

            $this->stats['categories']['migrated']++;

        } catch (\Exception $e) {
            $this->stats['categories']['errors']++;
            $this->logError('category', $cat->id, $e->getMessage());
        }

        $bar->advance();
    }
});

        $bar->finish();
        $this->newLine();
    }

    private function migrateBooks(int $startId): void
    {
        $this->info('📚 Migrating Books...');

        $query = DB::connection($this->option('connection'))
            ->table('ci_posts')
            ->where('type', 'book')
            ->where('id', '>', $startId)
            ->orderBy('id');

        if ($this->option('test')) {
            $query->limit(100);
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);

        $query->chunk(50, function ($books) use ($bar) {
            foreach ($books as $book) {
                try {
                    // Parse category
                    $categoryId = null;
                    if (!empty($book->category)) {
                        $cats = array_filter(array_map('intval', explode(',', $book->category)));
                        $categoryId = $cats[0] ?? null;
                    }

                    // Mapping: ci_posts → books
                    DB::connection('pgsql')->table('books')->updateOrInsert(
                        ['id' => $book->id],
                        [
                            'title' => $book->title,
                            'slug' => Str::slug($book->title . '-' . $book->id),
                            'excerpt' => $book->excerpt,
                            'content' => $book->content,
                            'isbn' => null,
                            'publisher_id' => null,
                            'primary_category_id' => $categoryId,
                            'cover_image' => $book->thumb,
                            'thumbnail' => $book->thumb,
                            'icon' => $book->icon,
                            'pages' => max(0, (int) $book->pages),
                            'total_paragraphs' => 0, // update بعداً
                            'file_size' => max(0, (int) $book->size),
                            'position' => max(0, (int) $book->position),
                            'has_description' => (bool) $book->has_description,
                            'has_sound' => (bool) $book->has_sound,
                            'has_video' => (bool) $book->has_video,
                            'has_image' => (bool) $book->has_image,
                            'has_test' => (bool) $book->has_test,
                            'has_essay' => (bool) $book->has_tashrihi,
                            'has_download' => (bool) $book->has_download,


'price' => max(0, (float) $book->price),
                            'discount_price' => null,
                            'is_free' => $book->price == 0,
                            'meta_keywords' => $book->meta_keywords,
                            'meta_description' => $book->meta_description,
                            'tags' => $book->tags,
                            'status' => $book->published ? 'published' : 'draft',
                            'is_special' => (bool) $book->special,
                            'allow_comments' => (bool) $book->accept_cm,
                            'view_count' => 0,
                            'purchase_count' => max(0, (int) $book->has_bought),
                            'download_count' => 0,
                            'rating' => 0,
                            'rating_count' => 0,
                            'created_at' => $book->date ?? now(),
                            'updated_at' => $book->date_modified ?? now(),
                        ]
                    );

                    // Categories (many-to-many)
                    if (!empty($book->category)) {
                        $cats = array_filter(array_map('intval', explode(',', $book->category)));
                        foreach ($cats as $catId) {
                            DB::connection('pgsql')->table('book_category')->insertOrIgnore([
                                'book_id' => $book->id,
                                'category_id' => $catId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    $this->stats['books']['migrated']++;

                } catch (\Exception $e) {
                    $this->stats['books']['errors']++;
                    $this->logError('book', $book->id, $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function migrateContents(int $startId): void
    {
        $this->info('📄 Migrating Book Contents...');

        $query = DB::connection($this->option('connection'))
            ->table('ci_book_meta')
            ->where('id', '>', $startId)
            ->orderBy('id');

        if ($this->option('test')) {
            $query->limit(1000);
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);

        $query->chunk(200, function ($contents) use ($bar) {
            foreach ($contents as $content) {
                try {
                    // Parse images
                    $imagePaths = null;
                    if (!empty($content->image)) {
                        $images = array_filter(array_map('trim', explode(',', $content->image)));
                        $imagePaths = !empty($images) ? json_encode($images) : null;
                    }

                    // Mapping: ci_book_meta → book_contents
                    DB::connection('pgsql')->table('book_contents')->updateOrInsert(
                        [
                            'book_id' => $content->book_id,
                            'page_number' => max(1, (int) $content->page),
                            'paragraph_number' => max(1, (int) $content->paragraph)
                        ],
                        [
                            'order' => max(0, (int) ($content->order ?? 0)),
                            'text' => $content->text,
                            'description' => $content->description,
                            'sound_path' => $content->sound,
                            'image_paths' => $imagePaths,
                            'video_path' => $content->video,
                            'is_index' => (bool) ($content->fehrest ?? 0),


'index_title' => $content->fehrest ? ($content->text ?? null) : null,
                            'index_level' => max(0, (int) ($content->index ?? 0)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $this->stats['contents']['migrated']++;

                } catch (\Exception $e) {
                    $this->stats['contents']['errors']++;
                    $this->logError('content', $content->id, $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        // Update total_paragraphs
        $this->info('  🔄 Updating book statistics...');
        DB::connection('pgsql')->statement('
            UPDATE books
            SET total_paragraphs = (
                SELECT COUNT(*)
                FROM book_contents
                WHERE book_contents.book_id = books.id
            )
        ');
    }

    private function migrateUserBooks(int $startId): void
    {
        $this->info('💰 Migrating User Books...');

        $query = DB::connection($this->option('connection'))
            ->table('ci_user_books')
            ->where('id', '>', $startId)
            ->orderBy('id');

        if ($this->option('test')) {
            $query->limit(500);
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);

        $query->chunk(200, function ($userBooks) use ($bar) {
            foreach ($userBooks as $ub) {
                try {
                    $isSubscription = !empty($ub->expiremembership);

                    // Mapping: ci_user_books → user_library
                    DB::connection('pgsql')->table('user_library')->updateOrInsert(
                        [
                            'user_id' => $ub->user_id,
                            'book_id' => $ub->book_id
                        ],
                        [
                            'access_type' => $isSubscription ? 'subscription' : 'purchased',
                            'purchase_id' => $ub->factor_id,
                            'subscription_id' => null,
                            'current_page' => 0,
                            'current_paragraph' => 0,
                            'total_pages_read' => 0,
                            'progress_percentage' => 0,
                            'status' => 'not_started',
                            'last_read_at' => null,
                            'completed_at' => null,
                            'access_expires_at' => $ub->expiremembership,
                            'total_reading_time' => 0,
                            'session_count' => 0,
                            'needs_update' => (bool) ($ub->need_update ?? 0),
                            'reading_preferences' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $this->stats['user_books']['migrated']++;

                } catch (\Exception $e) {
                    $this->stats['user_books']['errors']++;
                    $this->logError('user_book', $ub->id, $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function migrateSubscriptionPlans($category): void
    {
        $plans = [
            ['months' => 1, 'price' => $category->membership1, 'discount' => $category->discountmembership1],
            ['months' => 3, 'price' => $category->membership3, 'discount' => $category->discountmembership3],
            ['months' => 6, 'price' => $category->membership6, 'discount' => $category->discountmembership6],
            ['months' => 12, 'price' => $category->membership12, 'discount' => $category->discountmembership12],
        ];


foreach ($plans as $plan) {
    if ($plan['price'] > 0) {
        DB::connection('pgsql')->table('subscription_plans')->updateOrInsert(
            [
                'category_id' => $category->id,
                'duration_months' => $plan['months']
            ],
            [
                'price' => $plan['price'],
                'discount_percentage' => $plan['discount'],
                'is_active' => true,
                'priority' => $plan['months'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
    }

    private function verify(): void
    {
        $this->newLine();
        $this->info('🔍 Verifying Migration...');

        $oldConn = $this->option('connection');

        $checks = [
            'Categories' => [
                'old' => DB::connection($oldConn)->table('ci_category')->where('type', 'post')->count(),
                'new' => DB::connection('pgsql')->table('categories')->count(),
            ],
            'Books' => [
                'old' => DB::connection($oldConn)->table('ci_posts')->where('type', 'book')->count(),
                'new' => DB::connection('pgsql')->table('books')->count(),
            ],
            'Contents' => [
                'old' => DB::connection($oldConn)->table('ci_book_meta')->count(),
                'new' => DB::connection('pgsql')->table('book_contents')->count(),
            ],
            'User Books' => [
                'old' => DB::connection($oldConn)->table('ci_user_books')->count(),
                'new' => DB::connection('pgsql')->table('user_library')->count(),
            ],
        ];

        $this->table(
            ['Table', 'Old DB', 'New DB', 'Status'],
            collect($checks)->map(function ($counts, $table) {
                $match = $counts['old'] == $counts['new'];
                return [
                    $table,
                    $counts['old'],
                    $counts['new'],
                    $match ? '✅' : '❌'
                ];
            })->toArray()
        );
    }

    private function loadCheckpoint(): array
    {
        if ($this->option('resume') && file_exists($this->checkpointFile)) {
            return json_decode(file_get_contents($this->checkpointFile), true);
        }

        return [
            'categories' => ['completed' => false, 'last_id' => 0],
            'books' => ['completed' => false, 'last_id' => 0],
            'contents' => ['completed' => false, 'last_id' => 0],
            'user_books' => ['completed' => false, 'last_id' => 0],
        ];
    }

    private function saveCheckpoint(array $checkpoint): void
    {
        file_put_contents($this->checkpointFile, json_encode($checkpoint, JSON_PRETTY_PRINT));
    }

    private function logError(string $type, int $id, string $message): void
    {
        $log = sprintf("[%s] %s ID=%d: %s\n", now(), $type, $id, $message);
        file_put_contents($this->errorLog, $log, FILE_APPEND);
    }

    private function displayStats(float $duration): void
    {
        $this->newLine();
        $this->table(
            ['Type', 'Migrated', 'Errors'],
            [
                ['Categories', $this->stats['categories']['migrated'], $this->stats['categories']['errors']],
                ['Books', $this->stats['books']['migrated'], $this->stats['books']['errors']],
                ['Contents', $this->stats['contents']['migrated'], $this->stats['contents']['errors']],
                ['User Books', $this->stats['user_books']['migrated'], $this->stats['user_books']['errors']],
            ]
        );

        $this->info("⏱️  Total time: {$duration}s");

        $totalErrors = array_sum(array_column($this->stats, 'errors'));
        if ($totalErrors > 0) {
            $this->warn("⚠️  {$totalErrors} errors logged to: {$this->errorLog}");
        }
    }
}

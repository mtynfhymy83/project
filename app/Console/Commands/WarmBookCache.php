<?php

namespace App\Console\Commands;

use App\Services\FastBookCacheService;
use Illuminate\Console\Command;

class WarmBookCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm-books {--limit=100 : تعداد کتاب‌های محبوب برای warm up}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up cache for popular books (پیش‌گرم کردن کش کتاب‌های محبوب)';

    /**
     * Execute the console command.
     */
    public function handle(FastBookCacheService $cacheService): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Warming up cache for top {$limit} popular books...");
        
        $count = $cacheService->warmUpPopularBooks($limit);
        
        $this->info("✅ Cache warmed successfully for {$count} books!");
        
        return Command::SUCCESS;
    }
}









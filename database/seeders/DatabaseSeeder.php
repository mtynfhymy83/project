<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // ترتیب مهم است - dependencies اول
        $this->call([
            CategorySeeder::class,      // 1. دسته‌بندی‌ها
            AuthorSeeder::class,         // 2. نویسندگان
            PublisherSeeder::class,      // 3. ناشران
            BookSeeder::class,           // 4. کتاب‌ها (با relations)
            BookContentSeeder::class,    // 5. محتوای کتاب‌ها
            UserSeeder::class,           // 6. کاربران (با library)
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        
        // نمایش آمار
        $this->displayStats();
    }

    /**
     * نمایش آمار داده‌های ایجاد شده
     */
    protected function displayStats(): void
    {
        $this->command->table(
            ['Table', 'Count'],
            [
                ['Categories', \App\Models\Category::count()],
                ['Authors', \App\Models\Author::count()],
                ['Publishers', \App\Models\Publisher::count()],
                ['Books', \App\Models\Book::count()],
                ['Book Versions', \App\Models\BookVersion::count()],
                ['Book Contents', \App\Models\BookContent::count()],
                ['Book Stats', \App\Models\BookStats::count()],
                ['Users', \App\Models\User::count()],
                ['User Profiles', \App\Models\UserProfile::count()],
                ['User Library', \App\Models\User_Library::count()],
            ]
        );
    }
}

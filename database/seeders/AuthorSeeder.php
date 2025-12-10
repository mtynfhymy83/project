<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // نویسندگان معروف ایرانی
        $iranianAuthors = [
            'صادق هدایت',
            'جلال آل‌احمد',
            'سیمین دانشور',
            'احمد شاملو',
            'فروغ فرخزاد',
            'محمود دولت‌آبادی',
            'هوشنگ مرادی کرمانی',
            'غلامحسین ساعدی',
        ];

        foreach ($iranianAuthors as $name) {
            Author::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'bio' => "نویسنده و اندیشمند برجسته ایرانی",
                'is_active' => true,
            ]);
        }

        // نویسندگان تصادفی بیشتر
        Author::factory()->count(42)->create();

        $this->command->info('✅ Authors seeded successfully! (50 authors)');
    }
}







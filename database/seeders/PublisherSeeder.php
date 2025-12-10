<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PublisherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ناشران معروف ایرانی
        $publishers = [
            'نشر چشمه',
            'نشر نی',
            'نشر مرکز',
            'نشر سخن',
            'انتشارات امیرکبیر',
            'انتشارات علمی و فرهنگی',
            'نشر آموت',
            'نشر ققنوس',
        ];

        foreach ($publishers as $name) {
            Publisher::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "یکی از ناشران معتبر ایران",
                'is_active' => true,
            ]);
        }

        // ناشران تصادفی بیشتر
        Publisher::factory()->count(12)->create();

        $this->command->info('✅ Publishers seeded successfully! (20 publishers)');
    }
}







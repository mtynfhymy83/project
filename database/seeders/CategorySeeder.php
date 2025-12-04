<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // دسته‌های اصلی
            ['name' => 'ادبیات', 'icon' => '📚', 'position' => 1],
            ['name' => 'علوم', 'icon' => '🔬', 'position' => 2],
            ['name' => 'هنر', 'icon' => '🎨', 'position' => 3],
            ['name' => 'تکنولوژی', 'icon' => '💻', 'position' => 4],
            ['name' => 'تاریخ', 'icon' => '🏛️', 'position' => 5],
            ['name' => 'جغرافیا', 'icon' => '🌍', 'position' => 6],
            ['name' => 'فلسفه', 'icon' => '🤔', 'position' => 7],
            ['name' => 'روانشناسی', 'icon' => '🧠', 'position' => 8],
        ];

        $createdCategories = [];

        foreach ($categories as $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'icon' => $categoryData['icon'],
                'position' => $categoryData['position'],
                'is_active' => true,
                'type' => 'book',
            ]);

            $createdCategories[$categoryData['name']] = $category;
        }

        // زیردسته‌ها
        $subcategories = [
            'ادبیات' => ['رمان', 'شعر', 'داستان کوتاه', 'ادبیات کلاسیک'],
            'علوم' => ['فیزیک', 'شیمی', 'زیست‌شناسی', 'ریاضیات'],
            'هنر' => ['نقاشی', 'موسیقی', 'سینما', 'معماری'],
            'تکنولوژی' => ['برنامه‌نویسی', 'هوش مصنوعی', 'امنیت', 'شبکه'],
            'تاریخ' => ['تاریخ ایران', 'تاریخ جهان', 'تاریخ هنر', 'باستان‌شناسی'],
            'جغرافیا' => ['جغرافیای طبیعی', 'جغرافیای انسانی', 'نقشه‌خوانی'],
            'فلسفه' => ['فلسفه غرب', 'فلسفه اسلامی', 'منطق', 'اخلاق'],
            'روانشناسی' => ['روانشناسی عمومی', 'روانشناسی کودک', 'روانشناسی اجتماعی'],
        ];

        foreach ($subcategories as $parentName => $subs) {
            $parent = $createdCategories[$parentName];
            
            foreach ($subs as $index => $subName) {
                Category::create([
                    'name' => $subName,
                    'slug' => Str::slug($subName),
                    'parent_id' => $parent->id,
                    'position' => $index + 1,
                    'is_active' => true,
                    'type' => 'book',
                ]);
            }
        }

        $this->command->info('✅ Categories seeded successfully!');
    }
}


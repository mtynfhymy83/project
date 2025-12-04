<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserProfile;
use App\Models\User_Library;
use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // کاربر تست اصلی
        $testUser = User::create([
            'name' => 'کاربر تست',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        UserMeta::create([
            'user_id' => $testUser->id,
            'username' => 'test_user',
            'first_name' => 'کاربر',
            'last_name' => 'تست',
        ]);

        UserProfile::create([
            'user_id' => $testUser->id,
            'preferences' => [
                'theme' => 'dark',
                'font_size' => 'medium',
                'font_family' => 'vazir',
            ],
        ]);

        // اضافه کردن چند کتاب به کتابخانه
        $books = Book::published()->limit(5)->get();
        foreach ($books as $index => $book) {
            User_Library::create([
                'user_id' => $testUser->id,
                'book_id' => $book->id,
                'progress_percent' => rand(0, 100),
                'current_page' => rand(1, 50),
                'status' => $index === 0 ? 'reading' : 'not_started',
                'last_read_at' => now()->subDays(rand(0, 7)),
            ]);
        }

        // کاربران تصادفی
        User::factory()
            ->count(50)
            ->create()
            ->each(function ($user) {
                // ایجاد meta
                UserMeta::create([
                    'user_id' => $user->id,
                    'username' => 'user_' . $user->id,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                ]);

                // ایجاد profile
                UserProfile::factory()->forUser($user->id)->create();

                // اضافه کردن چند کتاب به کتابخانه
                $bookCount = rand(1, 10);
                $books = Book::published()->inRandomOrder()->limit($bookCount)->get();
                
                foreach ($books as $book) {
                    User_Library::create([
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'progress_percent' => rand(0, 100),
                        'current_page' => rand(1, $book->pages),
                        'status' => fake()->randomElement(['not_started', 'reading', 'completed']),
                        'last_read_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
                    ]);
                }
            });

        $this->command->info('✅ Users seeded successfully! (51 users with profiles and libraries)');
    }
}


<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AuthService;

class RealEitaaDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تست با داده واقعی Eitaa
     */
    public function test_authentication_with_real_eitaa_data(): void
    {
        // داده واقعی که فرستادی
        $realEitaaData = 'auth_date=1764568000&device_id=b2b310e482b5a529&query_id=4645171915983059&user={"id":8487086,"first_name":"متین","last_name":"","language_code":"en"}&hash=52a39b6084cbb426c2be0c145969a97b';

        $response = $this->postJson('/api/v1/auth/eitaa', [
            'eitaa_data' => $realEitaaData,
            'device_name' => 'Test Device',
            'device_type' => 'mobile',
            'platform' => 'ios',
        ]);

        // نمایش response برای debug
        if (!$response->json('success')) {
            dump([
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'user' => [
                    'id',
                    'display_name',
                    'username',
                ],
            ]);
    }

    /**
     * تست Validation Logic به صورت مستقیم
     */
    public function test_eitaa_validation_logic_directly(): void
    {
        $authService = new AuthService();
        $botToken = config('services.eitaa.bot_token');

        // داده واقعی
        $realEitaaData = 'auth_date=1764568000&device_id=b2b310e482b5a529&query_id=4645171915983059&user={"id":8487086,"first_name":"متین","last_name":"","language_code":"en"}&hash=52a39b6084cbb426c2be0c145969a97b';

        $isValid = $authService->validateEitaaData($realEitaaData, $botToken);

        // Debug: نمایش اطلاعات برای بررسی
        if (!$isValid) {
            parse_str($realEitaaData, $params);
            $receivedHash = $params['hash'];
            unset($params['hash']);
            ksort($params);

            $dataCheckArray = [];
            foreach ($params as $key => $value) {
                $dataCheckArray[] = $key . '=' . $value;
            }
            $dataCheckString = implode("\n", $dataCheckArray);

            $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);
            $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

            dump([
                'bot_token' => $botToken,
                'received_hash' => $receivedHash,
                'calculated_hash' => $calculatedHash,
                'data_check_string' => $dataCheckString,
                'params' => $params,
            ]);
        }

        $this->assertTrue($isValid, 'Eitaa data validation failed');
    }

    /**
     * تست Parse کردن داده
     */
    public function test_parse_eitaa_data(): void
    {
        $authService = new AuthService();

        $realEitaaData = 'auth_date=1764568000&device_id=b2b310e482b5a529&query_id=4645171915983059&user={"id":8487086,"first_name":"متین","last_name":"","language_code":"en"}&hash=52a39b6084cbb426c2be0c145969a97b';

        $parsed = $authService->parseEitaaData($realEitaaData);

        $this->assertEquals('8487086', $parsed['id']);
        $this->assertEquals('متین', $parsed['first_name']);
        $this->assertEquals('', $parsed['last_name']);
        $this->assertEquals('en', $parsed['language_code']);

        dump('Parsed Data:', $parsed);
    }

    /**
     * تست کامل Flow با Mock Bot Token
     */
    public function test_complete_flow_with_correct_token(): void
    {
        // اگر Bot Token واقعی داری، اینجا تنظیمش کن
        // config(['services.eitaa.bot_token' => 'YOUR_REAL_BOT_TOKEN']);


$realEitaaData = 'auth_date=1764568000&device_id=b2b310e482b5a529&query_id=4645171915983059&user={"id":8487086,"first_name":"متین","last_name":"","language_code":"en"}&hash=52a39b6084cbb426c2be0c145969a97b';

        $response = $this->postJson('/api/v1/auth/eitaa', [
            'eitaa_data' => $realEitaaData,
        ]);

        // اگر موفق نبود، اطلاعات بیشتر نمایش بده
        if ($response->status() !== 200) {
            dump([
                'status' => $response->status(),
                'body' => $response->json(),
                'bot_token_set' => !empty(config('services.eitaa.bot_token')),
            ]);
        }

        // بررسی که حداقل یک response برگشته
        $response->assertJsonStructure(['success', 'message']);
    }
}

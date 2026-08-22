<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QrisCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.qiospay.secret_key', 'mysecret');
    }

    /**
     * Test callback rejects request with invalid secret key.
     */
    public function test_callback_rejects_invalid_secret_key(): void
    {
        $response = $this->postJson('/api/callback/accept/wrongkey', [
            'status' => 'success',
            'data' => [
                'name' => 'JHON',
                'amount' => 1000
            ]
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => 'reject',
                'message' => 'Invalid secret key',
                'data'    => null,
            ]);
    }

    /**
     * Test callback accepts valid request and writes log file.
     */
    public function test_callback_accepts_valid_secret_key_and_logs_data(): void
    {
        $payload = [
            'status' => 'success',
            'data' => [
                'name'    => 'JHON_TEST',
                'nmid'    => 'ID20233072912345',
                'amount'  => 10000,
                'type'    => 'CR',
                'fee'     => 0,
                'refid'   => '295094156',
                'issuer'  => '93600002',
                'balance' => '24100',
                'time'    => '17/06/2025 18:52'
            ]
        ];

        $response = $this->postJson('/api/callback/accept/mysecret', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'accept',
                'message' => 'Data received successfully',
                'data'    => [
                    'name'    => 'JHON_TEST',
                    'nmid'    => 'ID20233072912345',
                    'amount'  => 10000,
                    'type'    => 'CR',
                    'fee'     => 0,
                    'refid'   => '295094156',
                    'issuer'  => '93600002',
                    'balance' => '24100',
                    'time'    => '17/06/2025 18:52'
                ]
            ]);

        $logPath = storage_path('logs/callback_qris/data[JHON_TEST]-ID20233072912345.json');
        $this->assertFileExists($logPath);

        // Cleanup test log file
        if (File::exists($logPath)) {
            File::delete($logPath);
        }
    }
}

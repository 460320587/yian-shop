<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\SmsCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmsCodeServiceRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_uses_injected_driver(): void
    {
        $driver = new class([]) implements SmsDriverInterface {
            public bool $wasCalled = false;
            public function __construct(private readonly array $config) {}
            public function send(string $phone, string $templateCode, array $params): bool {
                $this->wasCalled = true;
                return true;
            }
            public function getName(): string { return 'test'; }
        };

        $service = new SmsCodeService($driver);
        Cache::put('captcha_key_123', 'ABCD', now()->addMinutes(5));

        $service->send('13800138000', 'captcha_key_123', 'ABCD');

        $this->assertTrue($driver->wasCalled);
        $this->assertDatabaseHas('sms_logs', [
            'phone' => '13800138000',
            'provider' => 'test',
        ]);
    }
}

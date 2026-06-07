<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Webhooks;

use App\Domains\Payment\Webhooks\MockWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MockWebhookVerifierTest extends TestCase
{
    public function test_always_passes_in_skip_mode(): void
    {
        $verifier = new MockWebhookVerifier('mock');
        $request = Request::create('/webhooks/mock', 'POST', ['out_trade_no' => 'TEST123']);

        $this->assertTrue($verifier->verify($request));
    }

    public function test_strict_mode_requires_test_signature_header(): void
    {
        $verifier = new MockWebhookVerifier('mock', 'strict');
        $request = Request::create('/webhooks/mock', 'POST', ['out_trade_no' => 'TEST123']);

        $this->expectException(ValidationException::class);
        $verifier->verify($request);
    }

    public function test_strict_mode_passes_with_correct_header(): void
    {
        $verifier = new MockWebhookVerifier('mock', 'strict');
        $request = Request::create('/webhooks/mock', 'POST', ['out_trade_no' => 'TEST123'], [], [], [
            'HTTP_X_MOCK_SIGNATURE' => 'test',
        ]);

        $this->assertTrue($verifier->verify($request));
    }

    public function test_returns_correct_gateway_name(): void
    {
        $verifier = new MockWebhookVerifier('mock');
        $this->assertSame('mock', $verifier->getGateway());
    }
}

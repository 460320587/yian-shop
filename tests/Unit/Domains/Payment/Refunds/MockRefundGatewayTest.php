<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\MockRefundGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockRefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_correct_path(): void
    {
        $gateway = new MockRefundGateway();
        $this->assertSame('original', $gateway->getPath());
    }

    public function test_refund_generates_gateway_refund_no(): void
    {
        $gateway = new MockRefundGateway();
        $refund = RefundRecord::factory()->create(['refund_path' => 'original']);

        $response = $gateway->refund($refund);

        $this->assertArrayHasKey('gateway_refund_no', $response);
        $this->assertNotEmpty($response['gateway_refund_no']);
        $this->assertStringStartsWith('MOCK_REFUND_', $response['gateway_refund_no']);
    }

    public function test_refund_returns_success_status(): void
    {
        $gateway = new MockRefundGateway();
        $refund = RefundRecord::factory()->create(['refund_path' => 'original']);

        $response = $gateway->refund($refund);

        $this->assertArrayHasKey('status', $response);
        $this->assertSame('success', $response['status']);
    }
}

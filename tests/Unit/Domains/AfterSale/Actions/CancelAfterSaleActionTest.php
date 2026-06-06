<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AfterSale\Actions;

use App\Domains\AfterSale\Actions\CancelAfterSaleAction;
use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelAfterSaleActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_pending_after_sale(): void
    {
        $customer = Customer::factory()->create();
        $afterSale = AfterSale::factory()->create([
            'customer_id' => $customer->id,
            'status' => 1,
        ]);

        (new CancelAfterSaleAction($afterSale))->handle();

        $afterSale->refresh();
        $this->assertEquals(6, $afterSale->status);
    }

    public function test_throws_when_already_closed(): void
    {
        $customer = Customer::factory()->create();
        $afterSale = AfterSale::factory()->create([
            'customer_id' => $customer->id,
            'status' => 6,
        ]);

        $this->expectException(BusinessException::class);
        (new CancelAfterSaleAction($afterSale))->handle();
    }
}

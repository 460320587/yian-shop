<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Invoice\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_get_dashboard_stats(): void
    {
        $this->authAdmin();

        // 创建测试数据（避免factory级联创建额外数据）
        $customers = Customer::factory()->count(5)->create();
        Product::factory()->count(3)->create();
        Order::factory()->count(2)->create(['customer_id' => $customers->first()->id, 'created_at' => now()]);
        AfterSale::factory()->create(['customer_id' => $customers->first()->id, 'status' => 11]);
        $orders = Order::all();
        Invoice::factory()->create(['order_id' => $orders->first()->id, 'customer_id' => $customers->first()->id, 'status' => 11]);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'today_orders',
                    'today_sales',
                    'total_customers',
                    'total_products',
                    'pending_after_sales',
                    'pending_invoices',
                    'recent_orders',
                    'sales_trend',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['today_orders']);
        $this->assertEquals(5, $data['total_customers']);
        $this->assertEquals(3, $data['total_products']);
        $this->assertEquals(1, $data['pending_after_sales']);
        $this->assertEquals(1, $data['pending_invoices']);
        $this->assertCount(2, $data['recent_orders']);
        $this->assertCount(7, $data['sales_trend']);
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/admin/dashboard')
            ->assertUnauthorized();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Export;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_export_orders(): void
    {
        $this->authAdmin();
        $this->seed([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
        ]);

        $response = $this->postJson('/api/v1/admin/exports/orders');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');

        $content = $response->streamedContent();
        $this->assertStringContainsString('订单编号', $content);
        $this->assertStringContainsString('Y202601010001', $content);
    }

    public function test_guest_cannot_export_orders(): void
    {
        $response = $this->postJson('/api/v1/admin/exports/orders');
        $response->assertStatus(401);
    }
}

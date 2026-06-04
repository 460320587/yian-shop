<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Invoice\Models\Invoice;
use App\Domains\Order\Models\Order;
use App\Domains\Portal\Models\Announcement;
use App\Domains\Portal\Models\Banner;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('admin-api')->plainTextToken);
        return $admin;
    }

    // ========== 客户管理 ==========

    public function test_admin_can_list_customers(): void
    {
        $this->authAdmin();
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/customers');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_search_customers(): void
    {
        $this->authAdmin();
        Customer::factory()->create(['phone' => '13800138000']);
        Customer::factory()->create(['phone' => '13900139000']);

        $response = $this->getJson('/api/v1/admin/customers?keyword=13800');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/admin/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $customer->id);
    }

    // ========== 订单管理 ==========

    public function test_admin_can_list_orders(): void
    {
        $this->authAdmin();
        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_view_order_detail(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    // ========== Banner 管理 ==========

    public function test_admin_can_list_banners(): void
    {
        $this->authAdmin();
        Banner::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/banners');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_banner(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/banners', [
            'image' => 'https://example.com/banner.jpg',
            'link_type' => 'url',
            'link_target' => 'https://example.com',
            'position' => 'home',
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.image', 'https://example.com/banner.jpg');
    }

    public function test_admin_can_update_banner(): void
    {
        $this->authAdmin();
        $banner = Banner::factory()->create();

        $response = $this->putJson("/api/v1/admin/banners/{$banner->id}", [
            'title' => '新标题',
            'status' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', '新标题')
            ->assertJsonPath('data.status', 0);
    }

    public function test_admin_can_delete_banner(): void
    {
        $this->authAdmin();
        $banner = Banner::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/banners/{$banner->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
    }

    // ========== 公告管理 ==========

    public function test_admin_can_list_announcements(): void
    {
        $this->authAdmin();
        Announcement::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/announcements');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_announcement(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/announcements', [
            'title' => '系统维护通知',
            'content' => '系统将于今晚进行维护',
            'type' => 'general',
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', '系统维护通知');
    }

    public function test_admin_can_update_announcement(): void
    {
        $this->authAdmin();
        $announcement = Announcement::factory()->create();

        $response = $this->putJson("/api/v1/admin/announcements/{$announcement->id}", [
            'status' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 0);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $this->authAdmin();
        $announcement = Announcement::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/announcements/{$announcement->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('announcements', ['id' => $announcement->id]);
    }

    // ========== 售后审核 ==========

    public function test_admin_can_list_after_sales(): void
    {
        $this->authAdmin();
        AfterSale::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/after-sales');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_audit_after_sale(): void
    {
        $this->authAdmin();
        $afterSale = AfterSale::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/after-sales/{$afterSale->id}/audit", [
            'status' => 2,
            'audit_remark' => '审核通过',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 2)
            ->assertJsonPath('data.audit_remark', '审核通过');
    }

    // ========== 发票管理 ==========

    public function test_admin_can_list_invoices(): void
    {
        $this->authAdmin();
        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_audit_invoice(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/invoices/{$invoice->id}/audit", [
            'status' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 2);
    }

    public function test_admin_can_issue_invoice(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create(['status' => 2]);

        $response = $this->putJson("/api/v1/admin/invoices/{$invoice->id}/issue", [
            'invoice_no' => '01100190001112345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 4)
            ->assertJsonPath('data.invoice_no', '01100190001112345678');
    }

    // ========== 工单管理 ==========

    public function test_admin_can_list_tickets(): void
    {
        $this->authAdmin();
        Ticket::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_process_ticket(): void
    {
        $this->authAdmin();
        $ticket = Ticket::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/tickets/{$ticket->id}/process", [
            'status' => 2,
            'remark' => '已收到投诉，正在处理',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 2)
            ->assertJsonPath('data.remark', '已收到投诉，正在处理');
    }

    public function test_unauthenticated_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/v1/admin/customers');
        $response->assertStatus(401);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_tickets(): void
    {
        $this->authAdmin();
        Ticket::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/tickets');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ticket_no', 'customer_id', 'status', 'type', 'priority', 'customer'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_tickets_by_status(): void
    {
        $this->authAdmin();
        Ticket::factory()->create(['status' => 1]);
        Ticket::factory()->create(['status' => 2]);

        $response = $this->getJson('/api/v1/admin/tickets?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.status'));
    }

    public function test_admin_can_filter_tickets_by_type(): void
    {
        $this->authAdmin();
        Ticket::factory()->create(['type' => 1]);
        Ticket::factory()->create(['type' => 2]);

        $response = $this->getJson('/api/v1/admin/tickets?type=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.type'));
    }

    public function test_admin_can_filter_tickets_by_priority(): void
    {
        $this->authAdmin();
        Ticket::factory()->create(['priority' => 1]);
        Ticket::factory()->create(['priority' => 3]);

        $response = $this->getJson('/api/v1/admin/tickets?priority=3');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(3, $response->json('data.0.priority'));
    }

    public function test_admin_can_view_ticket_detail(): void
    {
        $this->authAdmin();
        $ticket = Ticket::factory()->create();

        $response = $this->getJson("/api/v1/admin/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.ticket_no', $ticket->ticket_no)
            ->assertJsonStructure([
                'data' => ['id', 'ticket_no', 'customer', 'order'],
            ]);
    }

    public function test_admin_can_process_ticket(): void
    {
        $this->authAdmin();
        $ticket = Ticket::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/tickets/{$ticket->id}/process", [
            'status' => 2,
            'remark' => '正在处理中',
            'priority' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 2)
            ->assertJsonPath('data.remark', '正在处理中')
            ->assertJsonPath('data.priority', 2);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 2,
            'processed_by' => auth('admin')->id(),
        ]);
        $this->assertNotNull($ticket->fresh()->processed_at);
    }

    public function test_admin_can_complete_ticket(): void
    {
        $this->authAdmin();
        $ticket = Ticket::factory()->create(['status' => 2]);

        $response = $this->putJson("/api/v1/admin/tickets/{$ticket->id}/process", [
            'status' => 4,
            'remark' => '已解决',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 4);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 4,
        ]);
        $this->assertNotNull($ticket->fresh()->completed_at);
    }

    public function test_admin_cannot_view_nonexistent_ticket(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/tickets/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_tickets(): void
    {
        $this->getJson('/api/v1/admin/tickets')
            ->assertUnauthorized();
    }
}

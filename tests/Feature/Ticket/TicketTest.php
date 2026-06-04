<?php

declare(strict_types=1);

namespace Tests\Feature\Ticket;

use App\Domains\Order\Models\Order;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_ticket_list(): void
    {
        $customer = $this->authCustomer();
        Ticket::factory()->count(3)->create(['customer_id' => $customer->id]);
        Ticket::factory()->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_can_filter_by_status(): void
    {
        $customer = $this->authCustomer();
        Ticket::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 1]);
        Ticket::factory()->count(3)->create(['customer_id' => $customer->id, 'status' => 2]);

        $response = $this->getJson('/api/v1/tickets?status=2');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_can_filter_by_type(): void
    {
        $customer = $this->authCustomer();
        Ticket::factory()->count(2)->create(['customer_id' => $customer->id, 'type' => 1]);
        Ticket::factory()->count(3)->create(['customer_id' => $customer->id, 'type' => 2]);

        $response = $this->getJson('/api/v1/tickets?type=2');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_ticket(): void
    {
        $customer = $this->authCustomer();

        $response = $this->postJson('/api/v1/tickets', [
            'type' => 2,
            'title' => '印刷质量问题',
            'content' => '收到的宣传册颜色与样品不符',
            'expected_resolution' => '要求重新印刷',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '印刷质量问题')
            ->assertJsonPath('data.status', 1);

        $this->assertDatabaseHas('tickets', [
            'customer_id' => $customer->id,
            'title' => '印刷质量问题',
            'type' => 2,
        ]);
    }

    public function test_user_can_create_ticket_with_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/tickets', [
            'order_id' => $order->id,
            'type' => 3,
            'title' => '物流延误',
            'content' => '订单已经超过承诺交货期',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_id', $order->id);
    }

    public function test_create_ticket_requires_order_ownership(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->postJson('/api/v1/tickets', [
            'order_id' => $order->id,
            'type' => 1,
            'title' => '投诉',
            'content' => '测试',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_get_ticket_detail(): void
    {
        $customer = $this->authCustomer();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $ticket->id);
    }

    public function test_user_cannot_view_others_ticket(): void
    {
        $this->authCustomer();
        $otherTicket = Ticket::factory()->create();

        $response = $this->getJson("/api/v1/tickets/{$otherTicket->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_cancel_pending_ticket(): void
    {
        $customer = $this->authCustomer();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'status' => 1]);

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 0]);
    }

    public function test_user_cannot_cancel_processing_ticket(): void
    {
        $customer = $this->authCustomer();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'status' => 2]);

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}/cancel");

        $response->assertStatus(400);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_notification_list(): void
    {
        $customer = $this->authCustomer();
        CustomerNotification::factory()->count(3)->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        CustomerNotification::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_can_filter_by_read_status(): void
    {
        $customer = $this->authCustomer();
        CustomerNotification::factory()->create(['customer_id' => $customer->id, 'is_read' => 1]);
        CustomerNotification::factory()->count(2)->create(['customer_id' => $customer->id, 'is_read' => 0]);

        $response = $this->getJson('/api/v1/notifications?is_read=0');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_mark_notification_read(): void
    {
        $customer = $this->authCustomer();
        $notification = CustomerNotification::factory()->create([
            'customer_id' => $customer->id,
            'is_read' => 0,
        ]);

        $response = $this->putJson('/api/v1/notifications/' . $notification->id . '/read');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $notification->refresh();
        $this->assertEquals(1, $notification->is_read);
    }

    public function test_user_can_mark_all_notifications_read(): void
    {
        $customer = $this->authCustomer();
        CustomerNotification::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'is_read' => 0,
        ]);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseCount('customer_notifications', 3);
        $this->assertDatabaseMissing('customer_notifications', [
            'customer_id' => $customer->id,
            'is_read' => 0,
        ]);
    }

    public function test_user_can_get_unread_count(): void
    {
        $customer = $this->authCustomer();
        CustomerNotification::factory()->create(['customer_id' => $customer->id, 'is_read' => 0]);
        CustomerNotification::factory()->create(['customer_id' => $customer->id, 'is_read' => 0]);
        CustomerNotification::factory()->create(['customer_id' => $customer->id, 'is_read' => 1]);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_user_cannot_read_others_notification(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $notification = CustomerNotification::factory()->create([
            'customer_id' => $otherCustomer->id,
            'is_read' => 0,
        ]);

        $response = $this->putJson('/api/v1/notifications/' . $notification->id . '/read');
        $response->assertStatus(403);
    }
}

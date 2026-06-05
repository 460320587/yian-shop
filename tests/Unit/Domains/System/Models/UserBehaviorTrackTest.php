<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\System\Models;

use App\Domains\System\Models\UserBehaviorTrack;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBehaviorTrackTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_track(): void
    {
        $track = UserBehaviorTrack::factory()->create();
        $this->assertDatabaseHas('user_behavior_tracks', ['id' => $track->id]);
    }

    public function test_track_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $track = UserBehaviorTrack::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $track->customer);
    }

    public function test_event_data_is_cast_to_array(): void
    {
        $track = UserBehaviorTrack::factory()->create(['event_data' => ['x' => 100, 'y' => 200]]);
        $this->assertIsArray($track->event_data);
        $this->assertSame(100, $track->event_data['x']);
    }

    public function test_track_can_be_created_without_customer(): void
    {
        $track = UserBehaviorTrack::factory()->create(['customer_id' => null]);
        $this->assertNull($track->customer_id);
    }
}

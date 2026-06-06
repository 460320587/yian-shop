<?php

declare(strict_types=1);

namespace Tests\Feature\User\Models;

use App\Domains\User\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_profile(): void
    {
        $profile = UserProfile::factory()->create();

        $this->assertDatabaseHas('user_profiles', ['id' => $profile->id]);
    }

    public function test_belongs_to_user(): void
    {
        $profile = UserProfile::factory()->create();

        $this->assertNotNull($profile->user);
    }

    public function test_gender_is_integer(): void
    {
        $profile = UserProfile::factory()->create(['gender' => 1]);

        $this->assertSame(1, $profile->gender);
    }

    public function test_birthday_is_date(): void
    {
        $profile = UserProfile::factory()->create(['birthday' => '1990-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $profile->birthday);
    }

    public function test_user_id_is_unique(): void
    {
        $profile = UserProfile::factory()->create();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        UserProfile::factory()->create(['user_id' => $profile->user_id]);
    }
}

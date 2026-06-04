<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'phone' => '13800138000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $customer->refresh();
        $this->assertNotNull($customer->reset_token);
        $this->assertNotNull($customer->reset_token_expires_at);
    }

    public function test_forgot_password_requires_valid_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'phone' => 'not-a-phone',
        ]);

        $response->assertStatus(422);
    }

    public function test_forgot_password_fails_for_nonexistent_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'phone' => '19999999999',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_reset_password_with_token(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'password' => Hash::make('old_password'),
        ]);
        $customer->update([
            'reset_token' => 'test_token_123',
            'reset_token_expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '13800138000',
            'token' => 'test_token_123',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $customer->refresh();
        $this->assertTrue(Hash::check('new_password_123', $customer->password));
        $this->assertNull($customer->reset_token);
        $this->assertNull($customer->reset_token_expires_at);
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'reset_token' => 'valid_token',
            'reset_token_expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '13800138000',
            'token' => 'wrong_token',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_fails_with_expired_token(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'reset_token' => 'expired_token',
            'reset_token_expires_at' => now()->subHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '13800138000',
            'token' => 'expired_token',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_requires_new_password(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '13800138000',
            'token' => 'some_token',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }
}

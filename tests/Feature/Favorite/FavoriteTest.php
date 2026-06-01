<?php

declare(strict_types=1);

namespace Tests\Feature\Favorite;

use App\Domains\Product\Models\CustomerFavorite;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_favorite_list(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        CustomerFavorite::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/favorites');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_user_can_add_favorite(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);

        $response = $this->postJson('/api/v1/favorites', [
            'product_id' => $product->id,
            'remark' => '喜欢这款',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('customer_favorites', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'remark' => '喜欢这款',
            'status' => 1,
        ]);
    }

    public function test_user_can_remove_favorite(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $favorite = CustomerFavorite::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson('/api/v1/favorites/' . $favorite->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseMissing('customer_favorites', [
            'id' => $favorite->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_remove_others_favorite(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $product = Product::factory()->create(['status' => 1]);
        $favorite = CustomerFavorite::factory()->create([
            'customer_id' => $otherCustomer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson('/api/v1/favorites/' . $favorite->id);

        $response->assertStatus(404);
    }

    public function test_add_favorite_is_idempotent(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);

        $this->postJson('/api/v1/favorites', ['product_id' => $product->id]);
        $response = $this->postJson('/api/v1/favorites', ['product_id' => $product->id]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseCount('customer_favorites', 1);
    }
}

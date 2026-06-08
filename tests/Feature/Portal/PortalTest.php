<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Domains\Portal\Models\Announcement;
use App\Domains\Portal\Models\Banner;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_banner_list(): void
    {
        Banner::factory()->count(3)->create(['position' => 'home', 'status' => 1, 'sort' => 0]);
        Banner::factory()->create(['position' => 'category', 'status' => 1]);
        Banner::factory()->create(['position' => 'home', 'status' => 0]);

        $response = $this->getJson('/api/v1/portal/banners');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_banners_can_filter_by_position(): void
    {
        Banner::factory()->count(2)->create(['position' => 'home', 'status' => 1]);
        Banner::factory()->count(3)->create(['position' => 'category', 'status' => 1]);

        $response = $this->getJson('/api/v1/portal/banners?position=category');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_banners_respect_display_time(): void
    {
        Banner::factory()->create([
            'position' => 'home',
            'status' => 1,
            'display_start' => now()->addDay(),
        ]);
        Banner::factory()->create([
            'position' => 'home',
            'status' => 1,
            'display_end' => now()->subDay(),
        ]);
        Banner::factory()->create(['position' => 'home', 'status' => 1]);

        $response = $this->getJson('/api/v1/portal/banners');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_banners_are_sorted_by_sort_field(): void
    {
        $banner1 = Banner::factory()->create(['position' => 'home', 'status' => 1, 'sort' => 10]);
        $banner2 = Banner::factory()->create(['position' => 'home', 'status' => 1, 'sort' => 5]);
        $banner3 = Banner::factory()->create(['position' => 'home', 'status' => 1, 'sort' => 20]);

        $response = $this->getJson('/api/v1/portal/banners');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($banner2->id, $data[0]['id']);
        $this->assertEquals($banner1->id, $data[1]['id']);
        $this->assertEquals($banner3->id, $data[2]['id']);
    }

    public function test_guest_can_get_announcement_list(): void
    {
        Announcement::factory()->count(3)->create(['status' => 1]);
        Announcement::factory()->create(['status' => 0]);

        $response = $this->getJson('/api/v1/portal/announcements');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_announcements_can_filter_by_type(): void
    {
        Announcement::factory()->count(2)->create(['status' => 1, 'type' => 'general']);
        Announcement::factory()->count(3)->create(['status' => 1, 'type' => 'promotion']);

        $response = $this->getJson('/api/v1/portal/announcements?type=promotion');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_announcements_respect_display_time(): void
    {
        Announcement::factory()->create([
            'status' => 1,
            'display_start' => now()->addDay(),
        ]);
        Announcement::factory()->create([
            'status' => 1,
            'display_end' => now()->subDay(),
        ]);
        Announcement::factory()->create(['status' => 1]);

        $response = $this->getJson('/api/v1/portal/announcements');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_can_get_hot_products(): void
    {
        Product::factory()->count(3)->create(['status' => 1, 'is_hot' => 1, 'is_new' => 0, 'sales_count' => 100]);
        Product::factory()->count(2)->create(['status' => 1, 'is_hot' => 0, 'is_new' => 0]);
        Product::factory()->create(['status' => 0, 'is_hot' => 1]);

        $response = $this->getJson('/api/v1/portal/hot-products');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_hot_products_are_sorted_by_sales_count(): void
    {
        $product1 = Product::factory()->create(['status' => 1, 'is_hot' => 1, 'sales_count' => 50]);
        $product2 = Product::factory()->create(['status' => 1, 'is_hot' => 1, 'sales_count' => 200]);
        $product3 = Product::factory()->create(['status' => 1, 'is_hot' => 1, 'sales_count' => 100]);

        $response = $this->getJson('/api/v1/portal/hot-products');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($product2->id, $data[0]['id']);
        $this->assertEquals($product3->id, $data[1]['id']);
        $this->assertEquals($product1->id, $data[2]['id']);
    }

    public function test_guest_can_get_new_arrivals(): void
    {
        Product::factory()->count(3)->create(['status' => 1, 'is_new' => 1, 'is_hot' => 0]);
        Product::factory()->count(2)->create(['status' => 1, 'is_new' => 0, 'is_hot' => 0]);
        Product::factory()->create(['status' => 0, 'is_new' => 1, 'is_hot' => 0]);

        $response = $this->getJson('/api/v1/portal/new-arrivals');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_can_get_home_data(): void
    {
        Banner::factory()->count(2)->create(['position' => 'home', 'status' => 1]);
        Announcement::factory()->count(2)->create(['status' => 1]);
        Product::factory()->count(3)->create(['status' => 1, 'is_hot' => 1, 'is_new' => 0]);
        Product::factory()->count(2)->create(['status' => 1, 'is_new' => 1, 'is_hot' => 0]);

        $response = $this->getJson('/api/v1/portal/home');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.banners')
            ->assertJsonCount(2, 'data.announcements')
            ->assertJsonCount(3, 'data.hot_products')
            ->assertJsonCount(2, 'data.new_arrivals');
    }

    public function test_home_returns_empty_arrays_when_no_data(): void
    {
        $response = $this->getJson('/api/v1/portal/home');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(0, 'data.banners')
            ->assertJsonCount(0, 'data.announcements')
            ->assertJsonCount(0, 'data.hot_products')
            ->assertJsonCount(0, 'data.new_arrivals');
    }

    public function test_home_data_is_cached(): void
    {
        Banner::factory()->count(2)->create(['position' => 'home', 'status' => 1]);
        Announcement::factory()->count(2)->create(['status' => 1]);
        Product::factory()->count(3)->create(['status' => 1, 'is_hot' => 1, 'is_new' => 0]);
        Product::factory()->count(2)->create(['status' => 1, 'is_new' => 1, 'is_hot' => 0]);

        $response1 = $this->getJson('/api/v1/portal/home');
        $response1->assertStatus(200);
        $this->assertTrue(Cache::has('portal_home'));

        $response2 = $this->getJson('/api/v1/portal/home');
        $response2->assertStatus(200)
            ->assertJson($response1->json());
    }

    public function test_banner_update_clears_home_cache(): void
    {
        $banner = Banner::factory()->create(['position' => 'home', 'status' => 1]);
        $this->getJson('/api/v1/portal/home')->assertStatus(200);
        $this->assertTrue(Cache::has('portal_home'));

        $banner->update(['title' => 'Updated Title']);

        $this->assertFalse(Cache::has('portal_home'));
    }

    public function test_banner_delete_clears_home_cache(): void
    {
        $banner = Banner::factory()->create(['position' => 'home', 'status' => 1]);
        $this->getJson('/api/v1/portal/home')->assertStatus(200);
        $this->assertTrue(Cache::has('portal_home'));

        $banner->delete();

        $this->assertFalse(Cache::has('portal_home'));
    }

    public function test_announcement_update_clears_home_cache(): void
    {
        $announcement = Announcement::factory()->create(['status' => 1]);
        $this->getJson('/api/v1/portal/home')->assertStatus(200);
        $this->assertTrue(Cache::has('portal_home'));

        $announcement->update(['title' => 'Updated Title']);

        $this->assertFalse(Cache::has('portal_home'));
    }

    public function test_announcement_delete_clears_home_cache(): void
    {
        $announcement = Announcement::factory()->create(['status' => 1]);
        $this->getJson('/api/v1/portal/home')->assertStatus(200);
        $this->assertTrue(Cache::has('portal_home'));

        $announcement->delete();

        $this->assertFalse(Cache::has('portal_home'));
    }
}

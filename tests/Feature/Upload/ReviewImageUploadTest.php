<?php

declare(strict_types=1);

namespace Tests\Feature\Upload;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_upload_review_images(): void
    {
        Storage::fake('public');
        $this->authCustomer();

        $file = UploadedFile::fake()->image('review.jpg', 800, 600);

        $response = $this->postJson('/api/v1/upload/review-images', [
            'images' => [$file],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.urls');

        $disk = Storage::disk('public');
        $stored = collect($disk->allFiles('reviews/' . now()->format('Y/m')))
            ->contains(fn (string $path) => str_starts_with(basename($path), 'review_'));

        $this->assertTrue($stored, '上传图片未保存到预期目录');
    }

    public function test_upload_rejects_invalid_mime(): void
    {
        Storage::fake('public');
        $this->authCustomer();

        $file = UploadedFile::fake()->create('review.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/upload/review-images', [
            'images' => [$file],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_upload_rejects_oversized_image(): void
    {
        Storage::fake('public');
        $this->authCustomer();

        $file = UploadedFile::fake()->image('big.jpg')->size(6 * 1024); // 6MB

        $response = $this->postJson('/api/v1/upload/review-images', [
            'images' => [$file],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_upload_rejects_more_than_nine_images(): void
    {
        Storage::fake('public');
        $this->authCustomer();

        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $files[] = UploadedFile::fake()->image('review' . $i . '.jpg', 100, 100);
        }

        $response = $this->postJson('/api/v1/upload/review-images', [
            'images' => $files,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_guest_cannot_upload_review_images(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('review.jpg', 800, 600);

        $response = $this->postJson('/api/v1/upload/review-images', [
            'images' => [$file],
        ]);

        $response->assertStatus(401);
    }
}

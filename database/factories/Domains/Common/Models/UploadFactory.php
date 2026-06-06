<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Common\Models;

use App\Domains\Common\Models\Upload;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['jpg', 'png', 'pdf']);

        return [
            'user_id' => Customer::factory(),
            'purpose' => $this->faker->randomElement(['product_image', 'user_file', 'prepress_pdf']),
            'original_name' => $this->faker->word() . '.' . $ext,
            'storage_path' => 'uploads/' . $this->faker->uuid() . '.' . $ext,
            'url' => $this->faker->imageUrl(),
            'file_size' => $this->faker->numberBetween(1024, 10485760),
            'mime_type' => $ext === 'pdf' ? 'application/pdf' : 'image/jpeg',
            'extension' => $ext,
            'width' => $ext !== 'pdf' ? $this->faker->numberBetween(100, 2000) : null,
            'height' => $ext !== 'pdf' ? $this->faker->numberBetween(100, 2000) : null,
            'hash_md5' => $this->faker->md5(),
            'is_virus_scanned' => $this->faker->numberBetween(0, 1),
            'virus_scan_result' => $this->faker->optional()->randomElement(['clean', 'infected', 'error']),
            'status' => 1,
        ];
    }
}

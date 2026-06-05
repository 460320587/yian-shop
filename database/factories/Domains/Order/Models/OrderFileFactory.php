<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Enterprise\Models\CustomerBrand;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFileFactory extends Factory
{
    protected $model = OrderFile::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'file_url' => $this->faker->url() . '/file.pdf',
            'thumb_url' => $this->faker->optional()->url() . '/thumb.jpg',
            'page_count' => $this->faker->numberBetween(1, 100),
            'ink_coverage' => $this->faker->optional()->randomFloat(2, 0, 100),
            'brand_id' => CustomerBrand::factory(),
            'file_name' => $this->faker->word() . '.pdf',
            'file_size' => $this->faker->numberBetween(1024, 10485760),
            'file_type' => 'application/pdf',
            'archive_path' => null,
            'archive_status' => 0,
            'version' => 1,
            'status' => 1,
        ];
    }
}

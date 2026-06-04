<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->bothify('PROD-####'),
            'price_min' => $this->faker->numberBetween(100, 10000),
            'price_max' => $this->faker->numberBetween(10000, 100000),
            'status' => $this->faker->numberBetween(0, 2),
            'sort' => $this->faker->numberBetween(0, 100),
            'cover_image' => $this->faker->optional()->imageUrl(),
            'thumbnail' => $this->faker->optional()->imageUrl(400, 400),
            'sales_count' => $this->faker->numberBetween(0, 10000),
            'is_hot' => $this->faker->boolean(20) ? 1 : 0,
            'is_new' => $this->faker->boolean(20) ? 1 : 0,
        ];
    }
}

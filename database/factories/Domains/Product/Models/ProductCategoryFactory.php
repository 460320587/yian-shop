<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'parent_id' => 0,
            'name' => $this->faker->word(),
            'icon' => $this->faker->optional()->imageUrl(),
            'sort' => $this->faker->numberBetween(0, 100),
            'status' => 1,
            'level' => 1,
            'path' => '',
        ];
    }
}

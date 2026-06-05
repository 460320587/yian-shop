<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\ParamTemplate;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParamTemplateFactory extends Factory
{
    protected $model = ParamTemplate::class;

    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'param_type' => $this->faker->randomElement(['paper', 'color', 'process', 'size', 'binding']),
            'param_name' => $this->faker->words(2, true),
            'options' => [
                ['id' => 1, 'name' => '选项A', 'price_factor' => 1.0],
                ['id' => 2, 'name' => '选项B', 'price_factor' => 1.2],
            ],
            'rules' => null,
            'version' => 1,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'status' => 1,
        ];
    }
}

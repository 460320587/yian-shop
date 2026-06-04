<?php

declare(strict_types=1);

namespace Database\Factories\Domains\AfterSale\Models;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\AfterSale\Models\AfterSaleItem;
use App\Domains\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AfterSaleItemFactory extends Factory
{
    protected $model = AfterSaleItem::class;

    public function definition(): array
    {
        return [
            'after_sale_id' => AfterSale::factory(),
            'order_item_id' => OrderItem::factory(),
            'product_name' => $this->faker->words(3, true),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_refund' => $this->faker->numberBetween(1000, 50000),
        ];
    }
}

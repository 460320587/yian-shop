<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unitPrice = $this->faker->numberBetween(100, 10000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->words(3, true),
            'spec_info' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'file_url' => $this->faker->optional()->url(),
        ];
    }
}

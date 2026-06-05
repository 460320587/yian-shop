<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductReview;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'order_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'content' => $this->faker->sentence(10),
            'images' => null,
            'reply' => null,
            'reply_at' => null,
            'is_show' => true,
        ];
    }
}

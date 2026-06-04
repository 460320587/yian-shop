<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Invoice\Models;

use App\Domains\Invoice\Models\Invoice;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'invoice_no' => $this->faker->optional()->numerify('INV##########'),
            'type' => $this->faker->randomElement([1, 2]),
            'status' => $this->faker->randomElement([1, 2, 3, 4, 5, 6]),
            'business_type' => $this->faker->randomElement([0, 1, 2, 3]),
            'title' => $this->faker->company(),
            'tax_number' => $this->faker->numerify('##################'),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'email' => $this->faker->optional()->email(),
            'address' => $this->faker->optional()->address(),
            'bank_name' => $this->faker->optional()->randomElement(['中国工商银行', '中国建设银行']),
            'bank_account' => $this->faker->optional()->numerify('####################'),
            'express_no' => null,
            'issued_at' => null,
            'remark' => null,
        ];
    }
}

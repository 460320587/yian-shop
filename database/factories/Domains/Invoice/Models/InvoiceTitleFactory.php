<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Invoice\Models;

use App\Domains\Invoice\Models\InvoiceTitle;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceTitleFactory extends Factory
{
    protected $model = InvoiceTitle::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'title_type' => $this->faker->randomElement([1, 2]),
            'invoice_category' => $this->faker->randomElement([1, 2]),
            'company_name' => $this->faker->company(),
            'tax_number' => $this->faker->numerify('##################'),
            'register_address' => $this->faker->address(),
            'register_phone' => $this->faker->phoneNumber(),
            'bank_name' => $this->faker->randomElement(['中国工商银行', '中国建设银行', '中国农业银行']),
            'bank_account' => $this->faker->numerify('####################'),
            'is_default' => 0,
        ];
    }
}

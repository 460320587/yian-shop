<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Enterprise\Models;

use App\Domains\Enterprise\Models\EnterpriseAuth;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnterpriseAuthFactory extends Factory
{
    protected $model = EnterpriseAuth::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'company_name' => $this->faker->company(),
            'credit_code' => $this->faker->bothify('##################'),
            'legal_person' => $this->faker->name(),
            'legal_person_id_card' => $this->faker->numerify('################'),
            'business_license_img' => $this->faker->imageUrl(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'register_address' => $this->faker->address(),
            'office_address' => $this->faker->address(),
            'valid_date' => $this->faker->date(),
            'auth_status' => $this->faker->randomElement([0, 1, 2, 3, 4, 20]),
            'audit_remark' => $this->faker->optional()->sentence(),
        ];
    }
}

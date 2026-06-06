<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Notification\Models;

use App\Domains\Notification\Models\SmsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    public function definition(): array
    {
        return [
            'phone' => $this->faker->phoneNumber(),
            'template_code' => $this->faker->optional()->numerify('SMS_######'),
            'content' => $this->faker->optional()->sentence(),
            'type' => $this->faker->numberBetween(1, 3),
            'status' => $this->faker->numberBetween(0, 1),
            'provider' => $this->faker->randomElement(['aliyun', 'tencent']),
            'error_msg' => $this->faker->optional()->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'request_id' => $this->faker->optional()->uuid(),
        ];
    }
}

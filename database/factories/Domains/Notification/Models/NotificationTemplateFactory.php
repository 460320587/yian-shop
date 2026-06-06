<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Notification\Models;

use App\Domains\Notification\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->randomElement(['order_paid', 'order_shipped', 'order_completed', 'refund_approved']),
            'name' => $this->faker->sentence(3),
            'event' => $this->faker->word(),
            'channels' => ['in_app', 'sms'],
            'sms_template_code' => $this->faker->optional()->numerify('SMS_######'),
            'email_subject' => $this->faker->optional()->sentence(),
            'email_body' => $this->faker->optional()->paragraph(),
            'wechat_template_id' => $this->faker->optional()->uuid(),
            'in_app_title' => $this->faker->sentence(3),
            'in_app_content' => $this->faker->sentence(10),
            'status' => 1,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Portal\Models;

use App\Domains\Portal\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['general', 'legality', 'promotion']),
            'is_popup' => $this->faker->boolean(20) ? 1 : 0,
            'status' => 1,
            'display_start' => null,
            'display_end' => null,
        ];
    }
}

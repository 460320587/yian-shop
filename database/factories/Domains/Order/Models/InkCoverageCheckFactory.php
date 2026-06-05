<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class InkCoverageCheckFactory extends Factory
{
    protected $model = InkCoverageCheck::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'file_id' => OrderFile::factory(),
            'check_type' => $this->faker->randomElement([1, 2, 3]),
            'ink_type' => $this->faker->randomElement(['CMYK', '专色']),
            'coverage_c' => $this->faker->randomFloat(2, 0, 100),
            'coverage_m' => $this->faker->randomFloat(2, 0, 100),
            'coverage_y' => $this->faker->randomFloat(2, 0, 100),
            'coverage_k' => $this->faker->randomFloat(2, 0, 100),
            'total_coverage' => $this->faker->randomFloat(2, 0, 100),
            'check_result' => $this->faker->randomElement([0, 1]),
            'check_report' => ['pages' => $this->faker->numberBetween(1, 100), 'dpi' => 300],
            'checked_by' => null,
            'checked_at' => $this->faker->optional()->dateTime(),
        ];
    }
}

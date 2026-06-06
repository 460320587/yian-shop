<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Audit\Models;

use App\Domains\Audit\Models\AuditArchive;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditArchiveFactory extends Factory
{
    protected $model = AuditArchive::class;

    public function definition(): array
    {
        return [
            'archive_date' => $this->faker->date(),
            'storage_path' => 's3://archive/' . $this->faker->uuid() . '.parquet',
            'format' => $this->faker->randomElement(['parquet', 'sqlite', 'csv']),
            'record_count' => $this->faker->numberBetween(1000, 1000000),
            'expire_date' => $this->faker->dateTimeBetween('+1 year', '+3 years')->format('Y-m-d'),
            'status' => $this->faker->randomElement([0, 1, 2]),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\ExchangeRateSource;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'effective_on' => fake()->unique()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'rate' => fake()->randomFloat(4, 30, 80),
            'source' => ExchangeRateSource::Manual,
        ];
    }
}

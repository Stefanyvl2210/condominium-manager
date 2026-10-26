<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->bothify('?#');

        return [
            'name' => "Tower {$code}",
            'code' => strtoupper($code),
            'floors' => fake()->numberBetween(4, 12),
            'description' => null,
        ];
    }
}

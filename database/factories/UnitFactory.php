<?php

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\Building;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $floor = fake()->numberBetween(1, 10);

        return [
            'building_id' => Building::factory(),
            'owner_id' => User::factory(),
            'number' => sprintf('%02d%02d', $floor, fake()->unique()->numberBetween(1, 99)),
            'type' => UnitType::Apartment,
            'floor' => $floor,
            'area' => fake()->randomFloat(2, 60, 140),
            'ownership_share' => '10.000000',
        ];
    }

    public function share(string $share): static
    {
        return $this->state(['ownership_share' => $share]);
    }
}

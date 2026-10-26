<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tax_id' => 'J-'.fake()->numerify('########-#'),
            'service' => fake()->randomElement(['Cleaning', 'Security', 'Elevator maintenance', 'Gardening', 'Plumbing']),
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('+58 212-###-####'),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}

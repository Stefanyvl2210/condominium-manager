<?php

namespace Database\Factories;

use App\Models\Condominium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Condominium>
 */
class CondominiumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Parkview Residences',
            'tax_id' => 'J-'.fake()->numerify('########-#'),
            'address' => fake()->address(),
            'email' => 'office@parkview.test',
            'phone' => fake()->numerify('+58 212-###-####'),
            'reserve_fund_rate' => '10.00',
            'late_fee_rate' => '1.00',
            'payment_term_days' => 15,
            'expense_approval_threshold' => 100_000,
        ];
    }
}

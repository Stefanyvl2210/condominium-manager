<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(5_000, 30_000);

        return [
            'unit_id' => Unit::factory(),
            'currency' => Currency::USD,
            'amount' => $amount,
            'amount_usd' => $amount,
            'method' => PaymentMethod::Zelle,
            'reference' => fake()->numerify('##########'),
            'paid_on' => now(),
            'status' => PaymentStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => PaymentStatus::Approved, 'reviewed_at' => now()]);
    }

    public function usd(int $cents): static
    {
        return $this->state(['currency' => Currency::USD, 'amount' => $cents, 'amount_usd' => $cents]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedOn = now()->startOfMonth();

        return [
            'billing_period_id' => BillingPeriod::factory(),
            'unit_id' => Unit::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('######-####'),
            'issued_on' => $issuedOn,
            'due_on' => $issuedOn->copy()->addDays(15),
            'total' => 10_000,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
        ];
    }
}

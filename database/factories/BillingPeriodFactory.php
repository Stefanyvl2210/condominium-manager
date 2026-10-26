<?php

namespace Database\Factories;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->subMonthNoOverflow();

        return [
            'year' => $date->year,
            'month' => $date->month,
            'status' => BillingPeriodStatus::Open,
        ];
    }
}

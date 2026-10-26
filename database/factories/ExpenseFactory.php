<?php

namespace Database\Factories;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Unit;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'vendor_id' => Vendor::factory(),
            'scope' => ExpenseScope::Condominium,
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(5_000, 90_000),
            'incurred_on' => now()->startOfMonth(),
            'status' => ExpenseStatus::Approved,
        ];
    }

    public function forBuilding(Building $building): static
    {
        return $this->state(['scope' => ExpenseScope::Building, 'building_id' => $building->id]);
    }

    public function forUnit(Unit $unit): static
    {
        return $this->state(['scope' => ExpenseScope::Unit, 'unit_id' => $unit->id]);
    }

    public function pendingApproval(): static
    {
        return $this->state(['status' => ExpenseStatus::PendingApproval]);
    }
}

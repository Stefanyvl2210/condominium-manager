<?php

use App\Models\Building;
use App\Models\Condominium;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Store the condominium settings used by the billing rules.
 *
 * @param  array<string, mixed>  $attributes
 */
function condominium(array $attributes = []): Condominium
{
    $condominium = Condominium::factory()->create($attributes);
    app()->forgetInstance(Condominium::class);

    return $condominium;
}

/**
 * Create units in one building with the given ownership shares.
 *
 * @param  list<string>  $shares
 * @return Collection<int, Unit>
 */
function unitsWithShares(array $shares, ?Building $building = null): Collection
{
    $building ??= Building::factory()->create();

    return collect($shares)->values()->map(fn (string $share, int $index) => Unit::factory()
        ->for($building)
        ->share($share)
        ->create(['number' => sprintf('%02d', $index + 1)]));
}

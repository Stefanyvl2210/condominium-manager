<?php

use App\Support\ShareAllocator;

it('splits an amount proportionally to the weights', function () {
    expect(ShareAllocator::allocate(10_000, ['a' => '50', 'b' => '30', 'c' => '20']))
        ->toBe(['a' => 5_000, 'b' => 3_000, 'c' => 2_000]);
});

it('never loses or creates a cent when the split is not exact', function (int $amount, array $weights) {
    $parts = ShareAllocator::allocate($amount, $weights);

    expect(array_sum($parts))->toBe($amount)
        ->and(array_keys($parts))->toBe(array_keys($weights));
})->with([
    'thirds' => [100, ['a' => '33.333333', 'b' => '33.333333', 'c' => '33.333334']],
    'odd cents' => [1, ['a' => '50', 'b' => '50']],
    'many units' => [987_654, array_fill(1, 48, '2.083333')],
    'uneven shares' => [123_457, ['x' => '1.234567', 'y' => '45.678901', 'z' => '53.086532']],
]);

it('gives the leftover cents to the largest remainders', function () {
    // 100 / 3 = 33.33..., one cent is left and goes to the first of the tied parts.
    expect(ShareAllocator::allocate(100, ['a' => '1', 'b' => '1', 'c' => '1']))
        ->toBe(['a' => 34, 'b' => 33, 'c' => 33]);

    // 10 split 15 / 85 gives 1.5 and 8.5; both remainders tie, input order wins.
    expect(ShareAllocator::allocate(10, ['a' => '15', 'b' => '85']))
        ->toBe(['a' => 2, 'b' => 8]);
});

it('rejects an empty or zero weight list', function (array $weights) {
    ShareAllocator::allocate(100, $weights);
})->with([
    'empty' => [[]],
    'zero' => [['a' => '0']],
])->throws(InvalidArgumentException::class);

it('checks that ownership shares add up to exactly 100 %', function () {
    expect(ShareAllocator::isComplete(ShareAllocator::totalInMillionths(['33.333333', '33.333333', '33.333334'])))->toBeTrue()
        ->and(ShareAllocator::isComplete(ShareAllocator::totalInMillionths(['50', '49.999999'])))->toBeFalse();
});

<?php

use App\Enums\Currency;
use App\Support\Money;

it('formats cents in both currencies', function () {
    expect(Money::format(123_456))->toBe('$1,234.56')
        ->and(Money::format(5, Currency::VES))->toBe('Bs. 0.05')
        ->and(Money::format(null))->toBe('$0.00');
});

it('parses user input into cents', function (mixed $input, int $expected) {
    expect(Money::toMinorUnits($input))->toBe($expected);
})->with([
    ['1,234.56', 123_456],
    ['12.5', 1_250],
    [12.345, 1_235],
    [0.1 + 0.2, 30],
    ['', 0],
]);

it('converts bolívares to dollars rounding half up', function () {
    // Bs. 3,650.00 at 36.50 Bs/USD = $100.00
    expect(Money::vesToUsd(365_000, '36.5000'))->toBe(10_000)
        // Bs. 100.00 at 36.50 = $2.7397... -> $2.74
        ->and(Money::vesToUsd(10_000, '36.5'))->toBe(274)
        ->and(Money::usdToVes(10_000, '36.5'))->toBe(365_000);
});

it('applies percentages rounding half up', function () {
    expect(Money::percentage(10_000, '10.00'))->toBe(1_000)
        ->and(Money::percentage(12_345, '1'))->toBe(123)
        ->and(Money::percentage(150, '1'))->toBe(2);
});

it('rejects a zero exchange rate', function () {
    Money::vesToUsd(100, '0');
})->throws(InvalidArgumentException::class);

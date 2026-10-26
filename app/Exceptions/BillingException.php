<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule prevented a billing or payment operation. The message is
 * safe to show to the user.
 */
class BillingException extends RuntimeException
{
    public static function periodAlreadyClosed(string $period): self
    {
        return new self("The billing period {$period} is already closed.");
    }

    public static function earlierPeriodOpen(string $period): self
    {
        return new self("Close {$period} first. Periods must be closed in order.");
    }

    public static function periodNotFinished(string $period): self
    {
        return new self("{$period} has not ended yet.");
    }

    public static function sharesIncomplete(string $total): self
    {
        return new self("Ownership shares add up to {$total}% instead of 100%. Fix the units before billing.");
    }

    public static function noUnits(): self
    {
        return new self('There are no units to bill.');
    }

    public static function buildingWithoutUnits(string $building): self
    {
        return new self("An expense is assigned to {$building}, which has no units.");
    }

    public static function missingExchangeRate(string $date): self
    {
        return new self("There is no exchange rate for {$date}. Ask the administrator to register it.");
    }

    public static function paymentAlreadyReviewed(): self
    {
        return new self('This payment has already been reviewed.');
    }

    public static function expenseAlreadyBilled(): self
    {
        return new self('This expense is already part of issued statements and can no longer change.');
    }

    public static function expenseAlreadyReviewed(): self
    {
        return new self('This expense has already been reviewed.');
    }
}

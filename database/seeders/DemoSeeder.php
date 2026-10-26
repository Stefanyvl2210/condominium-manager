<?php

namespace Database\Seeders;

use App\Actions\Billing\ApplyLateFees;
use App\Actions\Billing\CloseBillingPeriod;
use App\Actions\Expenses\RecordExpense;
use App\Actions\Expenses\ReviewExpense;
use App\Actions\Payments\ReviewPayment;
use App\Actions\Payments\SubmitPayment;
use App\Enums\Currency;
use App\Enums\ExchangeRateSource;
use App\Enums\ExpenseScope;
use App\Enums\PaymentMethod;
use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Condominium;
use App\Models\ExchangeRate;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\InvoiceIssued;
use App\Support\Money;
use App\Support\ShareAllocator;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Builds a realistic condominium with six months of history by replaying the
 * real business actions (record expenses, close periods, submit and approve
 * payments) while travelling through time. All data is fictional.
 */
class DemoSeeder extends Seeder
{
    private const MONTHS_OF_HISTORY = 6;

    private Carbon $today;

    private User $admin;

    private User $board;

    private User $demoOwner;

    /** @var array<string, ExpenseCategory> */
    private array $categories = [];

    /** @var array<string, Vendor> */
    private array $vendors = [];

    /** @var Collection<int, Building> */
    private Collection $buildings;

    /**
     * How each unit's owner behaves: on time, late, partial or not paying.
     *
     * @var array<int, string>
     */
    private array $payerProfiles = [];

    public function run(): void
    {
        config(['mail.default' => 'array']);
        mt_srand(2026);
        fake()->seed(2026);

        $today = $this->today = now()->startOfDay();
        $start = $today->copy()->subMonthsNoOverflow(self::MONTHS_OF_HISTORY)->startOfMonth();

        Carbon::setTestNow($start->copy()->subDays(10));

        $this->createCondominium();
        $this->createPeople();
        $this->createProperty();
        $this->createVendorsAndCategories();
        $this->createExchangeRates($start->copy()->subDays(10), $today);

        for ($month = $start->copy(); $month->lt($today->copy()->startOfMonth()); $month->addMonthNoOverflow()) {
            $this->simulateMonth($month->copy(), $today);
        }

        // Keep only the notifications about things still waiting for someone.
        DatabaseNotification::query()->delete();

        BillingPeriod::for($today->year, $today->month);
        $this->recordExpensesFor($today->copy()->startOfMonth(), $today);

        Carbon::setTestNow($today->copy()->setTime(9, 0));

        $this->createRecentActivity($today);
        $this->createAnnouncements($today);

        Carbon::setTestNow();
    }

    private function createCondominium(): void
    {
        Condominium::query()->delete();

        Condominium::factory()->create([
            'name' => 'Parkview Residences',
            'tax_id' => 'J-41234567-8',
            'address' => '1200 Parkview Avenue, Caracas',
            'email' => 'office@parkview.test',
            'phone' => '+58 212-555-0142',
            'reserve_fund_rate' => '10.00',
            'late_fee_rate' => '2.00',
            'payment_term_days' => 15,
            'expense_approval_threshold' => 150_000,
        ]);

        app()->forgetInstance(Condominium::class);
    }

    private function createPeople(): void
    {
        $this->admin = User::factory()->create([
            'name' => 'Diana Torres',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        $this->board = User::factory()->create([
            'name' => 'Carlos Rivera',
            'email' => 'board@example.com',
            'role' => UserRole::BoardMember,
        ]);

        User::factory()->create([
            'name' => 'Elena Park',
            'email' => 'board2@example.com',
            'role' => UserRole::BoardMember,
        ]);

        $this->demoOwner = User::factory()->create([
            'name' => 'Sofia Martin',
            'email' => 'owner@example.com',
        ]);
    }

    private function createProperty(): void
    {
        $this->buildings = collect([
            Building::query()->create(['name' => 'Tower A', 'code' => 'A', 'floors' => 6, 'description' => 'North tower, facing the park.']),
            Building::query()->create(['name' => 'Tower B', 'code' => 'B', 'floors' => 6, 'description' => 'South tower, next to the pool.']),
        ]);

        $layout = [];
        $areas = [1 => 118, 2 => 96, 3 => 84, 4 => 104];

        foreach ($this->buildings as $building) {
            if ($building->code === 'A') {
                $layout[] = [$building, 'L1', UnitType::Commercial, 0, 72];
                $layout[] = [$building, 'L2', UnitType::Commercial, 0, 58];
            }

            for ($floor = 1; $floor <= $building->floors; $floor++) {
                foreach ($areas as $position => $area) {
                    $layout[] = [$building, sprintf('%02d%02d', $floor, $position), UnitType::Apartment, $floor, $area];
                }
            }
        }

        // Shares proportional to the area, in millionths of a percent so they add up to exactly 100 %.
        $shares = ShareAllocator::allocate(100_000_000, array_map(fn (array $unit) => $unit[4], $layout));

        foreach ($layout as $index => [$building, $number, $type, $floor, $area]) {
            $owner = match ("{$building->code}-{$number}") {
                'A-0302' => $this->demoOwner,
                'A-0101' => $this->board,
                default => User::factory()->create(),
            };

            $unit = Unit::query()->create([
                'building_id' => $building->id,
                'owner_id' => $owner->id,
                'number' => $number,
                'type' => $type,
                'floor' => $floor,
                'area' => $area,
                'ownership_share' => number_format($shares[$index] / 1_000_000, 6, '.', ''),
            ]);

            $this->payerProfiles[$unit->id] = match (true) {
                $owner->is($this->demoOwner) => 'demo',
                $owner->is($this->board) => 'on_time',
                default => $this->randomProfile(),
            };
        }
    }

    private function randomProfile(): string
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 68 => 'on_time',
            $roll <= 84 => 'late',
            $roll <= 92 => 'partial',
            default => 'delinquent',
        };
    }

    private function createVendorsAndCategories(): void
    {
        foreach ([
            'utilities' => ['Utilities', 'info'],
            'maintenance' => ['Maintenance', 'primary'],
            'security' => ['Security', 'danger'],
            'cleaning' => ['Cleaning', 'success'],
            'administration' => ['Administration', 'gray'],
            'repairs' => ['Repairs', 'warning'],
        ] as $key => [$name, $color]) {
            $this->categories[$key] = ExpenseCategory::query()->create(['name' => $name, 'color' => $color]);
        }

        foreach ([
            'power' => ['CityPower Utility', 'Electricity'],
            'water' => ['Hydro Capital', 'Water'],
            'elevators' => ['LiftCare Elevators', 'Elevator maintenance'],
            'cleaning' => ['SparkleClean Services', 'Cleaning'],
            'security' => ['Guardian Security', 'Security'],
            'gardening' => ['GreenLeaf Gardening', 'Gardening'],
            'pool' => ['AquaBlue Pools', 'Pool maintenance'],
            'plumbing' => ['FixIt Plumbing', 'Plumbing'],
            'accounting' => ['Ledger & Co. Accountants', 'Accounting'],
        ] as $key => [$name, $service]) {
            $this->vendors[$key] = Vendor::factory()->create(['name' => $name, 'service' => $service]);
        }
    }

    private function createExchangeRates(Carbon $from, Carbon $until): void
    {
        $rate = 36.5;
        $rows = [];

        for ($day = $from->copy(); $day->lte($until); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $rate *= 1 + (mt_rand(5, 45) / 10_000);
            $rows[] = [
                'effective_on' => $day->toDateString(),
                'rate' => round($rate, 4),
                'source' => ExchangeRateSource::CentralBank->value,
                'created_at' => $day,
                'updated_at' => $day,
            ];
        }

        ExchangeRate::query()->insert($rows);
    }

    private function simulateMonth(Carbon $month, Carbon $today): void
    {
        $period = BillingPeriod::for($month->year, $month->month);

        $this->recordExpensesFor($month, $month->copy()->endOfMonth());

        $issuedOn = $month->copy()->addMonthNoOverflow()->startOfMonth();
        Carbon::setTestNow($issuedOn->copy()->setTime(8, 30));

        $invoices = app(CloseBillingPeriod::class)->handle($period, $this->admin, $issuedOn);

        $dueOn = $issuedOn->copy()->addDays(Condominium::current()->payment_term_days);
        $isLatestPeriod = $issuedOn->copy()->addMonthNoOverflow()->gt($today);

        foreach ($invoices as $invoice) {
            $profile = $this->payerProfiles[$invoice->unit_id];

            if (in_array($profile, ['on_time', 'partial'], true) || ($profile === 'demo' && ! $isLatestPeriod)) {
                $paidOn = $issuedOn->copy()->addDays(mt_rand(1, 13));
                $share = $profile === 'partial' ? mt_rand(40, 70) / 100 : 1;

                $this->payInvoice($invoice, $paidOn, $today, $share);
            }
        }

        if ($dueOn->lt($today)) {
            Carbon::setTestNow($dueOn->copy()->addDay()->setTime(1, 0));
            app(ApplyLateFees::class)->handle();
        }

        foreach ($invoices as $invoice) {
            $profile = $this->payerProfiles[$invoice->unit_id];
            $paidOn = $dueOn->copy()->addDays(mt_rand(3, 25));

            if ($profile === 'late' && $paidOn->lt($today)) {
                $this->payInvoice($invoice->fresh(), $paidOn, $today);
            }

            // Delinquent owners occasionally catch up on part of their debt.
            if ($profile === 'delinquent' && mt_rand(1, 4) === 1 && $paidOn->lt($today)) {
                $this->payInvoice($invoice->fresh(), $paidOn, $today, 0.5);
            }
        }
    }

    private function payInvoice(Invoice $invoice, Carbon $paidOn, Carbon $today, float $share = 1): void
    {
        if ($invoice->balance === 0 || $paidOn->gte($today)) {
            return;
        }

        Carbon::setTestNow($paidOn->copy()->setTime(mt_rand(8, 20), mt_rand(0, 59)));

        $usd = (int) round($invoice->balance * $share);
        $inBolivares = mt_rand(1, 100) <= 55;
        $owner = $invoice->unit->owner;

        $payment = app(SubmitPayment::class)->handle($owner, $invoice->unit, $inBolivares
            ? [
                'currency' => Currency::VES,
                'amount' => $this->bolivaresFor($usd, $paidOn),
                'method' => fake()->randomElement([PaymentMethod::MobilePayment, PaymentMethod::BankTransfer]),
                'reference' => fake()->numerify('0#########'),
                'paid_on' => $paidOn,
            ]
            : [
                'currency' => Currency::USD,
                'amount' => $usd,
                'method' => fake()->randomElement([PaymentMethod::Zelle, PaymentMethod::Zelle, PaymentMethod::Cash]),
                'reference' => fake()->bothify('ZL-#######'),
                'paid_on' => $paidOn,
            ]);

        Carbon::setTestNow($paidOn->copy()->addDays(mt_rand(0, 2))->setTime(17, 15));

        app(ReviewPayment::class)->approve($payment, $this->admin);
    }

    /**
     * Smallest bolívar amount that covers the dollar amount at the day's rate.
     */
    private function bolivaresFor(int $usdCents, Carbon $date): int
    {
        $rate = ExchangeRate::effectiveOn($date)->rate;
        $ves = Money::usdToVes($usdCents, $rate);

        while (Money::vesToUsd($ves, $rate) < $usdCents) {
            $ves++;
        }

        return $ves;
    }

    private function recordExpensesFor(Carbon $month, Carbon $until): void
    {
        $recurring = [
            [4, 'utilities', 'power', 'Common areas electricity', mt_rand(36_000, 46_000)],
            [6, 'utilities', 'water', 'Water service', mt_rand(21_000, 26_000)],
            [5, 'cleaning', 'cleaning', 'Cleaning staff and supplies', 62_000],
            [5, 'security', 'security', '24/7 security service', 124_000],
            [10, 'maintenance', 'gardening', 'Gardening', 15_000],
            [12, 'maintenance', 'pool', 'Pool maintenance', 21_000],
            [28, 'administration', 'accounting', 'Accounting and administration fee', 48_000],
        ];

        foreach ($recurring as [$day, $category, $vendor, $description, $amount]) {
            $this->expense($month->copy()->day($day), $until, $category, $vendor, "{$description} – {$month->format('F')}", $amount);
        }

        foreach ($this->buildings as $building) {
            $this->expense($month->copy()->day(15), $until, 'maintenance', 'elevators', "Elevator maintenance – {$building->name}", 18_500, ExpenseScope::Building, $building->id);
        }

        $oneOff = match ((int) $month->diffInMonths($this->today->copy()->startOfMonth(), absolute: true)) {
            5 => [[18, 'repairs', 'plumbing', 'Water pump repair', 64_000]],
            4 => [[9, 'repairs', null, 'Roof waterproofing – Tower B', 240_000, ExpenseScope::Building, 'B']],
            3 => [[21, 'repairs', 'plumbing', 'Leak repair in parking level', 38_500]],
            2 => [[14, 'repairs', null, 'Lobby door replaced after damage', 18_000, ExpenseScope::Unit, 'B-0503']],
            1 => [[7, 'maintenance', 'elevators', 'Elevator cable inspection – Tower A', 52_000, ExpenseScope::Building, 'A']],
            0 => [
                [8, 'repairs', 'elevators', 'Elevator motor replacement – Tower A', 380_000, ExpenseScope::Building, 'A'],
                [12, 'repairs', null, 'Intercom upgrade', 210_000],
            ],
            default => [],
        };

        foreach ($oneOff as $item) {
            [$day, $category, $vendor, $description, $amount] = $item;
            $scope = $item[5] ?? ExpenseScope::Condominium;
            $target = match ($scope) {
                ExpenseScope::Building => $this->buildings->firstWhere('code', $item[6])->id,
                ExpenseScope::Unit => Unit::query()->whereHas('building', fn ($q) => $q->where('code', str($item[6])->before('-')))->where('number', str($item[6])->after('-'))->value('id'),
                default => null,
            };

            $this->expense($month->copy()->day($day), $until, $category, $vendor, $description, $amount, $scope, $target);
        }
    }

    private function expense(Carbon $date, Carbon $until, string $category, ?string $vendor, string $description, int $amount, ExpenseScope $scope = ExpenseScope::Condominium, ?int $target = null): void
    {
        if ($date->gt($until)) {
            return;
        }

        Carbon::setTestNow($date->copy()->setTime(11, 0));

        $expense = app(RecordExpense::class)->handle($this->admin, [
            'expense_category_id' => $this->categories[$category]->id,
            'vendor_id' => $vendor ? $this->vendors[$vendor]->id : null,
            'scope' => $scope,
            'building_id' => $scope === ExpenseScope::Building ? $target : null,
            'unit_id' => $scope === ExpenseScope::Unit ? $target : null,
            'description' => $description,
            'amount' => $amount,
            'incurred_on' => $date,
            'invoice_number' => $vendor ? fake()->bothify('INV-####') : null,
            'paid_on' => $this->vendorPaidOn($date, $amount),
        ]);

        // The board signs off on large expenses from previous months; this month's stay pending for the demo.
        if ($expense->isPendingApproval() && $date->lt($this->today->copy()->startOfMonth())) {
            Carbon::setTestNow($date->copy()->addDays(2)->setTime(19, 0));
            app(ReviewExpense::class)->approve($expense, $this->board);
        }
    }

    /**
     * Vendors are paid a few days later; this month's large expenses wait for the board first.
     */
    private function vendorPaidOn(Carbon $date, int $amount): ?Carbon
    {
        $paidOn = $date->copy()->addDays(5);
        $awaitsBoard = Condominium::current()->requiresApproval($amount) && $date->gte($this->today->copy()->startOfMonth());

        return $paidOn->lt($this->today) && ! $awaitsBoard ? $paidOn : null;
    }

    /**
     * Pending items for the demo accounts to act on.
     */
    private function createRecentActivity(Carbon $today): void
    {
        Storage::disk('uploads')->makeDirectory('payments');

        $candidates = Invoice::query()
            ->outstanding()
            ->whereHas('unit', fn ($query) => $query->whereNotIn('owner_id', [$this->demoOwner->id, $this->board->id]))
            ->with('unit.owner')
            ->latest('issued_on')
            ->get()
            ->unique('unit_id')
            ->take(5);

        foreach ($candidates->values() as $index => $invoice) {
            $paidOn = $today->copy()->subDays($index + 1);
            $inBolivares = $index % 2 === 0;
            $reference = fake()->numerify('0#########');
            $amount = $inBolivares ? $this->bolivaresFor($invoice->balance, $paidOn) : $invoice->balance;
            $currency = $inBolivares ? Currency::VES : Currency::USD;

            Carbon::setTestNow($paidOn->copy()->setTime(19, 30));

            app(SubmitPayment::class)->handle($invoice->unit->owner, $invoice->unit, [
                'currency' => $currency,
                'amount' => $amount,
                'method' => $inBolivares ? PaymentMethod::MobilePayment : PaymentMethod::Zelle,
                'reference' => $reference,
                'paid_on' => $paidOn,
                'proof_path' => $this->fakeReceipt($reference, Money::format($amount, $currency), $paidOn),
            ]);
        }

        Carbon::setTestNow($today->copy()->setTime(9, 0));

        $latest = Invoice::query()->whereBelongsTo($this->demoOwner->units()->first())->latest('issued_on')->first();
        $this->demoOwner->notify(new InvoiceIssued($latest));
    }

    /**
     * A simple bank receipt image so the review screen has something to show.
     */
    private function fakeReceipt(string $reference, string $amount, Carbon $date): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(520, 300);
        $white = imagecolorallocate($image, 255, 255, 255);
        $brand = imagecolorallocate($image, 16, 120, 96);
        $dark = imagecolorallocate($image, 30, 41, 59);
        $muted = imagecolorallocate($image, 100, 116, 139);

        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 0, 0, 520, 56, $brand);
        imagestring($image, 5, 20, 20, 'DEMO BANK  -  Transfer receipt', $white);
        imagestring($image, 3, 20, 80, 'Status: Completed', $muted);
        imagestring($image, 5, 20, 110, "Amount: {$amount}", $dark);
        imagestring($image, 4, 20, 145, "Reference: {$reference}", $dark);
        imagestring($image, 4, 20, 175, 'Date: '.$date->toFormattedDateString(), $dark);
        imagestring($image, 3, 20, 215, 'Beneficiary: Parkview Residences', $muted);
        imagestring($image, 2, 20, 270, 'Fictional receipt generated for the demo.', $muted);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        $path = "payments/demo-receipt-{$reference}.png";
        Storage::disk('uploads')->put($path, $png);

        return $path;
    }

    private function createAnnouncements(Carbon $today): void
    {
        $announcements = [
            [
                'title' => 'Water service interruption this Saturday',
                'body' => '<p>The water utility will repair the main supply line on <strong>Saturday from 8:00 to 14:00</strong>. Please store enough water for the morning. The pool will remain closed until the afternoon.</p>',
                'is_pinned' => true,
                'published_at' => $today->copy()->subDays(2),
            ],
            [
                'title' => 'Annual owners\' meeting',
                'body' => '<p>The annual owners\' meeting will take place in the community hall. On the agenda:</p><ul><li>Approval of the yearly budget</li><li>Elevator modernization in Tower A</li><li>Election of the new board</li></ul><p>Owners who cannot attend may send a signed proxy.</p>',
                'is_pinned' => false,
                'published_at' => $today->copy()->subDays(6),
            ],
            [
                'title' => 'You can now report payments online',
                'body' => '<p>Report your transfers, mobile payments and Zelle payments directly from the owner portal. Attach a screenshot of the receipt and the administration will confirm it within two business days. Payments in bolívares are converted with the official exchange rate on the payment date.</p>',
                'is_pinned' => false,
                'published_at' => $today->copy()->subDays(20),
            ],
            [
                'title' => 'Pool maintenance schedule',
                'body' => '<p>The pool is cleaned every Monday morning. It reopens at 12:00.</p>',
                'is_pinned' => false,
                'published_at' => $today->copy()->subDays(45),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::query()->create([...$announcement, 'author_id' => $this->admin->id]);
        }
    }
}

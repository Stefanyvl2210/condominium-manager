# CLAUDE.md

Guidance for AI assistants working in this repository.

## Project

Condominium management app: a Filament back office (`/admin`) for
administrators and board members, and a Filament owner portal (`/portal`).
One condominium with several buildings; statements in USD, payments in USD or
VES. See `README.md` for the feature overview.

Stack: Laravel 13, Filament 5 (Livewire 4), PHP 8.3, MySQL 8 locally, SQLite
in-memory for tests, Pest 4, Larastan (level 6), Pint, Tailwind 4 via Vite.

## Hard rules

- **English only.** Code, identifiers, UI text, validation messages, comments,
  docs and commit messages. Domain terms: unit, ownership share,
  statement (the `Invoice` model), late fee, reserve fund, vendor.
- **Money is integer minor units.** Columns like `amount`, `total`,
  `paid_amount` and `amount_usd` are cents. Never use floats for money math;
  use `App\Support\Money` and `App\Support\ShareAllocator`. In Filament use
  `App\Filament\Forms\MoneyInput` for inputs and `->money('USD', divideBy: 100)`
  for display.
- **Business rules live in `app/Actions`.** Filament pages, the seeder and the
  tests call the same actions. Do not put billing logic in Filament classes or
  models. Rule violations throw `App\Exceptions\BillingException` with a message
  safe to show users.
- **Billing history is immutable.** Invoices are only created by
  `CloseBillingPeriod` and only changed by payment allocation and late fees.
  Billed expenses cannot be edited or deleted (`ExpensePolicy`).
- **User uploads use the `uploads` disk**, never `local` or `public`. Locally it
  points to `storage/app/private/uploads`; on Laravel Cloud a private bucket
  named `uploads` replaces it. Generate links with `temporaryUrl()`. Deployment
  details are in `docs/deployment.md`.
- **Authorization goes in policies** (`app/Policies`). Board members are
  read-only except `ExpensePolicy::review`. Owner portal resources must scope
  `getEloquentQuery()` to the signed-in owner.

## Domain rules

- Ownership shares are percentages with 6 decimals and must add up to exactly
  100 before a period can be closed.
- Expense scope: `condominium` (all units by share), `building` (that
  building's units by relative share), `unit` (charged in full).
- Reserve fund = `reserve_fund_rate` % of each unit's common (condominium +
  building) charges. Unit charges do not carry reserve fund.
- Expenses with `amount >= expense_approval_threshold` start as
  `pending_approval`; only board members can approve them.
- Periods close in order, only after the month ends. Due date = issue date +
  `payment_term_days`.
- VES payments are converted with `ExchangeRate::effectiveOn($paidOn)`; the rate
  is stored on the payment.
- Approved payments are allocated oldest statement first (`ApplyUnitCredit`);
  leftovers stay as unit credit and are applied when the next period closes.
- Late fee: once per invoice, `late_fee_rate` % of the balance when it becomes
  overdue (`billing:apply-late-fees`, scheduled daily).

## Layout

```
app/Actions/{Billing,Payments,Expenses}  business rules
app/Enums                                 statuses and types (implement Filament HasLabel/HasColor)
app/Filament/Resources, Pages, Widgets    back office panel
app/Filament/Portal/...                   owner portal panel
app/Models (+ Builders/InvoiceBuilder)    Eloquent models, typed query builder for invoices
app/Policies                              per-model authorization
app/Reports/DelinquencyReport             aging buckets in SQL
app/Support/{Money,ShareAllocator}        integer money helpers
database/seeders/DemoSeeder               replays six months of activity through the actions
resources/views/pdf/invoice.blade.php     DomPDF statement
```

## Commands

PHP 8.3 is required. On this machine (Laragon) the binaries are not on PATH:

```bash
export PATH="/c/laragon/bin/php/php-8.3.36-Win32-vs16-x64:/c/laragon/bin/composer:/c/laragon/bin/nodejs/node-v22:$PATH"
```

| Task | Command |
|---|---|
| Tests | `php vendor/bin/pest` (add `--parallel` for speed) |
| Style | `php vendor/bin/pint` |
| Static analysis | `php vendor/bin/phpstan analyse --memory-limit=2G` |
| Everything CI runs | `composer check` |
| Reset demo data | `php artisan migrate:fresh --seed` (about 45 s) |
| Frontend build | `npm run build` (needed after adding Tailwind classes in Blade views) |
| Dev server | `php artisan serve` |

Laravel Pao rewrites tool output as JSON for agents; set `PAO_DISABLE=1` to see
the normal output.

## Conventions

- Follow the existing Filament layout: one directory per resource with
  `Schemas/`, `Tables/` and `Pages/`. Shared actions live next to the resource
  (`ExpenseReviewActions`, `PaymentReviewActions`, `CloseBillingPeriodAction`).
- Models use the `#[Fillable]` attribute and a `casts()` method. Add
  `@property-read` docs for aggregate columns (`withSum`) used in code.
- Every new rule gets a Pest test; every new page gets a smoke test in
  `tests/Feature/Panels`. Use the `condominium()` and `unitsWithShares()`
  helpers from `tests/Pest.php`.
- Keep Larastan at level 6 with no baseline and no `@phpstan-ignore`.

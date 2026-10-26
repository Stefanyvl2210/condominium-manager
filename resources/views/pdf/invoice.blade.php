@php
    use App\Enums\Currency;
    use App\Enums\InvoiceStatus;
    use App\Support\Money;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement {{ $invoice->number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0; color: #312e81; }
        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin: 18px 0 6px; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .header td { vertical-align: top; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-partially_paid { background: #fef3c7; color: #92400e; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        .lines th { text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
        .lines td { border-bottom: 1px solid #e2e8f0; padding: 6px 4px; }
        .totals td { padding: 4px; }
        .totals .grand td { font-size: 13px; font-weight: bold; border-top: 2px solid #312e81; padding-top: 8px; }
        .box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; }
        .footer { margin-top: 28px; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $condominium->name }}</h1>
                <div class="muted">
                    @if ($condominium->tax_id) Tax ID {{ $condominium->tax_id }}<br>@endif
                    @if ($condominium->address) {{ $condominium->address }}<br>@endif
                    {{ collect([$condominium->email, $condominium->phone])->filter()->implode(' · ') }}
                </div>
            </td>
            <td class="right">
                <div style="font-size: 14px; font-weight: bold;">Monthly statement</div>
                <div>{{ $invoice->number }}</div>
                <div style="margin-top: 6px;"><span class="badge badge-{{ $invoice->status->value }}">{{ $invoice->status->getLabel() }}</span></div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 18px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="box">
                    <div class="muted">Billed to</div>
                    <strong>{{ $invoice->unit->owner?->name ?? 'Owner not assigned' }}</strong><br>
                    Unit {{ $invoice->unit->label }} · {{ $invoice->unit->building->name }}<br>
                    Ownership share {{ rtrim(rtrim($invoice->unit->ownership_share, '0'), '.') }} %
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="box">
                    <table>
                        <tr><td class="muted">Period</td><td class="right">{{ $invoice->billingPeriod->name }}</td></tr>
                        <tr><td class="muted">Issued</td><td class="right">{{ $invoice->issued_on->toFormattedDateString() }}</td></tr>
                        <tr><td class="muted">Due</td><td class="right"><strong>{{ $invoice->due_on->toFormattedDateString() }}</strong></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <h2>Charges</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th class="right">Expense total</th>
                <th class="right">Your share</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="muted">{{ $line->type->getLabel() }}</td>
                    <td class="right muted">{{ $line->expense_total ? Money::format($line->expense_total) : '' }}</td>
                    <td class="right">{{ Money::format($line->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 10px;">
        <tr>
            <td style="width: 55%;"></td>
            <td class="muted">Total</td>
            <td class="right">{{ Money::format($invoice->total) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="muted">Paid</td>
            <td class="right">− {{ Money::format($invoice->paid_amount) }}</td>
        </tr>
        <tr class="grand">
            <td></td>
            <td>Balance due</td>
            <td class="right">{{ Money::format($invoice->balance) }}</td>
        </tr>
        @if ($rate && $invoice->balance > 0)
            <tr>
                <td></td>
                <td class="muted" colspan="2" style="text-align: right;">
                    {{ Money::format(Money::usdToVes($invoice->balance, $rate->rate), Currency::VES) }}
                    at Bs. {{ number_format((float) $rate->rate, 2) }} per USD ({{ $rate->effective_on->toFormattedDateString() }})
                </td>
            </tr>
        @endif
    </table>

    @if ($invoice->allocations->isNotEmpty())
        <h2>Payments applied</h2>
        <table class="lines">
            @foreach ($invoice->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->payment->paid_on->toFormattedDateString() }}</td>
                    <td class="muted">{{ $allocation->payment->method->getLabel() }} {{ $allocation->payment->reference }}</td>
                    <td class="right">{{ Money::format($allocation->amount) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="footer">
        Amounts are in US dollars. Payments in bolívares are converted with the official exchange rate on the payment date.
        @if ($invoice->status !== InvoiceStatus::Paid)
            A late fee of {{ $condominium->late_fee_rate }} % of the outstanding balance is charged after the due date.
        @endif
        Report your payment in the owner portal at {{ url('/portal') }}.
    </div>
</body>
</html>

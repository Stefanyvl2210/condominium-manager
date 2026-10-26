<?php

namespace App\Http\Controllers;

use App\Models\Condominium;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['unit.building', 'unit.owner', 'billingPeriod', 'lines', 'allocations.payment']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'condominium' => Condominium::current(),
            'rate' => ExchangeRate::latest(),
        ])
            ->setPaper('letter')
            ->setOption('enable_font_subsetting', true)
            ->stream("{$invoice->number}.pdf");
    }
}

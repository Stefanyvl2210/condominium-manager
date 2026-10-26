<?php

namespace App\Models;

use App\Enums\InvoiceLineType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'expense_id', 'type', 'description', 'expense_total', 'amount'])]
class InvoiceLine extends Model
{
    protected function casts(): array
    {
        return [
            'type' => InvoiceLineType::class,
            'expense_total' => 'integer',
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}

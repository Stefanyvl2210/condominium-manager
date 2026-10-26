<?php

use App\Models\BillingPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BillingPeriod::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Unit::class)->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('issued_on');
            $table->date('due_on')->index();
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status')->index();
            $table->timestamp('late_fee_applied_at')->nullable();
            $table->timestamps();

            $table->unique(['billing_period_id', 'unit_id']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Invoice::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Expense::class)->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('description');
            // Full amount of the source expense, shown next to the unit's portion.
            $table->unsignedBigInteger('expense_total')->nullable();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};

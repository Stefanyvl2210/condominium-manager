<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('effective_on')->unique();
            // Bolívares (VES) per US dollar.
            $table->decimal('rate', 18, 4);
            $table->string('source')->default('manual');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Unit::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class, 'submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('currency', 3);
            // Amount in minor units of `currency`.
            $table->unsignedBigInteger('amount');
            $table->decimal('exchange_rate', 18, 4)->nullable();
            // Amount converted to USD cents, used for all balances.
            $table->unsignedBigInteger('amount_usd');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->date('paid_on');
            $table->string('proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->foreignIdFor(User::class, 'reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payment::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Invoice::class)->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('exchange_rates');
    }
};

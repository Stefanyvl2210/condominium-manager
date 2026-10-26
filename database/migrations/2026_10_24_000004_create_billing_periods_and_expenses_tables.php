<?php

use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\ExpenseCategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status')->default('open')->index();
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            // Snapshots of the settings used when the period was closed.
            $table->decimal('reserve_fund_rate', 5, 2)->nullable();
            $table->decimal('exchange_rate', 18, 4)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ExpenseCategory::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Vendor::class)->nullable()->constrained()->nullOnDelete();
            $table->string('scope');
            $table->foreignIdFor(Building::class)->nullable()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Unit::class)->nullable()->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->date('incurred_on')->index();
            $table->string('invoice_number')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->index();
            $table->foreignIdFor(User::class, 'reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignIdFor(BillingPeriod::class)->nullable()->constrained()->nullOnDelete();
            $table->date('paid_on')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('billing_periods');
    }
};

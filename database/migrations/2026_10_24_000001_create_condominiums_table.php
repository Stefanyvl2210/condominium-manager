<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominiums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('reserve_fund_rate', 5, 2)->default(10);
            $table->decimal('late_fee_rate', 5, 2)->default(1);
            $table->unsignedTinyInteger('payment_term_days')->default(15);
            $table->unsignedBigInteger('expense_approval_threshold')->default(100000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominiums');
    }
};

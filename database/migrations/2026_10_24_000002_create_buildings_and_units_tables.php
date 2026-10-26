<?php

use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Building::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class, 'owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 20);
            $table->string('type');
            $table->smallInteger('floor')->nullable();
            $table->decimal('area', 8, 2)->nullable();
            // Percentage of the whole condominium, e.g. 2.153846 means 2.153846 %.
            $table->decimal('ownership_share', 9, 6);
            $table->timestamps();

            $table->unique(['building_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
        Schema::dropIfExists('buildings');
    }
};

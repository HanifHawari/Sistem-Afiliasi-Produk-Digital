<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete(); // 1 order = maks. 1 komisi
            $table->foreignId('affiliate_id')->constrained('users')->restrictOnDelete();
            $table->decimal('rate', 5, 2); // persen saat transaksi terjadi
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending')->index(); // App\Enums\CommissionStatus
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};

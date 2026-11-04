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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            // Salinan data rekening saat pengajuan, supaya tidak berubah jika profil diedit
            $table->string('bank_name');
            $table->string('account_number', 50);
            $table->string('account_holder');
            $table->string('status', 20)->default('pending')->index(); // App\Enums\WithdrawalStatus
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('transfer_proof')->nullable(); // disk local (privat)
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};

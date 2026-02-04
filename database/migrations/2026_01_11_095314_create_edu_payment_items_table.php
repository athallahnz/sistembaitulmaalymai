<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_payment_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('edu_payment_id');
            $table->foreign('edu_payment_id')
                ->references('id')
                ->on('edu_payments')
                ->cascadeOnDelete();

            /**
             * bill_type:
             * - spp => bill_id mengarah ke tagihan_spps.id
             * - pmb => bill_id mengarah ke student_costs.id
             */
            $table->enum('bill_type', ['spp', 'pmb']);
            $table->unsignedBigInteger('bill_id');

            // jumlah alokasi ke bill tsb
            $table->unsignedBigInteger('amount');

            $table->timestamps();

            // index untuk performa
            $table->index(['bill_type', 'bill_id']);
            $table->index(['edu_payment_id']);

            // cegah dobel item bill yang sama dalam 1 pembayaran
            $table->unique(['edu_payment_id', 'bill_type', 'bill_id'], 'uniq_payment_bill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_payment_items');
    }
};

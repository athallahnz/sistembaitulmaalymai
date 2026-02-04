<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_infaq_kemasjidans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('infaq_kemasjidans', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal');
            $table->unsignedSmallInteger('tahun')->index();
            $table->unsignedTinyInteger('bulan')->index(); // 1-12

            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('metode_bayar', 50)->nullable(); // tunai/transfer
            $table->string('sumber', 100)->nullable(); // opsional: kotak amal, transfer jamaah, event, dll
            $table->string('nama_donatur', 120)->nullable(); // opsional
            $table->string('no_hp', 50)->nullable(); // opsional
            $table->text('keterangan')->nullable();

            // untuk audit & integrasi jurnal
            $table->unsignedBigInteger('akun_debit_id')->nullable();
            $table->unsignedBigInteger('akun_kredit_id')->nullable();
            $table->string('kode_transaksi', 100)->nullable()->index();

            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            // FK opsional (aktifkan kalau tabel akun_keuangans & users stabil)
            $table->foreign('akun_debit_id')->references('id')->on('akun_keuangans')->nullOnDelete();
            $table->foreign('akun_kredit_id')->references('id')->on('akun_keuangans')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infaq_kemasjidans');
    }
};


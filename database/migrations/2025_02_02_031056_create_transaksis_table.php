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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique(); // Kode unik untuk transaksi
            $table->date('tanggal_transaksi'); // Tanggal transaksi
            $table->enum('type', ['penerimaan', 'pengeluaran'])->after('tanggal_transaksi');
            $table->foreignId('akun_keuangan_id')->constrained('akun_keuangans')->onDelete('cascade'); // Menghubungkan dengan akun_keuangans
            $table->unsignedBigInteger('parent_id')->nullable()->after('akun_keuangan_id');
            $table->text('deskripsi'); // Deskripsi transaksi
            $table->decimal('amount', 15, 2);
            $table->string('bidang_name')->nullable();  // Menambahkan kolom bidang_name
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('akun_keuangans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

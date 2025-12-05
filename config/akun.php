<?php

return [
    // GROUP AKUN
    'group_kas' => 101,
    'group_bank' => 102,
    'group_piutang' => 103,                  // ← wajib!
    'group_aset_tetap' => 104,
    'group_inventaris' => 105,
    'group_pendapatan' => 201,
    'group_beban' => 301,

    // PIUTANG
    'piutang_spp' => 1031,   // Piutang SPP
    'piutang_pmb' => 1032,   // Piutang PMB
    'inventaris_kendaraan' => 1051, // Inventaris Kendaraan

    // Piutang/Hutang perantara (internal Bidang <-> Bendahara)
    'piutang_perantara' => 1034,
    'hutang_perantara' => 50016,

    // PENDAPATAN BELUM DITERIMA (KEWAJIBAN / LIABILITY)
    'pendapatan_belum_diterima_spp' => 50011, // opsional kalau mau pisah
    'pendapatan_belum_diterima_pmb' => 50012, // opsional

    // KAS/BANK PENDIDIKAN
    'kas_pendidikan' => 1013,
    'bank_pendidikan' => 1023,

    // PENDAPATAN (REVENUE)
    // PMB
    'pendapatan_pmb' => 201,                     // Pendapatan PMB
    'pendapatan_sdp' => 2011,                    // Pendapatan SDP
    'pendapatan_daftar_ulang' => 2012,           // Pendapatan Daftar Ulang
    'pendapatan_dana_seragam' => 2013,           // Pendapatan Dana Seragam
    'pendapatan_dana_peralatan' => 2014,         // Pendapatan Dana Peralatan
    'pendapatan_dana_kegiatan_ekstra' => 2015,   // Pendapatan Dana Kegiatan Ekstra
    'pendapatan_dana_kegiatan_siswa' => 2016,    // Pendapatan Dana Kegiatan Siswa
    // SPP
    'pendapatan_spp' => 202,                 // Pendapatan SPP
    'pendapatan_spp_kb_tk' => 2021,              // Pendapatan SPP KB/TK
    'pendapatan_spp_daycare' => 2022,            // Pendapatan SPP Daycare

];

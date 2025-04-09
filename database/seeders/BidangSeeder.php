<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BidangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bidangs = [
            ['name' => 'Kemasjidan', 'description' => 'Bidang kegiatan masjid'],
            ['name' => 'Pendidikan', 'description' => 'Bidang pendidikan dan keilmuan'],
            ['name' => 'Sosial', 'description' => 'Bidang kegiatan sosial'],
            ['name' => 'Usaha', 'description' => 'Bidang usaha dan ekonomi'],
            ['name' => 'Pembangunan', 'description' => 'Bidang pembangunan fasilitas'],
        ];

        DB::table('bidangs')->insert($bidangs);
    }
}

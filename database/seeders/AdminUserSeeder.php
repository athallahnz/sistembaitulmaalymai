<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Athallah Naufal Zuhdi',
            'email' => 'athallahnz57@gmail.com',
            'nomor' => '085231161434',
            'pin' => bcrypt('200902'), // gunakan bcrypt agar aman
            'role' => 'Admin',
            'bidang_name' => 'Kemasjidan',
            'is_active' => true,
        ])->assignRole('Admin'); // kalau kamu pakai Spatie role
    }
}

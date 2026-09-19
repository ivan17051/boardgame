<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'id_toko' => 0,
                'nama' => 'Administrator',
                'password' => '$2y$10$f.syDduEebtyaGWaAEpJRuvXg/I2t1sy7cJKfHihakTnqxJ7NgRuy',
                'remember_token' => '',
                'role' => 'admin',
                'is_active' => true,
                'is_hidden' => false,
                'doc' => '2026-05-05 15:53:25',
                'dom' => '2026-05-10 04:46:40',
            ]
        );
    }
}

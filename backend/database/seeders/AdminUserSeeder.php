<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('code', 'SUPER_ADMIN')
            ->firstOrFail();

        $admin = User::updateOrCreate(
            [
                'email' => 'admin@sikomando.test',
            ],
            [
                'name' => 'Administrator SIKOMANDO',
                'password' => Hash::make('Password'),
                'is_active' => true,
            ]
        );

        $admin->roles()->syncWithoutDetaching([
            $superAdminRole->id,
        ]);

        $this->command?->info(
            'Admin berhasil dibuat: admin@sikomando.test'
        );

        $this->command?->warn(
            'Password awal: Password — segera ganti pada implementasi autentikasi.'
        );
    }
}
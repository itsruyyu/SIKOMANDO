<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveUserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'code');

        $users = [
            [
                'email' => 'admin@sikomando.test',
                'name' => 'Administrator SIKOMANDO',
                'roles' => ['SUPER_ADMIN', 'ADMIN_SIKOMANDO'],
            ],
            [
                'email' => 'stefanus@sikomando.test',
                'name' => 'STEFANUS MONGKAREN',
                'roles' => ['SUPER_ADMIN', 'ADMIN_SIKOMANDO'],
            ],
            [
                'email' => 'verifikator@sikomando.test',
                'name' => 'Hendra Wijaya, S.STP',
                'roles' => ['VERIFIKATOR'],
            ],
            [
                'email' => 'evaluator@sikomando.test',
                'name' => 'Dr. Maya Pangemanan, M.Si',
                'roles' => ['EVALUATOR'],
            ],
            [
                'email' => 'surveyor@sikomando.test',
                'name' => 'Rendy Runtuwene, S.T.',
                'roles' => ['SURVEYOR'],
            ],
            [
                'email' => 'approver@sikomando.test',
                'name' => 'Mayjen TNI (Purn.) Yulius Selvanus Komaling, S.E',
                'roles' => ['APPROVER'],
            ],
            [
                'email' => 'auditor@sikomando.test',
                'name' => 'Grace Lumintang, S.E., Ak.',
                'roles' => ['AUDITOR'],
            ],
            [
                'email' => 'pemohon@sikomando.test',
                'name' => 'Michael Karundeng',
                'roles' => ['PEMOHON'],
            ],
            [
                'email' => 'pemohon2@sikomando.test',
                'name' => 'Maria Walewangko',
                'roles' => ['PEMOHON'],
            ],
            [
                'email' => 'pemohon3@sikomando.test',
                'name' => 'Ferry Sumendap',
                'roles' => ['PEMOHON'],
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('Password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $roleIds = [];
            foreach ($u['roles'] as $rCode) {
                if (isset($roles[$rCode])) {
                    $roleIds[] = $roles[$rCode];
                }
            }

            $user->roles()->sync($roleIds);
        }

        $this->command?->info('Comprehensive users with all RBAC roles seeded.');
    }
}


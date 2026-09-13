<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'proposal.viewAny',
            'proposal.view',
            'proposal.create',
            'proposal.update',
            'proposal.submit',
            'proposal.verify',
            'proposal.evaluate',
            'proposal.survey',
            'proposal.recommend',
            'proposal.approve',
            'proposal.reject',
            'proposal.disburse',
            'proposal.monitor',
            'proposal.lpj.verify',
            'user.manage',
            'role.manage',
            'master-data.manage',
            'audit.view',
            'report.view',
            'public-data.manage',
        ];

        foreach ($permissions as $code) {
            [$module, $action] = array_pad(
                explode('.', $code, 2),
                2,
                'access'
            );

            Permission::updateOrCreate(
                ['code' => $code],
                [
                    'name' => ucwords(str_replace(['.', '_'], ' ', $code)),
                    'module' => $module,
                    'description' => 'Permission ' . $code,
                ]
            );
        }

        $roles = [
            [
                'code' => 'SUPER_ADMIN',
                'name' => 'Super Administrator',
                'description' => 'Akses penuh terhadap seluruh sistem.',
            ],
            [
                'code' => 'ADMIN_SIKOMANDO',
                'name' => 'Administrator SIKOMANDO',
                'description' => 'Administrator operasional aplikasi.',
            ],
            [
                'code' => 'PEMOHON',
                'name' => 'Pemohon',
                'description' => 'Pengguna yang mengajukan proposal hibah.',
            ],
            [
                'code' => 'VERIFIKATOR',
                'name' => 'Verifikator',
                'description' => 'Petugas verifikasi proposal.',
            ],
            [
                'code' => 'EVALUATOR',
                'name' => 'Evaluator',
                'description' => 'Petugas evaluasi proposal.',
            ],
            [
                'code' => 'SURVEYOR',
                'name' => 'Surveyor',
                'description' => 'Petugas survei lapangan.',
            ],
            [
                'code' => 'APPROVER',
                'name' => 'Approver',
                'description' => 'Pejabat/petugas persetujuan proposal.',
            ],
            [
                'code' => 'AUDITOR',
                'name' => 'Auditor',
                'description' => 'Petugas pemeriksaan dan audit.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['code' => $roleData['code']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                ]
            );
        }

        $rolePermissions = [
            'SUPER_ADMIN' => $permissions,

            'ADMIN_SIKOMANDO' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.verify',
                'proposal.monitor',
                'user.manage',
                'master-data.manage',
                'report.view',
                'public-data.manage',
            ],

            'PEMOHON' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.create',
                'proposal.update',
                'proposal.submit',
            ],

            'VERIFIKATOR' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.verify',
                'proposal.reject',
            ],

            'EVALUATOR' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.evaluate',
            ],

            'SURVEYOR' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.survey',
            ],

            'APPROVER' => [
                'proposal.viewAny',
                'proposal.view',
                'proposal.recommend',
                'proposal.approve',
                'proposal.reject',
            ],

            'AUDITOR' => [
                'proposal.viewAny',
                'proposal.view',
                'audit.view',
                'report.view',
            ],
        ];

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $role = Role::where('code', $roleCode)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('code', $permissionCodes)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
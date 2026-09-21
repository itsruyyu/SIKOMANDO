<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! env('ALLOW_PRODUCTION_SEED', false)) {
            throw new RuntimeException(
                'DatabaseSeeder penuh dinonaktifkan di environment production untuk mencegah penulisan akun demo. Gunakan seeder master data atau command sikomando:create-admin.'
            );
        }

        $this->call([
            RolePermissionSeeder::class,
            RegionalSeeder::class,
            ComprehensiveUserSeeder::class,
            MasterDataSeeder::class,
            PolicyConfigurationSeeder::class,
            GrantProgramSeeder::class,
            AnnouncementSeeder::class,
            OrganizationSeeder::class,
            ProposalLifecycleSeeder::class,
            WorkflowEvidenceSeeder::class,
            TraceabilityAndSignatureSeeder::class,
            DisbursementAndRealizationSeeder::class,
            LpjAndMonitoringSeeder::class,
            AuditAndNotificationSeeder::class,
        ]);
    }
}

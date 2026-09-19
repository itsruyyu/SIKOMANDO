<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

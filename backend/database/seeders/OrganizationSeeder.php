<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $province = DB::table('provinces')->where('code', '71')->first();
        $manado = DB::table('regencies')->where('code', '7171')->first();
        $wenang = DB::table('districts')->where('code', '717101')->first();
        $sario = DB::table('districts')->where('code', '717102')->first();
        $tikala = DB::table('districts')->where('code', '717104')->first();

        $admin = User::where('email', 'admin@sikomando.test')->first();
        $pemohon1 = User::where('email', 'pemohon@sikomando.test')->first();
        $pemohon2 = User::where('email', 'pemohon2@sikomando.test')->first();
        $pemohon3 = User::where('email', 'pemohon3@sikomando.test')->first();

        $legalityDocType = DocumentType::where('code', 'LEGALITY_DOCUMENT')->first();

        $orgs = [
            [
                'code' => 'ORG-YPB-001',
                'name' => 'Yayasan Torang Bisa Sulawesi Utara',
                'organization_type' => 'YAYASAN',
                'description' => 'Yayasan pemberdayaan pemuda, pendidikan vokasi, dan literasi digital di Sulawesi Utara.',
                'phone' => '0431-882910',
                'email' => 'sekretariat@torangbisa.or.id',
                'address' => 'Jl. Sam Ratulangi No. 45, Wenang',
                'province_id' => $province?->id,
                'regency_id' => $manado?->id,
                'district_id' => $wenang?->id,
                'postal_code' => '95111',
                'legal_status' => 'TERVERIFIKASI',
                'registration_number' => 'AHU-0012849.AH.01.04.2021',
                'user' => $pemohon1,
            ],
            [
                'code' => 'ORG-LPMP-002',
                'name' => 'Lembaga Pemberdayaan Pesisir Bunaken Manado',
                'organization_type' => 'LEMBAGA_MASYARAKAT',
                'description' => 'Organisasi pelestarian terumbu karang dan pemberdayaan ekonomi nelayan tradisional Teluk Manado.',
                'phone' => '0431-855123',
                'email' => 'info@pesisirbunaken.org',
                'address' => 'Jl. Piere Tendean Boulevard No. 88, Sario',
                'province_id' => $province?->id,
                'regency_id' => $manado?->id,
                'district_id' => $sario?->id,
                'postal_code' => '95114',
                'legal_status' => 'TERVERIFIKASI',
                'registration_number' => 'AHU-0008472.AH.01.07.2019',
                'user' => $pemohon2,
            ],
            [
                'code' => 'ORG-SSB-003',
                'name' => 'Sanggar Seni & Budaya Minahasa Raya',
                'organization_type' => 'KOMUNITAS_BUDAYA',
                'description' => 'Komunitas pelestarian alat musik Kolintang, tarian Kabasaran, dan seni budaya Minahasa.',
                'phone' => '0431-863901',
                'email' => 'budaya@minahasaraya.id',
                'address' => 'Jl. BW Lapian No. 12, Tikala',
                'province_id' => $province?->id,
                'regency_id' => $manado?->id,
                'district_id' => $tikala?->id,
                'postal_code' => '95125',
                'legal_status' => 'TERVERIFIKASI',
                'registration_number' => 'SK-DISBUD-71/2020',
                'user' => $pemohon3,
            ],
            [
                'code' => 'ORG-YAI-004',
                'name' => 'Yayasan Bina Insan Harapan Manado',
                'organization_type' => 'YAYASAN_PENDIDIKAN',
                'description' => 'Lembaga pendidikan keagamaan dan pembinaan karakter anak asuh di Sulawesi Utara.',
                'phone' => '0431-871234',
                'email' => 'kontak@binainsanharapan.or.id',
                'address' => 'Jl. Ring Road I No. 102, Malalayang',
                'province_id' => $province?->id,
                'regency_id' => $manado?->id,
                'district_id' => $wenang?->id,
                'postal_code' => '95162',
                'legal_status' => 'TERVERIFIKASI',
                'registration_number' => 'AHU-0034921.AH.01.04.2022',
                'user' => $pemohon1,
            ],
        ];

        foreach ($orgs as $o) {
            $user = $o['user'];
            unset($o['user']);

            $org = Organization::updateOrCreate(
                ['code' => $o['code']],
                array_merge($o, [
                    'is_active' => true,
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ])
            );

            // Connect user in pivot
            if ($user) {
                DB::table('organization_user')->updateOrInsert(
                    [
                        'organization_id' => $org->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'membership_role' => 'chairperson',
                        'is_primary_contact' => true,
                        'is_active' => true,
                        'joined_at' => now()->subYears(2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Seed sample organization legality document
            if ($legalityDocType) {
                OrganizationDocument::updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'document_type_id' => $legalityDocType->id,
                    ],
                    [
                        'document_number' => $org->registration_number,
                        'original_filename' => 'Salinan_SK_Kemenkumham_Resmi.pdf',
                        'stored_filename' => 'SK_Kemenkumham_'.$org->code.'.pdf',
                        'storage_disk' => 'local',
                        'storage_path' => 'organizations/'.$org->id.'/legality.pdf',
                        'file_size' => 1024 * 512,
                        'mime_type' => 'application/pdf',
                        'status' => 'verified',
                        'uploaded_by' => $user?->id ?? $admin?->id,
                        'uploaded_at' => now()->subDays(30),
                        'verified_at' => now()->subDays(25),
                        'verified_by' => $admin?->id,
                    ]
                );
            }
        }

        $this->command?->info('Organizations and legality documents seeded.');
    }
}


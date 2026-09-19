<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegionalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Provinsi Sulawesi Utara
        $province = DB::table('provinces')->where('code', '71')->first();
        if (! $province) {
            $provinceId = (string) Str::uuid();
            DB::table('provinces')->insert([
                'id' => $provinceId,
                'code' => '71',
                'name' => 'Sulawesi Utara',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $provinceId = $province->id;
            DB::table('provinces')->where('id', $provinceId)->update([
                'name' => 'Sulawesi Utara',
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        // 2. Kabupaten / Kota di Sulawesi Utara
        $regencies = [
            ['code' => '7171', 'name' => 'Kota Manado'],
            ['code' => '7172', 'name' => 'Kota Bitung'],
            ['code' => '7173', 'name' => 'Kota Tomohon'],
            ['code' => '7102', 'name' => 'Kabupaten Minahasa'],
            ['code' => '7106', 'name' => 'Kabupaten Minahasa Utara'],
        ];

        foreach ($regencies as $reg) {
            $existing = DB::table('regencies')->where('code', $reg['code'])->first();
            if (! $existing) {
                DB::table('regencies')->insert([
                    'id' => (string) Str::uuid(),
                    'province_id' => $provinceId,
                    'code' => $reg['code'],
                    'name' => $reg['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('regencies')->where('id', $existing->id)->update([
                    'province_id' => $provinceId,
                    'name' => $reg['name'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }
        }

        $manado = DB::table('regencies')->where('code', '7171')->first();
        $minut = DB::table('regencies')->where('code', '7106')->first();

        // 3. Kecamatan
        $districts = [
            ['regency_id' => $manado->id, 'code' => '717101', 'name' => 'Kecamatan Wenang'],
            ['regency_id' => $manado->id, 'code' => '717102', 'name' => 'Kecamatan Sario'],
            ['regency_id' => $manado->id, 'code' => '717103', 'name' => 'Kecamatan Malalayang'],
            ['regency_id' => $manado->id, 'code' => '717104', 'name' => 'Kecamatan Tikala'],
            ['regency_id' => $minut->id, 'code' => '710601', 'name' => 'Kecamatan Airmadidi'],
        ];

        foreach ($districts as $dist) {
            $existing = DB::table('districts')->where('code', $dist['code'])->first();
            if (! $existing) {
                DB::table('districts')->insert([
                    'id' => (string) Str::uuid(),
                    'regency_id' => $dist['regency_id'],
                    'code' => $dist['code'],
                    'name' => $dist['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('districts')->where('id', $existing->id)->update([
                    'regency_id' => $dist['regency_id'],
                    'name' => $dist['name'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }
        }

        $wenang = DB::table('districts')->where('code', '717101')->first();
        $sario = DB::table('districts')->where('code', '717102')->first();
        $tikala = DB::table('districts')->where('code', '717104')->first();

        // 4. Kelurahan
        $villages = [
            ['district_id' => $wenang->id, 'code' => '7171011001', 'name' => 'Kelurahan Wenang Selatan'],
            ['district_id' => $sario->id, 'code' => '7171021001', 'name' => 'Kelurahan Sario Tumpaan'],
            ['district_id' => $tikala->id, 'code' => '7171041001', 'name' => 'Kelurahan Tikala Ares'],
        ];

        foreach ($villages as $vil) {
            $existing = DB::table('villages')->where('code', $vil['code'])->first();
            if (! $existing) {
                DB::table('villages')->insert([
                    'id' => (string) Str::uuid(),
                    'district_id' => $vil['district_id'],
                    'code' => $vil['code'],
                    'name' => $vil['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('villages')->where('id', $existing->id)->update([
                    'district_id' => $vil['district_id'],
                    'name' => $vil['name'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command?->info('Regional data Sulawesi Utara seeded.');
    }
}


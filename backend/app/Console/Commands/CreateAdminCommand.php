<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'sikomando:create-admin 
                            {--email= : Alamat email admin}
                            {--name= : Nama lengkap admin}';

    protected $description = 'Membuat akun SUPER_ADMIN resmi secara interaktif dan aman untuk kebutuhan operasional/produksi';

    public function handle(): int
    {
        $this->info('=== Pembuatan Akun Administrator SIKOMANDO ===');

        $email = $this->option('email') ?: $this->ask('Masukkan alamat email administrator');
        $name = $this->option('name') ?: $this->ask('Masukkan nama lengkap administrator', 'Administrator SIKOMANDO');

        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));
            return self::FAILURE;
        }

        $password = $this->secret('Masukkan kata sandi (minimal 12 karakter)');
        $passwordConfirm = $this->secret('Konfirmasi kata sandi');

        if ($password !== $passwordConfirm) {
            $this->error('Konfirmasi kata sandi tidak cocok.');
            return self::FAILURE;
        }

        $passValidator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($passValidator->fails()) {
            $this->error($passValidator->errors()->first('password'));
            return self::FAILURE;
        }

        $superAdminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'is_system' => true, 'is_active' => true]
        );

        $adminRole = Role::firstOrCreate(
            ['code' => 'ADMIN_SIKOMANDO'],
            ['name' => 'Admin SIKOMANDO', 'is_system' => true, 'is_active' => true]
        );

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $user->roles()->syncWithoutDetaching([$superAdminRole->id, $adminRole->id]);

        $this->info("Akun SUPER_ADMIN '{$email}' berhasil dibuat dengan aman.");

        return self::SUCCESS;
    }
}


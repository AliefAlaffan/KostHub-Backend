<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Buat akun Admin pertama secara interaktif (aman untuk repo publik - tidak ada credential di kode)';

    public function handle(): int
    {
        $name = $this->ask('Nama admin');
        $email = $this->ask('Email admin');
        $password = $this->secret('Password (input tersembunyi)');
        $passwordConfirm = $this->secret('Ulangi password');

        if ($password !== $passwordConfirm) {
            $this->error('Password tidak cocok. Coba lagi.');
            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email|unique:users,email', 'password' => 'required|min:8']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // otomatis ke-hash lewat cast 'hashed' di model
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin->assignRole('admin');

        $this->info("Admin berhasil dibuat: {$email}");
        return self::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ProvisionAdmin extends Command
{
    protected $signature = 'admin:provision {email : Email admin} {--name= : Nama admin}';

    protected $description = 'Create or reactivate an admin with a private password prompt';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }
        $name = trim((string) ($this->option('name') ?: $this->ask('Nama admin')));
        $password = $this->secret('Password minimal 12 karakter');
        if (mb_strlen($name) < 2 || mb_strlen((string) $password) < 12) {
            $this->error('Nama atau password terlalu pendek.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(['email' => $email], [
            'name' => $name, 'password' => Hash::make($password), 'is_active' => true,
        ]);
        $this->info('Akun admin siap digunakan.');

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [];

        foreach (config('seed_accounts.accounts', []) as $slot => $account) {
            $email = $account['email'] ?? null;
            $password = $account['password'] ?? null;

            if (blank($email) && blank($password)) {
                continue;
            }

            $name = $account['name'] ?? null;
            $accounts[$slot] = [
                'name' => is_string($name) ? trim($name) : $name,
                'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
                'password' => $password,
            ];
        }

        // Validate every configured account before inserting any of them.
        $validated = Validator::make(['accounts' => $accounts], [
            'accounts' => ['array'],
            'accounts.*.name' => ['required', 'string', 'min:2', 'max:255'],
            'accounts.*.email' => ['required', 'string', 'email:rfc', 'max:255', 'distinct:strict'],
            'accounts.*.password' => [
                'required', 'string', 'min:12',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                        $fail('Password seeder maksimal 72 byte dan tidak boleh mengandung karakter null.');
                    }
                },
            ],
        ])->validate()['accounts'];

        if ($validated === []) {
            $this->command?->info('Tidak ada akun seed yang dikonfigurasi. Isi SEED_*_EMAIL dan SEED_*_PASSWORD di .env.');

            return;
        }

        $created = DB::transaction(function () use ($validated): int {
            $count = 0;

            foreach ($validated as $account) {
                // Preserve passwords, names and deactivation decisions on subsequent runs.
                if (User::query()->whereRaw('LOWER(email) = ?', [$account['email']])->exists()) {
                    continue;
                }

                User::create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $account['password'],
                    'is_active' => true,
                ]);
                $count++;
            }

            return $count;
        });

        $existing = count($validated) - $created;
        $this->command?->info("Akun dibuat: {$created}; akun yang sudah ada dipertahankan: {$existing}.");
    }
}

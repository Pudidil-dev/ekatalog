<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['seed_accounts.accounts' => []]);
    }

    private function account(string $email, string $name = 'Admin Uji'): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'password' => 'Password-for-tests-only-2026',
        ];
    }

    public function test_configured_admin_and_staff_accounts_are_hashed_active_and_can_log_in(): void
    {
        config(['seed_accounts.accounts' => [
            'admin' => $this->account(' ADMIN@example.test ', 'Admin Utama'),
            'catalog_staff' => $this->account('catalog@example.test', 'Staf Katalog'),
            'stock_staff' => $this->account('stock@example.test', 'Staf Stok'),
        ]]);

        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 3);
        foreach (['admin@example.test', 'catalog@example.test', 'stock@example.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue($user->is_active);
            $this->assertNotSame('Password-for-tests-only-2026', $user->password);
            $this->assertTrue(Hash::check('Password-for-tests-only-2026', $user->password));
            $this->post('/admin/login', [
                'email' => $email, 'password' => 'Password-for-tests-only-2026',
            ])->assertRedirect('/admin/products');
            $this->get('/admin/products')->assertOk();
            $this->post('/admin/logout')->assertRedirect('/admin/login');
        }
    }

    public function test_reseeding_preserves_existing_password_name_and_inactive_status_case_insensitively(): void
    {
        $existing = User::factory()->create([
            'email' => 'Admin@Example.Test', 'name' => 'Pemilik lama',
            'password' => 'Existing-password-test-only', 'is_active' => false,
        ]);
        $hash = $existing->password;
        config(['seed_accounts.accounts' => [
            'admin' => $this->account('admin@example.test', 'Nama baru'),
        ]]);

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $existing->refresh();
        $this->assertSame($hash, $existing->password);
        $this->assertSame('Pemilik lama', $existing->name);
        $this->assertFalse($existing->is_active);
    }

    public function test_empty_credentials_skip_optional_accounts_without_generating_default_users(): void
    {
        config(['seed_accounts.accounts' => [
            'admin' => ['name' => 'Admin Utama', 'email' => null, 'password' => null],
            'catalog_staff' => ['name' => 'Staf Katalog', 'email' => '', 'password' => ''],
        ]]);

        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_incomplete_or_weak_credentials_are_rejected_before_any_account_is_inserted(): void
    {
        foreach ([
            ['email' => 'invalid-address'],
            ['email' => ''],
            ['password' => ''],
            ['password' => 'short'],
            ['password' => str_repeat('a', 73)],
            ['password' => str_repeat('é', 40)],
            ['name' => ''],
        ] as $invalid) {
            config(['seed_accounts.accounts' => [
                'admin' => $this->account('valid@example.test'),
                'catalog_staff' => [...$this->account('staff@example.test'), ...$invalid],
            ]]);

            try {
                $this->seed(UserSeeder::class);
                $this->fail('Invalid account configuration must be rejected.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_duplicate_normalized_emails_are_rejected_before_any_account_is_inserted(): void
    {
        config(['seed_accounts.accounts' => [
            'admin' => $this->account('admin@example.test'),
            'catalog_staff' => $this->account(' ADMIN@example.test '),
        ]]);

        try {
            $this->seed(UserSeeder::class);
            $this->fail('Duplicate configured email addresses must be rejected.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_database_seeder_creates_categories_and_accounts_without_duplicates_on_replay(): void
    {
        config(['seed_accounts.accounts' => ['admin' => $this->account('admin@example.test')]]);

        $this->seed(DatabaseSeeder::class);
        $first = User::where('email', 'admin@example.test')->firstOrFail();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Category::count());
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($first->password, $first->fresh()->password);
    }
}

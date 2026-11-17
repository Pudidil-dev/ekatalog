# E-Katalog deployment

The app is a Laravel 12 modular monolith (PHP 8.2+, Blade/Vite/Alpine) backed by PostgreSQL in a **private `app` schema** and Supabase Storage via the S3-compatible endpoint. Do not deploy until a store name, licensed product images, inventory, WhatsApp number, credentials and restore target are supplied.

## Environments

1. Install PHP 8.2+ with `pdo_pgsql`, `gd`, `fileinfo`, `mbstring`, `zip`, `openssl`, Composer 2 and Node LTS. `php -m` must list `pdo_pgsql`.
2. Configure DNS, HTTPS and an Nginx/PHP-FPM host; adapt `ops/nginx.conf.example` for the PHP-FPM socket and enable the certificate before traffic. Point document root to `public/`; keep `.env` outside the web root and `APP_DEBUG=false`.
3. Create an isolated Supabase PostgreSQL database role for migrations (DDL on `app`) and a separate runtime role (only SELECT/INSERT/UPDATE/DELETE on application tables/sequences). The schema is created before the first Laravel migration. Deny `anon` and `authenticated` database roles access to `app`. Configure TLS with `DB_SSLMODE=verify-full` and a trusted CA as appropriate. Never expose DB or Storage credentials to JavaScript.
4. Create a public-read `products` bucket and private S3-compatible Storage access credentials for server writes. Set `SUPABASE_STORAGE_ENDPOINT`, `SUPABASE_STORAGE_PUBLIC_URL`, key/secret, and bucket per `.env.example`. Verify that `Storage::disk('supabase')->url()` returns reachable product URLs.
5. Configure `.env` from `.env.example`, generate a fresh APP_KEY (`php artisan key:generate`), set `APP_URL` to the canonical HTTPS origin, and set `SESSION_SECURE_COOKIE=true`. There are no hardcoded account passwords. Provision admin interactively using `php artisan admin:provision admin@your-domain --name="Store Owner"`, or configure the account seeder below.
6. Run `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force` **with the migration role**. Run `php artisan db:seed --force` to create the five immutable category choices and any explicitly configured admin/staff accounts, then switch the app to the runtime role. Run `php artisan config:cache && php artisan view:cache`, verify `/up`, login and catalog queries.

The configured Supabase Cloud connection has been checked: `php artisan migrate:status` reported all four migrations as `Ran`, and a read query confirmed `current_schema() = app`. This verifies connection and recorded migration status, not every constraint, rollback, or concurrent operation. Before launch, test migrations/rollback against an isolated PostgreSQL database, runtime-role permissions, concurrent stock adjustments, Storage URLs, and export snapshots. Never use `migrate:fresh` on a populated cloud database. SQLite tests do not prove PostgreSQL-specific behavior.

## Cloud migrations and initial accounts

Laravel owns the application schema. Point `.env` at the cloud **Session pooler**, using the exact host from Supabase Connect, port `5432`, `DB_DATABASE=postgres`, `DB_SCHEMA=app`, and the pooled username `ROLE.PROJECT_REF`. `supabase link` links CLI metadata only; `supabase db push` does not run PHP files from `database/migrations/`.

Run from the project directory:

```powershell
php artisan config:clear
php artisan migrate
php artisan migrate:status
php artisan db:seed
```

Choose schema **app**, rather than public, in the Supabase Table Editor. `Nothing to migrate` means Laravel has already applied the migrations to the configured connection. Use a migration-capable role for migration and category seeding; the restricted runtime role should not create tables or edit the fixed categories.

### Optional admin/staff seeder

`config/seed_accounts.php` reads three slots from `.env`. All of these accounts have **the same admin access**; catalog/stock staff names are labels, not distinct authorization roles. Visitors do not need accounts.

| Account slot | Name | Email | Password |
|---|---|---|---|
| Main administrator | `SEED_ADMIN_NAME` | `SEED_ADMIN_EMAIL` | `SEED_ADMIN_PASSWORD` |
| Catalog staff | `SEED_CATALOG_STAFF_NAME` | `SEED_CATALOG_STAFF_EMAIL` | `SEED_CATALOG_STAFF_PASSWORD` |
| Stock staff | `SEED_STOCK_STAFF_NAME` | `SEED_STOCK_STAFF_EMAIL` | `SEED_STOCK_STAFF_PASSWORD` |

Fill each desired slot's email and password in the ignored `.env` file. Leave **both** blank to skip a slot. Passwords must contain at least 12 characters and at most 72 bytes, matching the current bcrypt constraints. Use different passwords per account. The distributed `.env.example` contains blank credential fields.

Then run:

```powershell
php artisan config:clear
php artisan db:seed --class=UserSeeder
```

The seeder normalizes emails, validates all configured accounts before inserting users, and hashes passwords through the User model. Repeated runs skip existing email addresses, including capitalization variants; they do not reset passwords, rename users, or reactivate disabled users. Change passwords through the admin account form; use the operator provisioning command only when intentionally resetting/reactivating an account.

After provisioning, clear both the email and password fields for slots that are no longer needed and clear/rebuild the Laravel configuration cache. Seeding with stale cached configuration will otherwise keep using the cached values. `.env`, local environment variants and `bootstrap/cache/config.php` must remain ignored by Git. Keep the seeder/config source in version control so setup is reproducible.

## Seeder simulasi barang dengan foto asli

`DemoCatalogSeeder` menambahkan **10 produk** (dua per kategori SSD, RAM, Motherboard, Prosesor, GPU), spesifikasi sesuai kategori, harga rupiah simulasi, stok tersedia/habis, satu foto cover per barang, dan riwayat stok awal. Semua produk demo menggunakan SKU berawalan `DEMO-` dan keterangan data simulasi. Seeder ini dijalankan terpisah; `DatabaseSeeder` tetap hanya mengisi kategori dan akun yang dikonfigurasi.

Foto produk diambil dari internet, diperiksa secara visual, dan disertakan di `database/seeders/data/images/`. Sumber, kredit, tanggal pengambilan, dan SHA-256 tiap foto tercatat di `database/seeders/data/demo-products.json`. Foto diproses menjadi WebP melalui `ProductImageService` lalu diunggah ke disk `PRODUCT_DISK`; aplikasi menggunakan URL Storage sendiri, sehingga tidak bergantung pada hotlink situs sumber. Seeder tidak perlu mengunduh ulang gambar dari internet. Foto dan merek tetap milik pemegang hak masing-masing; paket ini menyediakan contoh simulasi, bukan lisensi komersial foto.

### Jalankan ke Supabase

1. Pastikan `.env` menunjuk database Supabase yang dituju (`DB_CONNECTION=pgsql`, `DB_SCHEMA=app`) dan `PRODUCT_DISK=supabase`. Lengkapi seluruh `SUPABASE_STORAGE_*` sesuai `.env.example`: endpoint S3, key, secret, region, bucket `products`, dan public URL. Bucket harus sudah ada dan public-read.
2. Pastikan tersedia admin aktif sebagai pencatat riwayat stok. Gunakan akun yang sudah ada, isi kredensial `SEED_ADMIN_*` untuk `db:seed`, atau buat dengan `php artisan admin:provision admin@domain-anda --name="Admin Toko"`. Seeder demo memakai admin aktif dengan ID terkecil dan tidak membuat akun/password bawaan.
3. Jalankan dengan role database yang dapat mengisi kategori dan tabel aplikasi:

```powershell
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class=DemoCatalogSeeder --force
php artisan products:reconcile-images
```

Perintah terakhir harus melaporkan `Missing: 0`. Buka katalog dan filter setiap kategori; pada database kosong masing-masing berisi dua barang. Di Supabase Table Editor pilih schema `app` untuk melihat `products`, `product_images`, dan `stock_movements`. Foto tersedia di Storage bucket `products`.

Seeder melewati SKU yang sudah ada secara case-insensitive, termasuk produk yang diarsipkan. Menjalankannya ulang tidak mereset nama, harga, stok, status, foto, ataupun riwayat stok. Produk baru dipublikasikan setelah foto dan riwayat stok berhasil dibuat. Jika satu barang gagal, perubahan database barang tersebut dibatalkan dan foto yang baru diunggah dibersihkan; barang sebelumnya yang sudah selesai tetap tersimpan. Perbaiki penyebab kegagalan lalu jalankan perintah yang sama. Seeder tidak memperbaiki foto yang hilang pada SKU lama; gunakan hasil rekonsiliasi untuk mengidentifikasinya.

Disk Supabase juga mendukung autentikasi S3 session token: `SUPABASE_STORAGE_KEY` berisi project ref, `SUPABASE_STORAGE_SECRET` berisi legacy anon key, dan `SUPABASE_STORAGE_TOKEN` berisi JWT yang valid. Gunakan region project (misalnya `ap-southeast-1`) dan endpoint `https://PROJECT_REF.storage.supabase.co/storage/v1/s3`. Untuk operasi server, JWT service role tetap harus disimpan sebagai rahasia di `.env`; jangan kirim ke browser. Dengan pasangan S3 access key/secret biasa, kosongkan `SUPABASE_STORAGE_TOKEN`. Lihat [dokumentasi autentikasi S3 Supabase](https://supabase.com/docs/guides/storage/s3/authentication).

Ini menggunakan migration dan seeder **Laravel**, bukan `supabase db push` atau SQL Editor. Tidak perlu menjalankan `migrate:fresh`.

Untuk pratinjau lokal, gunakan database SQLite lokal, `PRODUCT_DISK=public`, jalankan migration dan provision admin, lalu perintah seeder yang sama dan `php artisan storage:link`.

Validasi otomatis tanpa menghubungi Supabase:

```powershell
php artisan test --filter=DemoCatalogSeederTest
```

## Backups and recovery

- Capture an encrypted daily PostgreSQL dump of schema `app` plus a manifest of product object paths. Also copy **Storage object bytes** to independent storage; Supabase database backups alone do not contain them.
- Retain versions, watch capacity and backup job health, and restore both database and image objects to staging regularly. Verify image URLs, admin login, counts and stock movements after restore. PRD targets: RPO 24h / RTO 4h, subject to measured restore tests.
- `products:reconcile-images` checks referenced paths and lists orphans. The scheduler runs it daily with `--delete-orphans`, deleting only unreferenced objects older than one day. Run `php artisan schedule:run` every minute with cron and alert on nonzero exit. Missing objects must be repaired from backup. Database and object-store writes cannot be atomically committed together.
- Set up HTTPS uptime checks on `/up`, DB connection/backup alerts, 5xx logs and disk usage. Ensure request logs never contain passwords or secrets. Stock-bearing HTML must not be publicly cached with stale values.

## Deploy gratis ke Render (Docker)

Database dan foto produk tetap di Supabase. Render hanya menjalankan container Laravel (PHP 8.2 + Apache + aset Vite) dari `Dockerfile`. Konfigurasi layanan ada di `render.yaml`; skrip start di `ops/docker/entrypoint.sh`.

1. Push repo ke GitHub.
2. Di Render: **New → Blueprint**, pilih repo ini. Render membaca `render.yaml` dan membuat web service `ekatalog` (plan free, region Singapore).
3. Isi variabel bertanda `sync: false` di dashboard, dengan nilai yang sama seperti `.env` lokal yang sudah terhubung ke Supabase:

   | Variabel | Isi |
   |---|---|
   | `APP_KEY` | Nilai `APP_KEY` dari `.env` (atau `php artisan key:generate --show`). |
   | `APP_URL` | URL Render, mis. `https://ekatalog.onrender.com` (isi setelah URL diketahui, lalu redeploy). |
   | `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | Host **Session pooler** Supabase (`aws-…pooler.supabase.com`) dan user `postgres.PROJECT_REF`. Jangan pakai host *direct connection* — itu IPv6-only dan tidak bisa dijangkau Render free. |
   | `SUPABASE_STORAGE_*` | Key, secret, token (jika dipakai), region, endpoint S3, public URL — sama dengan `.env`. |

4. Deploy. Health check memakai `/up`. Buka URL Render, cek katalog, detail produk, tombol WhatsApp, dan login admin.

Catatan:
- `RUN_MIGRATIONS=false` secara default karena schema Supabase sudah dimigrasikan dari lokal. Set `true` sementara hanya jika ada migration baru dan user DB runtime boleh menjalankan DDL.
- `TRUSTED_PROXIES=*` wajib di Render agar Laravel mengenali HTTPS dari proxy Render (URL aset dan cookie `secure`).
- Free plan tidur setelah ±15 menit tanpa trafik; akses pertama setelahnya butuh ±30–60 detik. Disk container tidak permanen; semua foto disimpan di Supabase Storage.
- Uji image secara lokal: `docker build -t ekatalog .` lalu `docker run --rm -p 8080:8080 --env-file .env -e APP_ENV=production -e APP_DEBUG=false -e APP_URL=http://localhost:8080 ekatalog`.

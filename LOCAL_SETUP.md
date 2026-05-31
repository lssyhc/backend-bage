# Bage Backend Local Setup

Panduan ini menyiapkan backend Laravel untuk local development tanpa wrapper shell khusus. Jalankan command dari root repository `backend-bage`.

## Prasyarat

- PHP, Composer, dan PostgreSQL tersedia di machine lokal.
- Database lokal sudah dibuat sesuai nilai `DB_*` di `.env`.
- Jangan memakai credential production untuk local development.

## Environment

Salin template env lalu isi nilai lokal masing-masing:

```bash
cp .env.example .env
php artisan key:generate
```

Nilai local yang perlu dipastikan:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000
PUBLIC_FILESYSTEM_DRIVER=local
PUBLIC_FILESYSTEM_URL=http://localhost:8000/media
SESSION_DOMAIN=null
SESSION_SAME_SITE=lax
DEMO_SEED_PASSWORD=<isi-password-demo-local>
PRODUCTION_DEMO_SEED_ENABLED=false
```

## Install, Migrasi, dan Seed

```bash
composer install
php artisan migrate:fresh --seed
```

Expected dataset setelah seed:

- `users=9`
- `categories=6`
- `locations=24`
- `posts=60`
- `post_media=95`
- `seed_records=328`

Akun demo memakai username seed seperti `testuser`, `raka_jalan`, dan `naya_kuliner`. Password local berasal dari nilai `DEMO_SEED_PASSWORD` di `.env`.

## Menjalankan Backend

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Backend local tersedia di `http://localhost:8000`.

## Verifikasi

```bash
php artisan migrate:status --no-interaction
php artisan test
./vendor/bin/pint --test
curl -sS -o /tmp/bage-media-check.jpg -w '%{http_code} %{content_type} %{size_download}\n' http://localhost:8000/media/seed/posts/post-001-1.jpg
```

Media seed valid bila curl mengembalikan status `200`, content type `image/jpeg`, dan ukuran file tidak `0`.

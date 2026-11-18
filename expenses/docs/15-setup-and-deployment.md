# 15 — Setup and Deployment

## Prasyarat

- PHP 8.2+.
- MySQL 8.0+.
- Ekstensi PHP: `pdo`, `pdo_mysql`, `fileinfo`, `mbstring`, `session`.
- Apache dengan `mod_rewrite` atau konfigurasi Nginx setara.
- Storage privat yang bisa ditulis PHP.
- HTTPS untuk deployment jaringan.

## Setup lokal

1. Salin repositori proyek dan letakkan di root web server lokal (misalnya Laragon/XAMPP).
2. Buat database `hris` (atau `erp_database` jika standalone).
3. Baca `database/README.md`. File baseline mengandung operasi `DROP TABLE` pada beberapa tabel dan tidak aman untuk diimpor ulang ke database yang telah terisi data operasional.
4. Impor skema data awal:
   - Untuk sistem terintegrasi: impor `Database/hris.sql` ke database `hris`.
   - Untuk standalone modul Expenses: impor `database/erp_database.sql`.
5. Jalankan runner migrasi untuk menerapkan migrasi 001 dan migrasi 002: `php bin/migrate.php`.
6. Terapkan seed role dan permission: `php bin/seed_roles.php`.
7. Konfigurasikan file `.env` dengan `DB_DATABASE=hris` (atau `erp_database` untuk standalone).
8. Buat akun demo melalui PHP CLI: `php bin/seed_demo.php` dengan kata sandi ter-hash `password_hash()`.
9. Pastikan folder `storage/receipts` dan `storage/logs` memiliki izin tulis (writable) oleh proses PHP dan tidak dapat diakses langsung via HTTP.
10. Jalankan aplikasi melalui portal web HRIS (`http://localhost/hris/`) atau jalankan mandiri dengan `php -S 127.0.0.1:8000 -t public`.
11. Uji fungsionalitas dan alur kerja menggunakan suite acceptance test: `php tests/acceptance_test.php`.

Jangan menjalankan SQL baseline setiap kali aplikasi start. Buat migration runner yang melacak migration tambahan melalui tabel `schema_migrations`.

## Apache

- DocumentRoot harus menunjuk ke `project/public`.
- Rewrite route non-file/non-directory ke `index.php`.
- Jangan izinkan akses langsung ke `storage`, `.env`, `config`, `database`, `app`, atau `tests`.

## Nginx

- Set root ke direktori `public`.
- Route selain file statis diteruskan ke `public/index.php`.
- Batasi eksekusi PHP sesuai konfigurasi server.
- Deny dotfiles dan jangan expose folder aplikasi.

## Production checklist

- `APP_ENV=production`, debug nonaktif.
- HTTPS dan cookie session Secure/HttpOnly aktif.
- DB user dengan hak akses minimum, tidak memakai root.
- Secret tidak di repository dan tidak dapat diakses via HTTP.
- Backup database dan receipt storage; lakukan uji restore.
- Atur batas upload/request PHP dan server.
- Zona waktu konsisten.
- Rotasi log dan kebijakan retensi audit/receipt disiapkan.
- Error production tidak menampilkan stack trace/query.
- Migration diterapkan terkontrol setelah backup dan review risiko.
- Verifikasi file permissions dan keberadaan receipt setelah deployment.

## Catatan

Aplikasi yang berjalan lokal tidak otomatis production-ready. Lakukan code review, security review, backup/restore test, serta verifikasi konfigurasi web server sebelum penggunaan nyata.

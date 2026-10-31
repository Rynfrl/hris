# 15 — Setup and Deployment

## Prasyarat

- PHP 8.2+.
- MySQL 8.0+.
- Ekstensi PHP: `pdo`, `pdo_mysql`, `fileinfo`, `mbstring`, `session`.
- Apache dengan `mod_rewrite` atau konfigurasi Nginx setara.
- Storage privat yang bisa ditulis PHP.
- HTTPS untuk deployment jaringan.

## Setup lokal

1. Salin proyek dan masuk ke folder root.
2. Buat database development terpisah atau gunakan server database baru untuk schema baseline.
3. Baca `database/README.md`. SQL baseline mengandung operasi `DROP TABLE` pada beberapa tabel dan tidak aman untuk diimpor ulang ke database berisi data.
4. Impor `database/erp_database.sql` satu kali, misalnya dengan `mysql -u root -p < database/erp_database.sql` atau phpMyAdmin Import. Script membuat/memilih `erp_database`.
5. Jalankan `database/seeds/gt_expense_roles_permissions.sql`.
6. Terapkan `database/migrations/001_gt_expense_integrity.sql` satu kali setelah menjalankan precheck dan backup.
7. Konfigurasikan `.env`/environment dengan `DB_DATABASE=erp_database` dan user DB berizin minimum.
8. Buat akun demo melalui seed PHP CLI dengan `password_hash()`; jangan memasukkan password plaintext ke SQL.
9. Pastikan `storage/receipts` dan `storage/logs` writable oleh proses PHP tetapi tidak dapat diakses lewat HTTP.
10. Jalankan web server dengan document root `public/`; secara lokal `php -S 127.0.0.1:8000 -t public` dapat dipakai.
11. Uji login, receipt privacy, role scope, workflow status, dan acceptance test.

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

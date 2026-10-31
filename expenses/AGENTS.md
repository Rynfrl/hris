# Instruksi untuk AI Coding Assistant

## Misi

Bangun GT Expense sebagai aplikasi pengelolaan pengeluaran dan reimbursement yang dapat dijalankan, terhubung ke database ERP yang disediakan, aman, dan dapat diuji. Ikuti seluruh spesifikasi `docs/` serta SQL sumber di `database/`.

## Wajib membaca database terlebih dahulu

Sebelum membuat kode atau migration:

1. Baca file ini, `README.md`, `database/README.md`, seluruh `database/erp_database.sql`, dan `docs/21-existing-database-integration.md`.
2. Gunakan `database/erp_database.sql` sebagai baseline authoritative. Jangan membuat ulang atau mengganti nama tabel/kolom inti.
3. Bedakan tabel yang memang ada pada SQL baseline dan tabel tambahan yang dibuat oleh migration GT Expense.
4. Identifikasi kolom, FK, enum, dan unique index nyata sebelum menulis SQL repository.
5. Jangan menjalankan SQL baseline berulang kali; ia dapat menghapus sebagian tabel dan gagal pada tabel yang sudah ada.

## Teknologi wajib

- PHP 8.2+ native; tanpa Laravel, Symfony, CodeIgniter, Slim, atau framework PHP lain.
- HTML5, Bootstrap 5.3, CSS khusus proyek, JavaScript native; tanpa jQuery/React/Vue/Angular.
- PDO dengan prepared statements, transaksi, dan locking saat transisi status kritis.
- `password_hash()` dan `password_verify()`; tanpa password plaintext.
- UI Bahasa Indonesia, mata uang IDR, timezone `Asia/Jakarta`.
- Jangan menambah dependensi Composer ke MVP tanpa alasan dan keputusan yang didokumentasikan.

## Kontrak skema database

- Database baseline: `erp_database`, MySQL 8.0+, InnoDB, `utf8mb4`.
- Gunakan `users.user_id`, `roles.role_id`, `users.role_id`, `users.employee_id`; role dibaca dari `roles.role_name`.
- Gunakan `employees.manager_id` untuk hierarki manager. `users.manager_id` tidak ada.
- Gunakan `expenses` untuk expense, `expense_reports` untuk report, dan `expense_report_items` sebagai relasi. `expenses.report_id` tidak ada.
- Receipt MVP tunggal disimpan sebagai metadata di kolom `expenses.receipt_*`; file fisik harus privat di luar `public/`.
- Status expense/report berupa enum uppercase yang didefinisikan SQL: `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`.
- Status `reimbursements` berbeda: `PENDING`, `PROCESSING`, `PAID`, `FAILED`, `CANCELLED`.
- `database/seeds/gt_expense_roles_permissions.sql` menambahkan role/permission khusus GT Expense; `database/migrations/001_gt_expense_integrity.sql` menambah unique constraint dan `expense_comments`.
- Nominal uang menggunakan `DECIMAL(15,2)`, bukan float/double.

## Aturan sumber kebenaran dan konflik

1. SQL sumber adalah kontrak kolom dan enum; dokumen produk adalah kontrak perilaku.
2. Jika dokumen lama menyebut kolom yang tidak ada pada SQL, perbaiki dokumen/kode agar sesuai SQL; jangan mengarang kolom.
3. Jika fitur membutuhkan perubahan skema, buat migration baru, jelaskan alasan, precheck data, dan konsekuensi. Jangan diam-diam mengubah file SQL baseline.
4. Jika ada konflik bisnis yang tidak dapat diselesaikan dengan aman, laporkan sebelum mengimplementasikan titik tersebut.
5. Jangan menambah fitur besar di luar MVP.

## Kualitas wajib

- Pisahkan routing, controller, service/domain logic, repository, dan view.
- View tidak menjalankan query SQL.
- Semua query memakai prepared statements; identifier sort dipetakan dari allowlist.
- Gunakan transaksi untuk perubahan multi-tabel; lock report/reimbursement saat approve, refuse, post, dan mark paid.
- Setiap endpoint menerapkan auth, permission, record scope, validasi status, serta CSRF untuk state-changing requests.
- Escape output HTML; jangan gunakan `innerHTML` untuk user text.
- Jangan pernah menampilkan stack trace, SQL, kredensial, atau path privat ke pengguna.
- Status workflow berubah hanya melalui service dengan state transition eksplisit.
- Total report harus disinkronkan dengan item database.
- Receipt disimpan privat dan diakses melalui controller yang mengecek authorization.
- Berikan loading/disabled state, empty state, pesan sukses/error, serta UI responsif.

## Proses pengerjaan

1. Baca semua dokumen sesuai urutan `README.md`.
2. Laporkan rencana per fase sebelum perubahan luas.
3. Implementasikan satu fase pada satu waktu, dimulai dari konfigurasi, import/migration database, autentikasi, dan permission.
4. Buat seed demo melalui PHP CLI dengan password hashing; jangan menanam kredensial produksi pada SQL.
5. Setelah tiap fase, laporkan file baru/diubah, perintah dan test yang benar-benar dijalankan, hasil aktual, acceptance criteria, serta hal yang belum selesai.
6. Jangan menyatakan test lulus jika belum dijalankan.
7. Jangan menghapus/mereset database pengguna untuk memperbaiki migration.

## Definisi selesai

MVP dianggap selesai jika UI, validasi server, izin akses per-record, persistensi DB, error handling, receipt privat, audit/history, dan acceptance test bekerja. Tombol placeholder atau angka dashboard statis tidak memenuhi kriteria.

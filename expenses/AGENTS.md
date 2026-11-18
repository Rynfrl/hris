# Instruksi untuk AI Coding Assistant

## Misi

Bangun dan pelihara modul **Expenses (GT Expense)** sebagai aplikasi pengelolaan pengeluaran dinas dan reimbursement yang dapat dijalankan, terhubung ke basis data terpadu `hris` (atau `erp_database` untuk pengujian mandiri), aman, dan dapat diuji. Ikuti seluruh spesifikasi pada folder `docs/`, berkas panduan `CARA_MENJALANKAN.md`, `DAFTAR_AKUN_DAN_FITUR.md`, serta skema authoritative di `Database/hris.sql` dan `database/erp_database.sql`.

---

## Wajib Membaca Database Terlebih Dahulu

Sebelum membuat kode atau migrasi baru:

1. Baca file ini, `README.md`, `CARA_MENJALANKAN.md`, `DAFTAR_AKUN_DAN_FITUR.md`, `database/README.md`, dan `docs/21-existing-database-integration.md`.
2. Gunakan database terpadu `hris` (dan skema baseline `database/erp_database.sql`) sebagai baseline authoritative. Jangan membuat ulang atau mengganti nama tabel/kolom inti.
3. Bedakan tabel yang memang ada pada SQL baseline dan tabel tambahan yang dibuat oleh migrasi modul Expenses.
4. Identifikasi kolom, FK, enum, dan unique index nyata sebelum menulis SQL repository.
5. Jangan menjalankan SQL baseline berulang kali; file tersebut memuat perintah `DROP TABLE` dan dapat menghapus sebagian tabel.

---

## Teknologi Wajib

- **Runtime & Bahasa:** PHP 8.2+ native; tanpa Laravel, Symfony, CodeIgniter, Slim, atau framework PHP lain.
- **Frontend:** HTML5 semantik, Bootstrap 5.3, CSS khusus proyek, JavaScript native; tanpa jQuery/React/Vue/Angular.
- **Visualisasi:** Chart.js 4.4 UMD mandiri (*offline-ready*) di `public/assets/vendor/chartjs/`.
- **Database Driver:** PDO dengan prepared statements, transaksi, dan locking saat transisi status kritis.
- **Keamanan Sandi:** `password_hash()` dan `password_verify()`; tanpa password plaintext.
- **Lokalisasi:** UI Bahasa Indonesia, mata uang IDR, timezone `Asia/Jakarta`.
- Jangan menambah dependensi Composer ke MVP tanpa alasan dan keputusan yang didokumentasikan.

---

## Kontrak Skema Database

- **Nama Database:** `hris` (default sistem terintegrasi, dikonfigurasi di `expenses/.env` dan `config/database.php`) atau `erp_database` (standalone fallback).
- **Karakter & Engine:** MySQL 8.0+, InnoDB, `utf8mb4`.
- **Pengguna & Peran:** Gunakan `users.user_id`, `roles.role_id`, `users.role_id`, `users.employee_id`; peran pengguna dibaca dari `roles.role_name`.
- **Hierarki Organisasi:** Gunakan `employees.manager_id` untuk hierarki atasan/manajer. Kolom `users.manager_id` tidak ada.
- **Pengeluaran & Laporan:** Gunakan tabel `expenses` untuk pengeluaran, `expense_reports` untuk laporan klaim, dan `expense_report_items` sebagai tabel relasi penghubung. Kolom `expenses.report_id` tidak ada.
- **Bukti Transaksi (Receipt):** Receipt MVP tunggal disimpan sebagai metadata di kolom `expenses.receipt_*`; file fisik kuitansi wajib disimpan di direktori privat di luar `public/` (`storage/receipts/`).
- **Pilihan Bank Transaksi:** Gunakan kolom `expenses.bank_name` (ditambahkan via migration 002) untuk mencatat rekening bank pembayaran karyawan.
- **Status Database:** Status `expenses` dan `expense_reports` berupa enum uppercase yang didefinisikan SQL: `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`.
- **Status Reimbursement:** Status pada tabel `reimbursements` berbeda: `PENDING`, `PROCESSING`, `PAID`, `FAILED`, `CANCELLED`.
- **Migrasi Tambahan:** 
  - `database/seeds/gt_expense_roles_permissions.sql` menambahkan peran `EMPLOYEE` dan permissions modul Expenses.
  - `database/migrations/001_gt_expense_integrity.sql` menambah unique constraint dan tabel komentar `expense_comments`.
  - `database/migrations/002_add_bank_name_to_expenses.sql` menambah kolom `bank_name` pada tabel `expenses`.
- **Tipe Uang:** Nominal uang wajib menggunakan `DECIMAL(15,2)`, bukan float/double.

---

## Aturan Sumber Kebenaran dan Konflik

1. SQL sumber adalah kontrak kolom dan enum; dokumen produk adalah kontrak perilaku.
2. Jika dokumen lama menyebut kolom yang tidak ada pada SQL, perbaiki dokumen/kode agar sesuai SQL; jangan mengarang kolom.
3. Jika fitur membutuhkan perubahan skema, buat migration baru di `database/migrations/`, jelaskan alasan, precheck data, dan konsekuensi. Jangan diam-diam mengubah file SQL baseline.
4. Jika ada konflik bisnis yang tidak dapat diselesaikan dengan aman, laporkan sebelum mengimplementasikan titik tersebut.
5. Jangan menambah fitur besar di luar MVP.

---

## Kualitas Wajib

- Pisahkan routing, controller, service/domain logic, repository, dan view.
- View tidak boleh menjalankan query SQL.
- Semua query memakai prepared statements; identifier sort dipetakan dari allowlist.
- Gunakan transaksi untuk perubahan multi-tabel; lock report/reimbursement saat approve, refuse, post, dan mark paid.
- Setiap endpoint menerapkan auth, permission, record scope, validasi status, serta CSRF untuk state-changing requests.
- Escape output HTML; jangan gunakan `innerHTML` untuk user text.
- Jangan pernah menampilkan stack trace, SQL, kredensial, atau path privat ke pengguna.
- Status workflow berubah hanya melalui service dengan state transition eksplisit.
- Total report harus disinkronkan dengan item database.
- Receipt disimpan privat dan diakses melalui controller yang mengecek authorization.
- Berikan loading/disabled state, empty state, pesan sukses/error, serta UI responsif.

---

## Definisi Selesai (*Definition of Done*)

Modul Expenses dianggap selesai dan valid jika seluruh alur (UI, validasi server, izin akses per-record, persistensi DB, error handling, receipt privat, audit log, dan acceptance test di `tests/acceptance_test.php`) bekerja dengan benar. Tombol placeholder atau angka dashboard statis tidak memenuhi kriteria.

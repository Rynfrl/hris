# Database Setup — Expenses

## Database utama

`erp_database.sql` adalah file SQL yang diberikan untuk proyek ini dan menjadi baseline database. Database dibuat dengan nama `erp_database`, menggunakan MySQL 8.0+, InnoDB, dan `utf8mb4`.

**Jangan membuat ulang tabel inti Expenses dengan nama atau kolom alternatif.** Modul Expenses harus menggunakan tabel yang sudah tersedia, termasuk `users`, `roles`, `role_permissions`, `employees`, `departments`, `expense_categories`, `expenses`, `expense_reports`, `expense_report_items`, `expense_approvals`, `reimbursements`, `activity_logs`, `audit_logs`, `notifications`, dan `settings`.

## Peringatan sebelum impor

- Jalankan SQL terhadap database development/baru atau setelah membuat backup penuh.
- File utama **bukan migrasi yang aman untuk dijalankan berulang kali**. Ada beberapa `DROP TABLE IF EXISTS`, sedangkan banyak tabel lain dibuat tanpa `DROP TABLE`; mengimpor ulang ke database yang telah terisi dapat menghapus data tabel tertentu dan kemudian gagal saat `CREATE TABLE`.
- Jangan jalankan file ini di production untuk memperbarui skema.
- File ini menambahkan beberapa data master awal dan kategori, tetapi **tidak** membuat akun login demo.

## Impor dengan MySQL CLI

Dari direktori root proyek:

```bash
mysql -u root -p < database/erp_database.sql
```

Karena file menggunakan `CREATE DATABASE` dan `USE erp_database`, pengguna MySQL harus memiliki izin membuat database dan tabel. Alternatifnya, pilih `database/erp_database.sql` di menu Import phpMyAdmin.

## Urutan setup

1. Buat backup atau gunakan server/database development kosong.
2. Impor `database/erp_database.sql` satu kali.
3. Periksa bahwa tabel expense sudah tersedia.
4. Jalankan `database/seeds/gt_expense_roles_permissions.sql` untuk menambahkan role/permission khusus Expenses. Seed ini memakai `INSERT IGNORE`, sehingga dapat dijalankan ulang.
5. Terapkan `database/migrations/001_gt_expense_integrity.sql` **satu kali** setelah memeriksa prasyarat migrasi di awal file. Migration ini menambahkan batasan satu expense per report, satu reimbursement per report, dan tabel komentar laporan. Index dibuat secara kondisional agar pemulihan setelah partial DDL lebih mudah; precheck duplikasi tetap wajib.
6. Jalankan seed pengguna demo melalui PHP CLI agar password diproses memakai `password_hash()`. Jangan memasukkan password teks biasa ke SQL.
7. Konfigurasikan koneksi PDO aplikasi ke database `erp_database`.

## Pemeriksaan skema

```sql
USE erp_database;
SHOW TABLES;
DESCRIBE users;
DESCRIBE expenses;
DESCRIBE expense_reports;
DESCRIBE expense_report_items;
DESCRIBE reimbursements;
```

## Catatan integrasi

- Peran pengguna berasal dari `roles.role_name` melalui `users.role_id`; `users` tidak memiliki kolom `role`.
- Pengguna terkait ke data karyawan melalui `users.employee_id`. Struktur organisasi berada pada `employees.manager_id`, bukan pada `users.manager_id`.
- Receipt disimpan sebagai satu set metadata pada kolom `receipt_*` di `expenses`. File fisik tetap disimpan pada direktori privat di luar `public/`.
- Nominal `expenses.amount`, `expense_reports.total_amount`, dan `reimbursements.amount` harus menggunakan presisi `DECIMAL`, bukan floating-point.
- Status database menggunakan nilai uppercase yang ditentukan dalam SQL sumber. Jangan mengubah ejaan status untuk menyesuaikan label UI.

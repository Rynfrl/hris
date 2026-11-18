# Database Setup — Expenses (GT Expense)

Dokumen ini menjelaskan konfigurasi, struktur skema, dan urutan inisialisasi basis data untuk modul **Expenses (GT Expense)**.

---

## 1. Arsitektur Basis Data: Terintegrasi vs Standalone

Modul Expenses dirancang untuk dapat beroperasi dalam dua lingkungan basis data yang konsisten:

1. **Lingkungan Terintegrasi HRIS & ERP (Standar Produksi & Repositori Utama):**
   - Basis data bernama **`hris`**.
   - Berisi seluruh tabel terintegrasi: HRIS (`users`, `roles`, `employees`, `departments`, `divisi`, `payrolls`), Purchasing, Sales, Inventory, dan Expenses (`expenses`, `expense_reports`, `expense_report_items`, `expense_approvals`, `reimbursements`, `expense_comments`).
   - Sumber skema authoritative terpadu: **`Database/hris.sql`** (atau `hris.sql` di root proyek).

2. **Lingkungan Mandiri Modul Expenses (Standalone Development):**
   - Basis data bernama **`erp_database`** (atau tetap menggunakan `hris`).
   - File baseline mandiri: **`database/erp_database.sql`**.

> [!IMPORTANT]
> **Jangan membuat ulang tabel inti Expenses dengan nama atau kolom alternatif.** Modul Expenses harus menggunakan tabel yang sudah tersedia pada skema authoritative, termasuk `users`, `roles`, `role_permissions`, `employees`, `departments`, `expense_categories`, `expenses`, `expense_reports`, `expense_report_items`, `expense_approvals`, `reimbursements`, `activity_logs`, `audit_logs`, `notifications`, dan `settings`.

---

## 2. Peringatan Keselamatan Sebelum Impor

- Jalankan file SQL skema terhadap basis data development/baru atau setelah membuat backup penuh.
- File `database/erp_database.sql` **bukan migrasi yang aman untuk dijalankan berulang kali**. File tersebut memuat perintah `DROP TABLE IF EXISTS` untuk beberapa tabel tertentu; mengimpor ulang ke database yang telah terisi data dapat menghapus data tabel dan menimbulkan kegagalan DDL.
- Jangan menjalankan file baseline ini di lingkungan *production* untuk memperbarui struktur tabel yang telah berjalan.
- File skema baseline menyediakan data master awal dan kategori, tetapi **tidak** memuat akun pengguna demo. Akun demo harus dibuat melalui script CLI ber-hashing aman.

---

## 3. Prosedur Inisialisasi Basis Data

### Skenario A: Menggunakan Database Terintegrasi HRIS (`hris`) — *Direkomendasikan*

```powershell
# 1. Buat database hris jika belum ada
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS hris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Impor skema lengkap HRIS & ERP dari root proyek
mysql -u root -p hris < Database/hris.sql

# 3. Masuk ke folder expenses, terapkan migrasi tambahan
cd expenses
php bin/migrate.php

# 4. Pasang/verifikasi role dan permissions modul Expenses
php bin/seed_roles.php

# 5. Buat akun demo ter-hash
php bin/seed_demo.php
```

### Skenario B: Menggunakan Skema Standalone (`erp_database`)

```powershell
# 1. Impor baseline erp_database
mysql -u root -p < database/erp_database.sql

# 2. Masuk ke folder expenses, terapkan migrasi tambahan
cd expenses
php bin/migrate.php

# 3. Pasang role dan permissions
php bin/seed_roles.php

# 4. Buat akun demo ter-hash
php bin/seed_demo.php
```

---

## 4. Daftar Migrasi Tambahan Modul Expenses (`database/migrations/`)

Migrasi dijalankan secara otomatis dan terlacak melalui tabel `schema_migrations` menggunakan runner `php bin/migrate.php`:

1. **`001_gt_expense_integrity.sql`**:
   - Menambahkan *unique constraint* satu expense hanya boleh terhubung ke satu report (`uq_eri_expense_id` pada `expense_report_items`).
   - Menambahkan *unique constraint* satu reimbursement per report (`uq_reimbursements_report_id` pada `reimbursements`).
   - Membuat tabel komentar umum laporan (`expense_comments`).
2. **`002_add_bank_name_to_expenses.sql`**:
   - Menambahkan kolom pilihan bank/rekening pembayaran (`bank_name VARCHAR(100) NULL AFTER payment_method`) pada tabel `expenses`.

---

## 5. Pemeriksaan Skema di MySQL

Untuk memeriksa tabel dan struktur kolom yang telah terpasang:

```sql
-- Pilih database aktif (hris atau erp_database)
USE hris;

SHOW TABLES;
DESCRIBE users;
DESCRIBE employees;
DESCRIBE expenses;
DESCRIBE expense_reports;
DESCRIBE expense_report_items;
DESCRIBE reimbursements;
DESCRIBE expense_comments;
```

---

## 6. Catatan Integrasi Penting

- **Peran Pengguna (*Roles*):** Ditentukan melalui relasi `users.role_id` ➔ `roles.role_id` (atau `roles.id_role`). Nilai peran dibaca dari kolom `roles.role_name`.
- **Hierarki Organisasi (*Manager Relation*):** Struktur atasan-bawahan berada pada kolom `employees.manager_id` (bukan di tabel `users`).
- **Bukti Struk/Kuitansi (*Receipt*):** Metadata disimpan pada kolom `receipt_*` di tabel `expenses`. Berkas fisik disimpan di direktori privat `storage/receipts/` di luar *document root* publik.
- **Tipe Data Moneter:** Seluruh kolom nominal uang (`amount`, `total_amount`) wajib menggunakan tipe presisi tetap `DECIMAL(15,2)`, bukan *floating-point*.
- **Nilai Status Workflow:** Nilai status pada basis data wajib menggunakan enum uppercase persis seperti yang didefinisikan skema: `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`.

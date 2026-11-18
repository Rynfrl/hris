# 09 — Database Schema and Source-of-Truth Rules

## 1. Aturan utama

Sumber kebenaran skema data terpadu adalah basis data `hris` ([`../../Database/hris.sql`](../../Database/hris.sql)) untuk seluruh ekosistem HRIS & ERP, serta skema baseline mandiri [`../database/erp_database.sql`](../database/erp_database.sql). Database menggunakan MySQL 8.0+, engine InnoDB, dan charset `utf8mb4`.

**Jangan** membuat ulang versi alternatif dari `users`, `employees`, `roles`, `permissions`, `departments`, `expense_categories`, `expenses`, `expense_reports`, `expense_report_items`, `expense_approvals`, `reimbursements`, atau tabel audit. Gunakan nama kolom dan enum yang benar-benar ada pada SQL sumber. Jangan menjalankan ulang file SQL utama sebagai migration.

Paket migrasi dan seed tambahan yang melengkapi modul Expenses:

- [`../database/seeds/gt_expense_roles_permissions.sql`](../database/seeds/gt_expense_roles_permissions.sql) menambahkan role `EMPLOYEE`, permission posting/reimbursement, dan mapping permission modul Expenses.
- [`../database/migrations/001_gt_expense_integrity.sql`](../database/migrations/001_gt_expense_integrity.sql) menambahkan unique constraint satu expense per report, satu reimbursement per report, dan tabel komentar laporan (`expense_comments`).
- [`../database/migrations/002_add_bank_name_to_expenses.sql`](../database/migrations/002_add_bank_name_to_expenses.sql) menambahkan kolom pilihan bank/rekening transfer (`bank_name VARCHAR(100) NULL AFTER payment_method`) pada tabel `expenses`.

## 2. Tabel autentikasi dan organisasi

### `roles`

Kolom relevan: `role_id`, `role_name`, `description`, `created_at`. Role yang disediakan SQL sumber: `ADMIN`, `HR`, `PURCHASING`, `WAREHOUSE`, `SALES`, `FINANCE`, `MANAGER`. Seed GT Expense menambahkan `EMPLOYEE`.

### `permissions` dan `role_permissions`

`permissions.permission_key` adalah kunci permission unik. `role_permissions` menghubungkan `role_id` dan `permission_id`. SQL sumber memasukkan daftar permission tetapi tidak mengisi semua relasi role-permission; jalankan seed GT Expense. Permission level modul tidak menggantikan pemeriksaan kepemilikan record di PHP.

### `users`

Kolom relevan: `user_id`, `username`, `email`, `password_hash`, `employee_id`, `role_id`, `is_active`, `last_login`, `created_at`, `updated_at`, `deleted_at`. Role ditentukan lewat join `users.role_id → roles.role_id`; tidak ada kolom `users.role`, `users.name`, `users.department_id`, `users.manager_id`, atau `users.last_login_at`.

### `employees` dan `departments`

`employees.employee_id` adalah identitas karyawan. Kolom penting: `employee_number`, `full_name`, `email`, `department_id`, `manager_id`, `position`, `status`, dan detail rekening bank. `employees.manager_id` menunjuk `employees.employee_id` milik manajer langsung. Hubungan akun ke karyawan adalah `users.employee_id → employees.employee_id`.

`departments` menggunakan `department_id`, `department_code`, `department_name`, `manager_id`, dan `is_active`. Jangan mengganti nama kolom ke `id`, `name`, atau `department_id` pada tabel `users`.

## 3. Tabel expense yang tersedia

### `expense_categories`

Kolom: `category_id`, `category_code`, `category_name`, `description`, `default_amount`, `is_fixed_price`, `is_active`, `created_at`, `updated_at`. Kode kategori unik. Kategori yang telah dipakai sebaiknya dinonaktifkan, bukan dihapus.

### `expenses`

Gunakan kolom persis seperti berikut (kolom selain yang disebut tetap mengikuti SQL sumber):

- Identitas/pemilik: `expense_id`, `expense_number`, `employee_id`, `created_by`, `updated_by`.
- Detail: `category_id`, `expense_date`, `description`, `amount`, `currency`, `payment_method`, `bank_name` (Migration 002), `merchant`, `notes`.
- Alokasi: `department_id`, `customer_id`, `project_id`.
- Flag: `is_reimbursable`, `is_reinvoiceable`, `reinvoice_status`.
- Status: enum uppercase `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`.
- Bukti tunggal: `receipt_original_name`, `receipt_stored_name`, `receipt_path`, `receipt_mime`, `receipt_size`.
- Workflow: `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `paid_by`, `paid_at`.
- Timestamp: `created_at`, `updated_at`.

Database sumber mendukung satu set metadata receipt per expense. Simpan file fisik di storage privat di luar `public/`; simpan nama asli hanya untuk display, serta nama acak/path privat dalam kolom receipt yang tersedia. Jangan membuat tabel attachment alternatif untuk MVP.

`employee_id` nullable di skema, tetapi proses pencatatan expense oleh pengguna harus mensyaratkan akun pengguna terhubung ke karyawan aktif. `created_by` wajib menunjuk `users.user_id` dan digunakan sebagai tambahan kontrol siapa yang membuat record.

### `expense_reports`

Kolom: `report_id`, `report_number`, `employee_id`, `title`, `description`, `start_date`, `end_date`, `total_amount`, `status`, `submitted_at`, `approved_at`, `posted_at`, `paid_at`, `refused_at`, `rejection_reason`, `created_at`, `updated_at`.

Status menggunakan enum uppercase sama dengan `expenses`. Tidak ada `owner_id`, `manager_id`, `notes`, `decided_at`, atau `decided_by` pada tabel ini. Pemilik adalah `employee_id`; manajer otorisasi dihitung dari `employees.manager_id` lalu dicocokkan dengan `users.employee_id` dan role `MANAGER`.

`total_amount` ada pada skema dan harus dipelihara dalam transaksi saat item berubah. Namun nilai kanonis tetap dapat diverifikasi dari `SUM(expenses.amount)` melalui `expense_report_items`; jangan menerima total dari browser.

### `expense_report_items`

Kolom: `report_item_id`, `report_id`, `expense_id`; pada SQL sumber terdapat unique pair `(report_id, expense_id)`. Migration tambahan menegakkan unique `expense_id` agar satu expense tidak dapat terhubung ke lebih dari satu report.

Hubungan report–expense selalu melalui tabel ini. Tidak ada `expenses.report_id`.

### `expense_approvals`

Kolom: `approval_id`, `report_id`, `approver_id`, `action`, `comment`, `created_at`. Gunakan sebagai histori keputusan/aksi workflow, dengan nilai `action` konsisten seperti `SUBMITTED`, `APPROVED`, `REFUSED`, `RETURNED_TO_DRAFT`, `POSTED`, `REIMBURSEMENT_PENDING`, dan `PAID`. Kolom `action` adalah `VARCHAR`, sehingga konsistensi nilainya wajib dijaga di aplikasi.

### `reimbursements`

Kolom: `reimbursement_id`, `report_id`, `employee_id`, `amount`, `payment_method`, `payment_reference`, `payment_date`, `notes`, `status`, `processed_by`, `processed_at`, timestamps. Statusnya `PENDING`, `PROCESSING`, `PAID`, `FAILED`, `CANCELLED`. `report_id` tidak unik dalam SQL sumber; migration tambahan menambahkan unique constraint setelah data lama diperiksa.

### `expense_comments` — tabel tambahan

Tabel ini tidak ada di SQL awal. Migration `001_gt_expense_integrity.sql` menambah `expense_comments` untuk komentar non-workflow pada laporan. Untuk keputusan approval, tetap gunakan juga `expense_approvals.comment` sehingga riwayat persetujuan tidak hilang.

## 4. Tabel pendukung yang dipakai aplikasi

- `activity_logs`: ringkasan aksi pengguna; kolom `user_id`, `action`, `entity_type`, `entity_id`, `summary`, `ip_address`, `created_at`.
- `audit_logs`: audit perubahan; kolom `user_id`, `action`, `module`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`. Ini bukan tabel dengan kolom `actor_id/event/metadata_json`.
- `notifications`: notifikasi dalam aplikasi berdasarkan `user_id`, `title`, `message`, `type`, `is_read`, `created_at`.
- `settings`: `setting_key` dan `setting_value`, termasuk nama perusahaan, mata uang, dan tarif pajak yang sudah di-seed.
- `customers` dan `projects`: master opsional; expense bisa dialokasikan melalui `customer_id` dan `project_id`.
- `login_attempts`: pencatatan percobaan login.

Tabel ERP lain seperti purchasing, inventory, sales, vendor invoices, dan payroll tetap milik skema ERP tetapi tidak perlu diubah untuk MVP GT Expense.

## 5. Transaksi, total, dan status

- Seluruh operasi nominal memakai `DECIMAL(15,2)` dan validasi `amount > 0`.
- Pembuatan/pembaruan expense dan report item harus menjaga `expense_reports.total_amount` tetap sinkron.
- Submit, approve/refuse, revise, post, mulai reimbursement, dan tandai paid harus memakai transaksi database dan mengunci report (`SELECT ... FOR UPDATE`) sebelum memeriksa status/membuat transisi.
- Gunakan nilai status uppercase persis seperti enum SQL. Label UI Bahasa Indonesia harus dipetakan terpisah.
- Jangan menyimpan status workflow tambahan yang tidak diizinkan enum. Log detail keputusan melalui `expense_approvals`, `activity_logs`, dan `audit_logs`.

## 6. Indeks dan penguatan integritas

SQL sumber sudah menyediakan foreign key dan beberapa index untuk daftar expense, report, status, employee, category, dan reimbursement. Migration tambahan menerapkan:

- `UNIQUE(expense_report_items.expense_id)` agar satu expense hanya muncul di satu report.
- `UNIQUE(reimbursements.report_id)` agar satu report memiliki paling banyak satu record reimbursement.
- Index komentar berdasarkan `(report_id, created_at)`.

Periksa duplikasi sebelum migration; jangan menghapus atau menggabungkan data lama secara otomatis.

## 7. Setup, seed, dan migration tracking

- Impor SQL utama hanya pada database baru/development setelah backup.
- Jalankan seed role/permission GT Expense.
- Jalankan migration integritas hanya sekali dan catat versi yang diterapkan pada tabel `schema_migrations` yang dikelola aplikasi.
- Buat akun demo dengan PHP CLI dan `password_hash()`; file SQL utama tidak menyediakan akun demo.
- Jangan menyatakan migration idempotent bila belum dibuat demikian dan diuji. Jangan menjalankan file SQL utama setiap kali aplikasi mulai.

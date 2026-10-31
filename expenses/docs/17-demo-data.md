# 17 — Demo Data

Seed ini hanya untuk development/demo; jangan menjalankannya terhadap database produksi.

## Aturan terhadap baseline database

- SQL utama sudah menambahkan beberapa roles, permissions, categories, dan settings, tetapi tidak membuat akun login.
- Jalankan `database/seeds/gt_expense_roles_permissions.sql` terlebih dahulu agar role `EMPLOYEE` dan permission GT Expense tersedia.
- Buat akun demo dengan PHP CLI memakai `password_hash()`. Jangan menyimpan password plaintext di file SQL.
- Jangan mengubah password akun existing secara diam-diam. Seed harus memeriksa email/username sebelum membuat akun.
- `users.employee_id` harus merujuk ke record `employees` yang valid. Manager relation ditentukan di `employees.manager_id`.

## Akun demo yang disarankan

| Nama | Role DB | Email contoh | Employee mapping |
|---|---|---|---|
| Admin GT Expense | `ADMIN` | `admin@example.test` | Opsional |
| Rina Manajer | `MANAGER` | `manager@example.test` | Employee manager |
| Budi Santoso | `EMPLOYEE` | `budi@example.test` | Employee bawahannya Rina |
| Sari Putri | `EMPLOYEE` | `sari@example.test` | Employee bawahannya Rina |
| Dimas Finance | `FINANCE` | `finance@example.test` | Opsional |

Semua password demo harus dummy, didokumentasikan untuk developer lokal, dan diganti sebelum deployment. Jangan menggunakan kredensial demo di production.

## Data organisasi

Seed dapat menggunakan departemen yang dibuat di lingkungan development. Pastikan ID dan kode unik; jangan mengasumsikan ID auto-increment tertentu. Buat employee records terlebih dahulu sebelum membuat akun yang menunjuk ke employee tersebut.

## Kategori

SQL baseline menyediakan kategori awal dengan kode `TRAVEL`, `MEAL`, `TRANSPORT`, `OFFICE`, `OTHER`. Seed demo dapat menggunakan kategori yang sudah ada atau membuat tambahan dengan kode unik. Jangan mengganti kategori di database production.

## Expense demo

Buat beberapa expense berstatus `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, dan `PAID` hanya di lingkungan demo. Gunakan enum uppercase yang benar serta nomor expense unik. Set `created_by` ke `users.user_id` dan `employee_id` ke `users.employee_id` yang sesuai.

## Report demo yang konsisten

Contoh yang dapat disediakan:

- Draft milik Budi dengan dua expense draft.
- Submitted milik Sari untuk antrean Rina.
- Approved report yang menunggu posting Finance.
- Refused report dengan alasan dan `expense_approvals` history.
- Posted report yang menunggu antrean reimbursement.
- Paid report dengan record `reimbursements` yang cocok.

Untuk setiap seeded report, item harus berada di `expense_report_items`, total harus sesuai jumlah expense, serta status report dan expenses konsisten. Paid report harus memiliki reimbursement dengan `status = 'PAID'`, detail payment valid, dan histori/audit yang sesuai.

## Idempotensi

Implementasikan seed PHP dengan upsert atau pemeriksaan stable keys yang jelas. Hindari mengandalkan angka ID tetap. Seed harus dapat dijalankan ulang tanpa menggandakan akun, report, item, atau reimbursement, dan tidak boleh mereset password akun yang sudah ada.

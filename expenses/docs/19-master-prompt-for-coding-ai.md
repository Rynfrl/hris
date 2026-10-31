# 19 — Master Prompt for Coding AI

Salin prompt ini ke AI coding assistant bersama seluruh folder `gt-expense-md-spec`.

---

Anda bertindak sebagai senior PHP engineer, database integrator, application security reviewer, dan UI engineer. Bangun aplikasi **GT Expense** berdasarkan spesifikasi paket ini.

## Wajib dilakukan sebelum coding

1. Baca `AGENTS.md`, `database/README.md`, seluruh `database/erp_database.sql`, dan `docs/21-existing-database-integration.md` terlebih dahulu.
2. Baca dokumen `docs/` lainnya sesuai urutan `README.md`.
3. Anggap `database/erp_database.sql` sebagai sumber kebenaran struktur tabel, nama kolom, FK, index, dan enum baseline.
4. Petakan kebutuhan produk ke tabel yang sudah ada. Jangan membuat tabel duplicate untuk users, employees, roles, categories, expenses, reports, reimbursements, activity logs, atau audit logs.
5. Identifikasi semua migration/seed tambahan. Jangan mengimpor ulang SQL utama saat aplikasi start; file utama tidak aman untuk dijalankan berulang kali.
6. Sebelum membuat query, periksa kolom nyata; jangan menggunakan `users.role`, `users.manager_id`, `expenses.report_id`, `expenses.owner_id`, atau `expense_reports.owner_id` yang tidak ada pada baseline.
7. Jelaskan arsitektur, skema data, status workflow, permission, risiko keamanan, dan urutan implementasi sebelum menulis fitur besar.

## Stack wajib

- PHP native 8.2+ tanpa framework PHP.
- MySQL 8.0+ melalui PDO.
- HTML5, Bootstrap 5.3, CSS khusus proyek, JavaScript native; tanpa jQuery/React/Vue/Angular.
- UI Bahasa Indonesia, IDR, timezone `Asia/Jakarta`.

## Kontrak database

- Database: `erp_database`.
- Role di `roles.role_name` melalui `users.role_id`; role GT Expense: `EMPLOYEE`, `MANAGER`, `FINANCE`, `ADMIN`.
- Employee account map: `users.employee_id`; manager relation: `employees.manager_id`.
- Expense data ada pada `expenses`; reports di `expense_reports`; relationship di `expense_report_items`.
- Receipt tunggal per expense menggunakan kolom `receipt_*`; file fisik privat di luar `public/`.
- Status report/expense menggunakan enum uppercase dari SQL. Reimbursement menggunakan status sendiri dari tabel `reimbursements`.
- Seed `database/seeds/gt_expense_roles_permissions.sql` diperlukan untuk role/permission module.
- Migration `database/migrations/001_gt_expense_integrity.sql` perlu precheck dan hanya diterapkan sekali.

## Hasil wajib

- Aplikasi end-to-end, bukan hanya mockup.
- Arsitektur sederhana: router, controller, service, repository, view.
- Setup, environment example, `.gitignore`, migrasi, seed demo, dan dokumentasi lokal.
- Autentikasi aman, permissions + record-level authorization, CSRF untuk semua aksi mutasi, output escaping, upload/download privat, audit history.
- State transition hanya melalui service. Lock report/reimbursement saat aksi kritis.
- Sinkronkan `expense_reports.total_amount` berdasarkan item database.
- Gunakan database untuk seluruh data dinamis, bukan angka dashboard hardcoded.
- Pagination/filter, empty/error states, validation messages, halaman 403/404/500, dan UI responsif dengan warna biru.
- Tombol/link tidak boleh placeholder.
- Jangan menambahkan OCR, multi-currency, journal integration, atau framework tanpa keputusan eksplisit.

## Urutan implementasi

Ikuti Fase 0–9 dalam `docs/16-implementation-plan.md`, tetapi Fase 1 harus **mengintegrasikan** schema baseline yang diberikan, bukan membuat ulang semua tabel. Terapkan seed dan migration tambahan hanya sesuai dokumen database.

## Output setiap fase

1. Ringkasan perubahan.
2. Daftar file baru/diubah.
3. SQL/seed/migration relevan.
4. Perintah setup/test.
5. Hasil test aktual (termasuk gagal/belum dijalankan).
6. Acceptance criteria lulus/gagal/belum diuji.
7. Risiko dan keputusan yang belum dikonfirmasi.

## Kriteria selesai akhir

MVP berjalan secara lokal dan mampu login, membuat expense dan report, submit, approve/refuse sesuai scope, merevisi report refused, posting, mengantrekan reimbursement, mencatat payment, menampilkan timeline, mengelola kategori/user sesuai izin, menyajikan dashboard dinamis, memfilter/mengekspor CSV, dan lulus security regression yang relevan. Jangan menyatakan production-ready sebelum ada security review, backup/restore test, dan verifikasi konfigurasi deployment.

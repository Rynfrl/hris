# Expenses — AI-Ready Project Specification

Paket ini berisi spesifikasi dan file database untuk membangun **Expenses**, modul expense management dan reimbursement yang terintegrasi dengan database ERP yang diberikan pengguna.

> [!TIP]
> - **Panduan Menjalankan Program:** Untuk instruksi langkah demi langkah menjalankan aplikasi, setup database, dan server web lokal, silakan baca panduan lengkap di **[CARA_MENJALANKAN.md](file:///D:/gt-expense-md-spec/CARA_MENJALANKAN.md)**.
> - **Daftar Akun & Fitur Demo:** Untuk rincian lengkap seluruh username, password, hak akses, dan penjelasan fitur tiap role, silakan baca **[DAFTAR_AKUN_DAN_FITUR.md](file:///D:/gt-expense-md-spec/DAFTAR_AKUN_DAN_FITUR.md)**.


## Teknologi wajib

- PHP native 8.2+ tanpa framework PHP.
- MySQL 8.0+ melalui PDO.
- HTML5, Bootstrap 5.3, CSS proyek, JavaScript native tanpa jQuery.
- Bahasa UI: Bahasa Indonesia. Mata uang: IDR. Timezone: `Asia/Jakarta`.

## Database disertakan

- `database/erp_database.sql` — salinan file SQL yang diberikan; baseline skema utama.
- `database/README.md` — panduan impor dan peringatan keselamatan.
- `database/seeds/gt_expense_roles_permissions.sql` — seed role/permission untuk Expenses.
- `database/migrations/001_gt_expense_integrity.sql` — migration unique constraint dan komentar report.
- `docs/09-database-schema.md` dan `docs/21-existing-database-integration.md` — pemetaan skema dan aturan integrasi.

**Penting:** `erp_database.sql` bukan migration yang aman dijalankan berulang. Gunakan database development/baru atau backup terlebih dahulu. Jangan menjalankannya di production. Baca `database/README.md` sebelum impor.

## Cara menggunakan paket

1. Berikan seluruh folder ini sebagai konteks kepada AI coding assistant.
2. Instruksikan AI membaca `AGENTS.md` serta `database/README.md` dan `database/erp_database.sql` terlebih dahulu.
3. AI harus mencocokkan semua query dengan skema SQL nyata, bukan membuat tabel expense duplikat atau memakai kolom asumsi.
4. Jalankan import SQL utama sekali pada lingkungan development yang aman.
5. Jalankan seed role/permission dan migration integritas sesuai petunjuk. Buat akun demo dengan PHP CLI dan `password_hash()`.
6. Ikuti `docs/16-implementation-plan.md`, satu fase per satu fase; jalankan acceptance test setelah setiap fase.
7. Catat perubahan skema bisnis/teknis di `docs/20-decisions-and-assumptions.md`.

## Urutan pembacaan

1. `AGENTS.md`
2. `database/README.md`
3. `database/erp_database.sql`
4. `docs/21-existing-database-integration.md`
5. `docs/01-product-vision.md`
6. `docs/02-product-requirements.md`
7. `docs/03-feature-catalog.md`
8. `docs/04-roles-and-permissions.md`
9. `docs/05-workflows-and-states.md`
10. `docs/06-information-architecture.md`
11. `docs/07-ui-design-system.md`
12. `docs/08-technical-architecture.md`
13. `docs/09-database-schema.md`
14. `docs/10-routes-and-actions.md`
15. `docs/11-security-requirements.md`
16. `docs/12-validation-and-business-rules.md`
17. `docs/13-reports-and-exports.md`
18. `docs/14-testing-and-acceptance.md`
19. `docs/15-setup-and-deployment.md`
20. `docs/16-implementation-plan.md`
21. `docs/17-demo-data.md`
22. `docs/18-operations-and-configuration.md`
23. `docs/19-master-prompt-for-coding-ai.md`
24. `docs/20-decisions-and-assumptions.md`

## MVP

Login/logout, permission + record-level authorization, dashboard dinamis, expense CRUD, satu receipt privat per expense, report workflow, approval/refusal, revisi report, posting finance, reimbursement, kategori/pengguna, komentar/timeline, audit logs, filter, pagination, dan CSV export.

Di luar MVP: OCR/AI receipt, multi-receipt per expense, email receipt, multi-currency, integrasi jurnal eksternal, mobile native, dan PDF/XLSX.

## Aturan implementasi

- Gunakan tabel dan kolom yang memang ada pada SQL sumber; jangan membuat `users`/`expenses`/`expense_reports` versi kedua.
- Validasi/otorisasi dilakukan di server; JS hanya memperbaiki UX.
- Jangan mempercayai employee ID, role, nominal, atau status dari browser.
- Simpan receipt di storage privat di luar document root.
- Gunakan migration terkontrol, seed development, dan acceptance test.

## Referensi produk

GT Expense mengambil inspirasi alur umum expense management, bukan menyalin kode, aset, atau identitas merek produk lain. Referensi fitur: [Odoo Expenses](https://www.odoo.com/app/expenses-features) dan [Dokumentasi Odoo Expenses](https://www.odoo.com/documentation/18.0/applications/finance/expenses/approve_expenses.html).

# Expenses (GT Expense) — Manifest File

Paket dokumentasi dan spesifikasi modul Expenses (GT Expense) mencakup dokumen panduan operasional, dokumen spesifikasi teknis, serta file skema basis data dan migrasi:

## 1. Dokumen Root Modul (`expenses/`)

- `README.md` — Gambaran umum modul Expenses, stack teknologi, daftar berkas database, dan urutan pembacaan spesifikasi.
- `AGENTS.md` — Aturan wajib, batasan arsitektur, dan prinsip implementasi untuk AI coding assistant dan developer.
- `CARA_MENJALANKAN.md` — Panduan langkah demi langkah inisialisasi basis data, migrasi, konfigurasi `.env`, dan cara menjalankan server web.
- `DAFTAR_AKUN_DAN_FITUR.md` — Rincian seluruh akun demo, hak akses (*role & permissions*), penjelasan fitur, dan alur pengujian (*walkthrough*).
- `MANIFEST.md` — Daftar lengkap seluruh file dokumentasi dan komponen modul Expenses.

## 2. Berkas Basis Data & Migrasi (`expenses/database/`)

- `database/erp_database.sql` — Salinan skema SQL baseline mandiri modul Expenses.
- `database/README.md` — Panduan penyiapan database, mitigasi DDL destruktif, dan integrasi dengan database `hris`.
- `database/seeds/gt_expense_roles_permissions.sql` — Seed penambahan peran `EMPLOYEE` dan hak akses modul Expenses.
- `database/migrations/001_gt_expense_integrity.sql` — Migrasi integritas relasi 1 expense per report, 1 reimbursement per report, dan tabel komentar (`expense_comments`).
- `database/migrations/002_add_bank_name_to_expenses.sql` — Migrasi penambahan kolom pilihan rekening bank (`bank_name VARCHAR(100) NULL`) pada tabel `expenses`.

## 3. Dokumen Spesifikasi Teknis & Desain (`expenses/docs/`)

1. `01-product-vision.md` — Visi produk, latar belakang masalah, dan batasan ruang lingkup MVP.
2. `02-product-requirements.md` — PRD (*Product Requirements Document*), kebutuhan fungsional dan nonfungsional.
3. `03-feature-catalog.md` — Katalog modul dan rincian fitur aplikasi.
4. `04-roles-and-permissions.md` — Matriks otorisasi peran (*RBAC*) dan isolasi data per pengguna (*record-level access*).
5. `05-workflows-and-states.md` — State machine dan siklus status expense, report, serta reimbursement.
6. `06-information-architecture.md` — Arsitektur informasi, struktur navigasi, dan hierarki layar.
7. `07-ui-design-system.md` — Sistem desain antarmuka, token warna biru profesional, tipografi, dan aksesibilitas.
8. `08-technical-architecture.md` — Struktur folder MVC/Service, alur permintaan (*request flow*), dan pola koneksi database.
9. `09-database-schema.md` — Kontrak authoritative skema tabel, nama kolom, tipe data, dan relasi foreign key.
10. `10-routes-and-actions.md` — Kontrak route HTTP, method, proteksi CSRF, dan tindakan controller.
11. `11-security-requirements.md` — Persyaratan keamanan autentikasi, otorisasi, pencegahan XSS, SQLi, dan proteksi berkas kuitansi privat.
12. `12-validation-and-business-rules.md` — Validasi server-side lengkap untuk setiap form dan aturan bisnis workflow.
13. `13-reports-and-exports.md` — Formula metrik dashboard, analitik visual, dan spesifikasi ekspor CSV aman.
14. `14-testing-and-acceptance.md` — Kriteria penerimaan (*Acceptance Criteria*) P0/P1 dan checklist regresi keamanan.
15. `15-setup-and-deployment.md` — Panduan konfigurasi server web (Apache/Nginx/Built-in) dan checklist siap rilis (*production readiness*).
16. `16-implementation-plan.md` — Rencana implementasi teknis bertahap (Fase 0 hingga 9).
17. `17-demo-data.md` — Spesifikasi akun, data organisasi, dan alur transaksi demo pengembangan.
18. `18-operations-and-configuration.md` — Manajemen environment `.env`, kebijakan logging, rotasi data, dan strategi backup/restore.
19. `19-master-prompt-for-coding-ai.md` — Master prompt instruksi untuk AI coding assistant.
20. `20-decisions-and-assumptions.md` — Log keputusan arsitektural (*Architecture Decision Record / ADR*).
21. `21-existing-database-integration.md` — Peta integrasi terpadu dengan database sentral `hris` dan skema baseline.

## Catatan Penting

Dalam arsitektur terintegrasi HRIS & ERP, basis data utama yang digunakan adalah `hris` (skema lengkap berada di root repositori: `Database/hris.sql` atau `hris.sql`). Berkas `database/erp_database.sql` berfungsi sebagai baseline mandiri. Jalankan migrasi tambahan secara aman melalui migration runner: `php bin/migrate.php`.

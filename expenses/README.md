# Expenses (GT Expense) — Modul Spesifikasi & Implementasi

Paket ini berisi spesifikasi teknis, rancangan alur kerja, dan file basis data untuk modul **Expenses (GT Expense)** — aplikasi manajemen pengeluaran dinas dan penggantian biaya (*reimbursement*) yang terintegrasi secara penuh dengan sistem ERP & HRIS (`hris`).

> [!TIP]
> - **Panduan Menjalankan Program:** Untuk instruksi langkah demi langkah konfigurasi, inisialisasi database, dan server web lokal, silakan baca panduan lengkap di **[CARA_MENJALANKAN.md](CARA_MENJALANKAN.md)**.
> - **Daftar Akun & Fitur Demo:** Untuk rincian lengkap seluruh username, password, hak akses per peran, dan skenario pengujian, silakan baca **[DAFTAR_AKUN_DAN_FITUR.md](DAFTAR_AKUN_DAN_FITUR.md)**.
> - **README Utama HRIS:** Untuk gambaran integrasi keseluruhan sistem enterprise, silakan buka **[README Utama](../README.md)**.

---

## Teknologi Wajib

- **Bahasa & Runtime:** PHP native 8.2+ tanpa framework eksternal (Laravel/Symfony/CI).
- **Basis Data:** MySQL / MariaDB 8.0+ menggunakan driver PDO (Prepared Statements).
- **Frontend:** HTML5 semantik, Bootstrap 5.3, CSS kustom proyek, Vanilla JavaScript murni tanpa jQuery/framework JS.
- **Visualisasi & Grafik:** Chart.js 4.4 UMD mandiri (*offline-ready* di `public/assets/vendor/`).
- **Lokalisasi:** Bahasa antarmuka Bahasa Indonesia, mata uang Rupiah (IDR), zona waktu `Asia/Jakarta`.

---

## Basis Data & Migrasi yang Disertakan

Modul Expenses beroperasi di atas database terpadu `hris` (atau `erp_database` untuk pengujian mandiri):

- `Database/hris.sql` (di root proyek) — skema basis data terpadu yang memuat seluruh tabel HRIS, ERP, dan modul Expenses.
- `database/erp_database.sql` — salinan skema baseline mandiri modul Expenses.
- `database/README.md` — panduan impor basis data dan pencegahan eksekusi berulang.
- `database/seeds/gt_expense_roles_permissions.sql` — seed penambahan peran `EMPLOYEE` serta hak akses *posting* dan *reimbursement*.
- `database/migrations/001_gt_expense_integrity.sql` — migrasi penegakan integritas relasi 1 expense per report, 1 reimbursement per report, dan tabel komentar (`expense_comments`).
- `database/migrations/002_add_bank_name_to_expenses.sql` — migrasi penambahan kolom pilihan bank/rekening pembayaran (`bank_name VARCHAR(100) NULL`) pada tabel `expenses`.
- `database/migrations/004_consolidated_expense_reimbursement_workflow.sql` — migrasi skema reimbursement gabungan (nullable employee_id, recipient metadata: name, bank, account number/holder, proof_path) dan pembaruan hak akses peran.
- `docs/09-database-schema.md` & `docs/21-existing-database-integration.md` — dokumentasi pemetaan skema dan aturan integrasi.

> [!IMPORTANT]
> Script `erp_database.sql` bukan migrasi aman yang dapat dijalankan berulang kali karena memuat perintah `DROP TABLE`. Pada lingkungan sistem terintegrasi, gunakan database utama `hris`. Jalankan migrasi tambahan menggunakan runner terpadu: `php bin/migrate.php`.

---

## Cara Menggunakan Paket Dokumentasi

1. Baca `AGENTS.md`, `CARA_MENJALANKAN.md`, serta `DAFTAR_AKUN_DAN_FITUR.md` untuk memahami konteks dan konfigurasi.
2. Gunakan database `hris` sebagai sumber kebenaran data; jangan membuat tabel duplikat (seperti `gt_users` atau `gt_expenses`).
3. Terapkan migrasi integritas (`php bin/migrate.php`) dan seed data demo (`php bin/seed_demo.php`).
4. Ikuti rencana bertahap pada `docs/16-implementation-plan.md` dan verifikasi kriteria penerimaan pada `docs/14-testing-and-acceptance.md`.

---

## Urutan Pembacaan Dokumen

1. `AGENTS.md` — Petunjuk utama untuk AI & Developer.
2. `CARA_MENJALANKAN.md` — Panduan instalasi, konfigurasi `.env`, dan server web.
3. `DAFTAR_AKUN_DAN_FITUR.md` — Kredensial akun demo, hak akses per role, dan fitur.
4. `database/README.md` — Penjelasan arsitektur database dan urutan impor.
5. `docs/21-existing-database-integration.md` — Integrasi dengan skema database sentral.
6. `docs/01-product-vision.md` — Visi produk dan permasalahan yang diselesaikan.
7. `docs/02-product-requirements.md` — PRD, kebutuhan fungsional & nonfungsional.
8. `docs/03-feature-catalog.md` — Katalog modul dan fitur detail.
9. `docs/04-roles-and-permissions.md` — Matriks peran dan hak akses record-level.
10. `docs/05-workflows-and-states.md` — Siklus status pengeluaran, laporan, dan reimbursement.
11. `docs/06-information-architecture.md` — Navigasi dan hierarki halaman.
12. `docs/07-ui-design-system.md` — Sistem desain antarmuka, warna, dan token visual.
13. `docs/08-technical-architecture.md` — Arsitektur teknis folder dan alur MVC/Service.
14. `docs/09-database-schema.md` — Kontrak skema tabel, kolom, dan relasi.
15. `docs/10-routes-and-actions.md` — Kontrak route HTTP dan aksi controller.
16. `docs/11-security-requirements.md` — Keamanan autentikasi, otorisasi, CSRF, upload kuitansi.
17. `docs/12-validation-and-business-rules.md` — Validasi server-side dan aturan bisnis.
18. `docs/13-reports-and-exports.md` — Agregasi dashboard, analitik, dan ekspor CSV.
19. `docs/14-testing-and-acceptance.md` — Kriteria penerimaan (*Acceptance Criteria*) & regresi.
20. `docs/15-setup-and-deployment.md` — Panduan setup lokal dan checklist produksi.
21. `docs/16-implementation-plan.md` — Rencana implementasi bertahap (Fase 0–9).
22. `docs/17-demo-data.md` — Struktur dan ketentuan data demo pengembangan.
23. `docs/18-operations-and-configuration.md` — Operasional, logging, backup, dan environment.
24. `docs/19-master-prompt-for-coding-ai.md` — Prompt master untuk AI engineering.
25. `docs/20-decisions-and-assumptions.md` — Catatan keputusan arsitektural (*ADR*).

---

## Ruang Lingkup MVP (Minimum Viable Product)

- **Fitur Termasuk MVP:** Login/logout aman, proteksi brute-force, regenerasi ID sesi, pembagian sesi SSO dengan portal HRIS, dashboard dinamis per peran, CRUD biaya dinas pribadi, satu berkas struk/kuitansi privat (*receipt*), pengelompokan laporan klaim (*expense report*), alur persetujuan manajer (*approve/refuse* dengan alasan penolakan wajib), pembukuan oleh bagian keuangan (*Finance posting*), antrean pencairan uang (*reimbursement*), master kategori pengeluaran, manajemen pengguna, komentar laporan, riwayat persetujuan, jejak audit (`audit_logs`), filter data, pagination server-side, serta ekspor file CSV aman dengan proteksi formula injection.
- **Di Luar Cakupan MVP:** Ekstraksi receipt otomatis (OCR/AI), multi-receipt per transaksi, pengiriman bukti via email, transaksi multi-mata uang (*multi-currency*), integrasi jurnal akuntansi eksternal, aplikasi mobile native, dan ekspor PDF/XLSX.

---

## Referensi Produk

Modul Expenses mengambil inspirasi alur kerja standar industri expense management (seperti [Odoo Expenses](https://www.odoo.com/app/expenses-features) dan [Dokumentasi Odoo Expenses](https://www.odoo.com/documentation/18.0/applications/finance/expenses/approve_expenses.html)), dengan implementasi mandiri yang disesuaikan untuk struktur ERP & HRIS organisasi.

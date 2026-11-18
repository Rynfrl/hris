# 16 — Implementation Plan

Implementasikan per fase. Database baseline sudah disediakan; jangan membuat ulang seluruh tabel dari nol.

## Fase 0 — Bootstrap proyek

- Buat struktur folder PHP native, environment config, front controller/router, error pages, layout Bootstrap, CSS token biru, dan README runtime.
- Pastikan document root `public/`, route tidak dikenal menghasilkan 404, serta debug tidak bocor.

**Exit criteria:** halaman dasar tampil, route bekerja, Bootstrap/CSS proyek aktif.

## Fase 1 — Audit dan integrasi database

- Baca `Database/hris.sql` serta `database/erp_database.sql` dan verifikasi versi MySQL, tabel, kolom, foreign key, enum, serta index.
- Sambungkan PDO ke `hris` (atau `erp_database` untuk standalone); jangan generate ulang tabel baseline.
- Jalankan seed role/permission modul Expenses.
- Buat migration tracking dan terapkan `001_gt_expense_integrity.sql` serta `002_add_bank_name_to_expenses.sql` via runner `php bin/migrate.php`.
- Buat repository/query sesuai schema, `Database` wrapper, CSRF, flash, validation, dan audit foundation.

**Exit criteria:** aplikasi terhubung ke database; query memakai nama kolom valid; seed tidak menimpa data; migration tercatat; tidak ada tabel duplikat.

## Fase 2 — Autentikasi dan authorization

- Login/logout aman, session regeneration, check `users.is_active` dan role melalui join `roles`.
- Resolve employee mapping melalui `users.employee_id`; resolve manager melalui `employees.manager_id`.
- AuthorizationService berbasis permission dan record scope.

**Exit criteria:** test RBAC lulus; guest/role salah ditolak; data employee tidak bocor.

## Fase 3 — Master data

- CRUD user, role/permission mapping yang aman, kategori, dan department sesuai schema existing.
- Mengubah/menonaktifkan account tanpa menghapus histori.
- Jangan membuat `users` atau category table baru.

**Exit criteria:** unique username/email/category code ditegakkan; user nonaktif tidak bisa login.

## Fase 4 — Expense management

- CRUD expense draft, filter/search/pagination, satu receipt upload/download privat, audit events.
- Isi `employee_id`/`created_by` server-side; gunakan kategori aktif.

**Exit criteria:** expense persisted; receipt protected; ID manipulation ditolak.

## Fase 5 — Report workflow

- Membuat report, mengelola item via `expense_report_items`, menjaga `total_amount`, submit, komentar, timeline.
- State transition service menggunakan transaksi dan approval history.

**Exit criteria:** report kosong tidak bisa disubmit; expense tidak ada di dua report; jumlah/status konsisten.

## Fase 6 — Approval

- Antrean manager, detail report, receipt, approve/refuse, rejection reason, revisi refused report.

**Exit criteria:** hanya direct manager berpermission bisa memutuskan; histori dan audit tersimpan.

## Fase 7 — Posting/reimbursement

- Antrean report approved, posting Finance, create reimbursement queue, processing, mark paid/failed, payment history.

**Exit criteria:** state transition valid, tidak ada double reimbursement, pembayaran tercatat atomik.

## Fase 8 — Dashboard, analytics, CSV

- Metric berdasarkan role dan status DB, grafik native sederhana, filter, ekspor CSV aman.

**Exit criteria:** metrik cocok dengan query, export scope sama dengan list, formula injection dimitigasi.

## Fase 9 — UX, QA, deployment

- Responsiveness, error/empty states, accessibility basics, edge cases, security regression, backup/restore notes.

**Exit criteria:** acceptance criteria applicable lulus; tidak ada tombol placeholder; deployment terdokumentasi.

## Laporan tiap fase

- Ringkasan pekerjaan dan daftar file dibuat/diubah.
- SQL seed/migration relevan.
- Perintah yang benar-benar dijalankan dan hasil test aktual.
- Acceptance criteria lulus/gagal/belum diuji.
- Risiko dan keputusan yang butuh konfirmasi.

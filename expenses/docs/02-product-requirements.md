# 02 — Product Requirements Document (PRD)

## 1. Sasaran rilis

GT Expense MVP adalah modul web expense management/reimbursement untuk satu perusahaan, diintegrasikan ke database `hris` (atau `erp_database` untuk setup standalone). UI berbahasa Indonesia, mata uang IDR, timezone `Asia/Jakarta`.

## 2. Asumsi operasional

- PHP native 8.2+; MySQL 8.0+ melalui PDO; HTML5, Bootstrap 5.3, CSS proyek, JavaScript native.
- Database baseline yang wajib dipakai adalah `hris` (skema terpadu `Database/hris.sql`) atau `database/erp_database.sql` (skema baseline modul); jangan menduplikasi tabel inti.
- Setiap akun karyawan dipetakan ke `employees` melalui `users.employee_id`. Manager langsung dibaca dari `employees.manager_id`.
- Karyawan membuat expense individual dan mengelompokkannya ke report melalui `expense_report_items`.
- Report adalah unit submit dan keputusan approval.
- Satu receipt per expense untuk MVP karena baseline menyediakan kolom `receipt_*` tunggal.
- Workflow memiliki tahap approval, posting oleh Finance, antrean reimbursement, dan pembayaran final sesuai enum status database.
- Persetujuan belum mem-post jurnal akuntansi eksternal; posting di MVP hanya status workflow aplikasi.
- Notifikasi dalam aplikasi cukup; email eksternal bukan syarat MVP.

## 3. Modul wajib

1. Autentikasi: login/logout aman dan status akun aktif.
2. Dashboard: ringkasan sesuai role dan scope.
3. Pengeluaran: create/view/edit/delete draft, receipt privat tunggal, pencarian/filter.
4. Laporan pengeluaran: membuat report dan menambahkan expense draft yang belum terikat report.
5. Approval: antrean manager, approve/refuse dengan alasan, revisi report yang ditolak.
6. Posting dan reimbursement: Finance mem-post report lalu mencatat proses/pembayaran.
7. Master data: kategori, user, role/permission, employee mapping, department.
8. Reporting: filter, pagination, agregasi dan CSV export.
9. Timeline/audit/notifikasi: gunakan tabel bawaan `expense_approvals`, `activity_logs`, `audit_logs`, `notifications`, serta tabel tambahan `expense_comments` dari migration.

## 4. Persyaratan fungsional

- FR-01: User aktif dapat login dan melihat dashboard yang relevan dengan role/scope.
- FR-02: Karyawan yang terhubung ke employee aktif dapat membuat expense berisi kategori, tanggal, deskripsi, nominal, merchant opsional, dan receipt opsional.
- FR-03: Setiap expense dapat menyimpan satu receipt yang diunggah dengan validasi server dan storage privat.
- FR-04: User hanya dapat mengubah/hapus expense `DRAFT` miliknya sendiri.
- FR-05: Expense `DRAFT` yang belum terhubung report dapat ditambahkan ke satu report `DRAFT` milik employee yang sama.
- FR-06: Total report dihitung dari item database dan kolom `expense_reports.total_amount` dijaga konsisten.
- FR-07: Submit memvalidasi item, mengubah status report/expense, dan menyimpan histori secara transaksional.
- FR-08: Manager hanya dapat approve/refuse report direct report yang berada dalam scope-nya.
- FR-09: Refusal memerlukan alasan dan disimpan dalam kolom status terkini serta histori.
- FR-10: Report `REFUSED` dapat dikembalikan ke `DRAFT`; histori lama tidak dihapus.
- FR-11: Finance dengan permission yang tepat dapat mem-post report `APPROVED`, lalu membuat record reimbursement dan memproses pembayaran.
- FR-12: Satu report tidak dapat terikat pada lebih dari satu reimbursement aktif/final pada MVP.
- FR-13: Admin dapat mengelola user, role/permission, kategori, dan departemen sesuai izin.
- FR-14: Daftar dapat dicari, difilter, disortir lewat allowlist, dan dipaginasi server-side.
- FR-15: Expense dan report dapat diekspor ke CSV setelah authorization check.
- FR-16: Aksi penting tercatat dalam activity/audit logs tanpa menyimpan password, token, atau isi file.
- FR-17: Pengguna berwenang dapat melihat timeline keputusan dan komentar laporan.

## 5. Persyaratan nonfungsional

- Keamanan: authorization per endpoint/record, CSRF, SQL injection prevention, output escaping, validasi receipt, dan error handling aman.
- Kinerja: query berindeks dan pagination; hindari membaca seluruh tabel tanpa batas.
- Aksesibilitas: input berlabel, navigasi keyboard, kontras memadai, dan status tidak dibedakan hanya dengan warna.
- Kegunaan: layout responsif, pesan validasi jelas, konfirmasi tindakan destruktif.
- Maintainability: controller, service, repository, view dipisah; query tidak diletakkan di view.
- Reliability: semua perubahan multi-tabel memakai transaksi/locking yang sesuai.

## 6. Di luar MVP

OCR/AI receipt extraction, beberapa attachment per expense, email receipt alias, integrasi bank, multi-currency, pembukuan/jurnal otomatis, sinkronisasi payroll, mobile native, multi-company, approval bertingkat, dan ekspor PDF/XLSX.

## 7. Prioritas

- **P0:** integrasi database baseline, autentikasi, permissions, expense CRUD, receipt privat, report workflow, approval, posting/reimbursement, audit trail.
- **P1:** dashboard dinamis, pencarian/filter/pagination, admin/master data, komentar, CSV export, responsive polish.
- **P2:** email, PDF, OCR, multi-currency, integrasi akuntansi.

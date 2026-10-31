# 21 — Existing ERP Database Integration

Dokumen ini menjelaskan cara membangun GT Expense di atas file database ERP yang diberikan pengguna, tanpa membuat database/tabel expense duplikat.

## Sumber yang wajib diikuti

- SQL baseline: [`../database/erp_database.sql`](../database/erp_database.sql)
- Panduan impor: [`../database/README.md`](../database/README.md)
- Seed permission GT Expense: [`../database/seeds/gt_expense_roles_permissions.sql`](../database/seeds/gt_expense_roles_permissions.sql)
- Migration penambahan integritas: [`../database/migrations/001_gt_expense_integrity.sql`](../database/migrations/001_gt_expense_integrity.sql)

Baca file SQL sebenarnya, bukan hanya ringkasan dokumentasi. Bila nama/tipe kolom pada SQL sumber berbeda dari contoh pada dokumen lain, SQL sumber menjadi kontrak teknis dan dokumen yang bertentangan harus diperbaiki.

## Entity mapping

| Konsep aplikasi | Tabel/kolom sumber |
|---|---|
| Login/account | `users.user_id`, `username`, `email`, `password_hash`, `is_active`, `last_login` |
| Role | `users.role_id` → `roles.role_id`, `roles.role_name` |
| Permission | `permissions.permission_key` melalui `role_permissions` |
| Akun karyawan | `users.employee_id` → `employees.employee_id` |
| Nama karyawan | `employees.full_name` |
| Departemen karyawan | `employees.department_id` → `departments.department_id` |
| Manajer langsung | `employees.manager_id` → `employees.employee_id` manajer |
| Kategori | `expense_categories.category_id`, `category_code`, `category_name`, `is_active` |
| Pengeluaran | `expenses` |
| Report pengeluaran | `expense_reports` |
| Detail report | `expense_report_items` |
| Histori approval | `expense_approvals` |
| Pembayaran reimbursement | `reimbursements` |
| Komentar report | `expense_comments` dari migration tambahan |
| Aktivitas dan audit | `activity_logs`, `audit_logs` |
| Notifikasi | `notifications` |
| Customer/project allocation | `customers`, `projects`, serta FK pada `expenses` |

## Resolusi pemilik dan manajer

### Pengeluaran

Saat user login, ambil `users.user_id`, `users.role_id`, `users.employee_id`, serta `roles.role_name`. Untuk membuat expense, user harus aktif dan terhubung dengan karyawan aktif. Server mengisi `expenses.created_by = users.user_id` dan `expenses.employee_id = users.employee_id`; jangan menerima dua nilai ini dari input bebas.

Untuk otorisasi edit/hapus expense, periksa `created_by` dan `employee_id`, status `DRAFT`, serta scope role. Jangan menganggap `expense_id` yang valid berarti boleh diakses.

### Laporan

Pemilik report adalah `expense_reports.employee_id`. Akun boleh mengelola laporan miliknya jika `users.employee_id = expense_reports.employee_id`. Pengguna yang tidak memiliki `employee_id` tidak boleh membuat expense/report atas nama karyawan.

Untuk approval manajer, lakukan resolusi dari employee pemilik report: `employees.manager_id` menunjuk employee manajer; cari akun aktif yang `users.employee_id` sama dengan employee manajer itu dan role-nya `MANAGER`. Pastikan hasilnya tunggal atau tangani konfigurasi yang ambigu secara aman. Jangan membaca `users.manager_id` karena kolom itu tidak ada.

## Role yang digunakan GT Expense

Gunakan nama role uppercase dari database: `EMPLOYEE`, `MANAGER`, `FINANCE`, `ADMIN`. Role `EMPLOYEE` ditambahkan oleh seed GT Expense; role `MANAGER`, `FINANCE`, dan `ADMIN` sudah ada dalam baseline.

Role ERP lain seperti `HR`, `PURCHASING`, `WAREHOUSE`, dan `SALES` bukan otomatis role GT Expense. Jangan beri akses expense/reimbursement ke role tersebut kecuali permission secara eksplisit diberikan oleh administrator. Administrator sistem (`ADMIN`) tidak otomatis menjadi approver atau petugas reimbursement.

`role_permissions` pada baseline tidak disemai dengan semua relasi role-permission yang diperlukan. Jalankan seed khusus GT Expense setelah impor. Semua endpoint tetap harus melakukan record-level authorization di PHP.

## Receipt

Skema baseline menyediakan satu receipt per expense melalui kolom `receipt_*`. Versi MVP menggunakan satu file bukti per expense. Validasi ekstensi, MIME hasil server, ukuran, dan nama acak; simpan file di direktori privat di luar document root. Halaman detail dan endpoint download harus melakukan pemeriksaan kepemilikan/scope sebelum membaca file.

Jika kelak perlu banyak kuitansi per expense, buat migration terpisah dan keputusan produk eksplisit. Jangan diam-diam membuat tabel attachment yang tidak terdokumentasi.

## Siklus status yang sesuai enum

Status `expenses` dan `expense_reports` harus memakai uppercase yang diizinkan skema:

`DRAFT → SUBMITTED → APPROVED → POSTED → REIMBURSEMENT_PENDING → PAID`

Penolakan: `SUBMITTED → REFUSED → DRAFT` untuk revisi. Status `CANCELLED` hanya untuk pembatalan yang sah dan belum dibayar. Detail aksi dan komentar disimpan di histori/audit.

- Submit: ubah report dan seluruh expense pada report menjadi `SUBMITTED`; catat `submitted_at` dan histori.
- Approve: ubah report/expense menjadi `APPROVED`; isi `approved_by`/`approved_at` pada expense dan `approved_at` pada report; catat `expense_approvals`.
- Refuse: ubah report/expense menjadi `REFUSED`; simpan alasan dan waktu pada kolom yang tersedia; catat approval history.
- Revise: kembalikan status ke `DRAFT`, lepaskan kolom penolakan aktif jika diperlukan, tetapi pertahankan histori keputusan di `expense_approvals`/log.
- Post: petugas Finance dengan permission `expense.post` mengubah report/expense dari `APPROVED` menjadi `POSTED` dan mengisi `posted_at`.
- Queue reimbursement: dari `POSTED`, buat satu `reimbursements` berstatus `PENDING` dan ubah report/expense ke `REIMBURSEMENT_PENDING` dalam satu transaksi.
- Mark paid: Finance dengan permission `expense.reimburse` hanya dapat menyelesaikan record `PENDING`/`PROCESSING` yang belum dibayar; set reimbursement `PAID`, isi pembayaran yang relevan, dan ubah report/expense ke `PAID`.

Status reimbursement (`PENDING`, `PROCESSING`, `PAID`, `FAILED`, `CANCELLED`) dan status report/expense adalah dua state machine yang saling dikonsolidasikan melalui service. Jangan menganggap `reimbursements.status` memiliki enum yang sama dengan `expenses.status`.

## Perubahan skema tambahan

Migration `001_gt_expense_integrity.sql` menambah aturan yang tidak dijamin file asli:

- Satu `expense_id` paling banyak ada pada satu report.
- Satu report paling banyak memiliki satu record reimbursement.
- Report memiliki tabel komentar umum.

Sebelum menambahkan unique index pada database yang sudah berisi data, jalankan query precheck di awal migration dan tinjau hasilnya. Migration tidak boleh membersihkan konflik data secara otomatis.

## Hal yang tidak boleh dilakukan AI

- Jangan membuat tabel `users`, `departments`, `expenses`, atau `expense_reports` versi kedua.
- Jangan mengubah `expense_id`/`report_id` menjadi `id` atau mengubah casing enum pada database.
- Jangan memakai `users.role`, `users.manager_id`, `expenses.report_id`, `expenses.owner_id`, `expense_reports.owner_id`, atau kolom lain yang tidak ada pada SQL sumber.
- Jangan menyimpan password plaintext atau menambahkan akun default dengan password produksi.
- Jangan mengimpor SQL utama setiap kali aplikasi dinyalakan.
- Jangan menghapus tabel ERP lain yang tidak terkait GT Expense.

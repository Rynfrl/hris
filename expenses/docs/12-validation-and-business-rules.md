# 12 — Validation and Business Rules

Semua validasi penting harus dilakukan di PHP/server. JavaScript hanya membantu UX. Nama kolom serta enum wajib mengikuti [`09-database-schema.md`](09-database-schema.md) dan SQL sumber.

## Form expense

- `category_id`: bilangan positif; kategori `expense_categories.is_active = 1`.
- `expense_date`: tanggal valid `YYYY-MM-DD`. Default MVP memperbolehkan hari ini dan masa lalu, bukan masa depan.
- `description`: wajib setelah trim, 3–255 karakter karena kolom database `VARCHAR(255)`.
- `amount`: wajib, desimal valid, `> 0`, maksimal 2 digit desimal, maksimum bisnis default Rp1.000.000.000,00 per expense. Simpan sebagai `DECIMAL(15,2)`.
- `merchant`: opsional maksimal 150 karakter.
- `notes`: opsional; batas ditetapkan aplikasi.
- `currency`: tetapkan `IDR` untuk MVP meskipun kolomnya `CHAR(3)`.
- `payment_method`: gunakan allowlist internal yang terdokumentasi karena kolom database berupa `VARCHAR(50)`.
- `employee_id` dan `created_by` diisi berdasarkan session dan pemetaan `users.employee_id`, bukan dari browser.
- Untuk menyederhanakan pembayaran MVP, expense baru diset `is_reimbursable = 1` dan `is_reinvoiceable = 0`; flags tidak boleh diubah melalui input bebas.
- `expense_number` dibuat di server dan dijamin unik oleh constraint database.

## Receipt

- Database inti mendukung satu receipt per expense melalui kolom `receipt_*`.
- Jenis yang diperbolehkan: `.jpg`, `.jpeg`, `.png`, `.webp`, `.pdf`; periksa ekstensi dan MIME hasil server.
- Maksimum 5 MB per file.
- Tolak file kosong/terlalu besar/tipe tidak didukung. Jangan percaya `Content-Type` yang dikirim browser.
- Nama file asli hanya untuk display dan wajib di-escape. Gunakan nama file stored yang acak.
- Storage harus berada di luar `public/`; endpoint receipt memeriksa authorization sebelum membaca file.
- Hanya pemilik pada expense `DRAFT` dapat mengganti/menghapus receipt.

## Report dan detail item

- `title`: wajib, 3–150 karakter menyesuaikan `expense_reports.title`.
- `description`: opsional dan disimpan pada `expense_reports.description`.
- `report_number`: nomor unik yang dibuat server, misalnya `GTE-2026-000001`; gunakan strategi aman terhadap request serentak.
- `employee_id` report diisi dari `users.employee_id` saat membuat report.
- Report minimal berisi satu item sebelum submit.
- Item dikaitkan melalui `expense_report_items`; jangan mencari kolom `expenses.report_id`.
- Expense harus dimiliki employee yang sama dengan report, berstatus `DRAFT`, dan belum ada pada report lain.
- Pertahankan `expense_reports.total_amount` tetap sinkron dalam setiap transaksi item add/remove. Total dihitung server dari tabel relasi/amount, bukan dari nilai kiriman browser.
- Jika `start_date`/`end_date` diisi, masing-masing harus valid dan `start_date <= end_date`; aturan apakah tanggal expense wajib berada dalam rentang report harus konsisten di semua endpoint.

## Manager dan approval

- Role dicek menggunakan join `users.role_id` ke `roles.role_id`, lalu `roles.role_name = 'MANAGER'`; permission `expense.approve` juga harus ada.
- Manajer langsung didapat dari `employees.manager_id` milik employee pemilik report, kemudian dicocokkan dengan `users.employee_id` akun manager yang aktif.
- Jangan mengasumsikan `users.manager_id` atau `expense_reports.manager_id` tersedia.
- Approve/refuse hanya berlaku untuk report `SUBMITTED`.
- Refuse wajib memiliki alasan minimal 5 karakter setelah trim, maksimal 2.000 karakter; simpan pada kolom report/expense yang disediakan dan `expense_approvals.comment`.
- Jalankan aksi status dalam transaksi dan lock report dengan `SELECT ... FOR UPDATE`.

## Revisi

- Pemilik dapat merevisi report berstatus `REFUSED` ke `DRAFT`.
- Setel kembali status item expense ke `DRAFT` dan bersihkan rejection fields aktif apabila akan mengizinkan pengajuan ulang.
- Jangan hapus histori `expense_approvals`, `activity_logs`, atau `audit_logs`.

## Posting dan reimbursement

- Hanya Finance berpermission `expense.post` dapat mem-post report `APPROVED` menjadi `POSTED`.
- Hanya Finance berpermission `expense.reimburse` dapat memasukkan report `POSTED` ke antrean reimbursement atau menandai pembayaran final.
- `payment_method` pada `reimbursements` adalah `VARCHAR(50)`, tetapi aplikasi harus menggunakan allowlist yang disepakati, misalnya `BANK_TRANSFER`, `CASH`, `OTHER`.
- `payment_date` harus tanggal valid; `payment_reference` maksimal 150 karakter mengikuti database.
- Buat satu row reimbursement per report; migration unique constraint membantu mencegah duplikasi.
- Proses untuk membuat reimbursement dan menandai paid mengunci report/reimbursement dalam transaksi, menguji ulang status, mencatat `processed_by`/`processed_at`, lalu menyelaraskan status report dan expense.

## User, role, dan master data

- Normalisasikan email lowercase sebelum validasi keunikan.
- Password diproses dengan `password_hash()` dan `password_verify()`.
- Role mengacu pada `roles.role_name` uppercase; role GT Expense yang digunakan: `EMPLOYEE`, `MANAGER`, `FINANCE`, `ADMIN`.
- Jangan menonaktifkan admin terakhir yang aktif tanpa prosedur pemulihan akses yang aman.
- Kategori/departemen yang sudah dipakai hanya dinonaktifkan, bukan dihapus.
- Setiap karyawan yang login untuk expense wajib memiliki akun `users` dengan `employee_id` yang valid. Pastikan employee mapping unik secara aplikasi walaupun kolom DB tidak unique.

## Query dan pagination

- `page >= 1`; default page size 20, maksimum 100.
- Rentang tanggal harus valid dan `from <= to`.
- Status/department/category menerima allowlist/ID valid.
- Sort key dipetakan ke kolom tetap.
- Terapkan record scope dalam query sebelum filter tambahan. Semua query menggunakan PDO prepared statements.

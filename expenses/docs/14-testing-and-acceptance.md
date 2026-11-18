# 14 — Testing and Acceptance Criteria

## Pendekatan

Uji aturan business/service, integrasi database, dan alur UI/HTTP. Test memakai database development, bukan produksi. Pastikan migration GT Expense diterapkan dan test menyadari schema sumber.

## Database dan autentikasi

- AC-DB-01: Aplikasi terhubung ke database `hris` (atau `erp_database` untuk standalone) dan memakai kolom yang benar-benar ada pada SQL sumber.
- AC-DB-02: Setup tidak membuat tabel kedua `users`, `expenses`, `expense_reports`, atau `expense_categories`.
- AC-DB-03: Seed GT Expense menambahkan role/permission/mapping tanpa menimpa akun atau password existing.
- AC-MIG-01: Migration 001 tercatat dan tabel `expense_comments` tersedia.
- AC-MIG-02: Migration 002 tercatat dan kolom `bank_name` tersedia pada tabel `expenses`.
- AC-AUTH-01: Login kredensial valid membuat sesi setelah validasi `users.is_active`.
- AC-AUTH-02: Password salah, akun tak dikenal, dan user nonaktif menghasilkan pesan aman.
- AC-AUTH-03: Session ID berubah setelah login.
- AC-AUTH-04: Logout POST mengakhiri sesi dengan CSRF valid.
- AC-AUTH-05: Route privat tidak dapat dibuka guest.

## Expenses

- AC-EXP-01: Employee aktif dengan `users.employee_id` valid dapat membuat expense draft dan data tersimpan.
- AC-EXP-01c: Kolom `bank_name` tersimpan dan diperbarui dengan benar pada expense draft.
- AC-EXP-02: Nominal nol/negatif, tanggal invalid, kategori nonaktif, atau deskripsi kosong ditolak oleh server.
- AC-EXP-03: User tidak dapat mengubah/menghapus expense orang lain lewat manipulasi ID.
- AC-EXP-04: Expense non-`DRAFT` tidak dapat diedit lewat endpoint draft.
- AC-EXP-05: File PHP/HTML/SVG dan file lebih dari batas ditolak.
- AC-EXP-06: Receipt tidak dapat diakses lewat URL publik; endpoint receipt memeriksa authorization.
- AC-EXP-07: Satu expense menyimpan satu receipt sesuai skema baseline.

## Reports/workflow

- AC-REP-01: Report draft dibuat dengan `employee_id` dari session user, bukan input bebas.
- AC-REP-02: Expense hanya dapat terhubung ke satu report; unique constraint migration menegakkan aturan ini.
- AC-REP-03: Report kosong tidak dapat disubmit.
- AC-REP-04: Submit menyinkronkan status report/expense, `total_amount`, approval history, audit, dan notifikasi.
- AC-REP-05: Manager di luar direct-report scope tidak dapat approve/refuse.
- AC-REP-06: Hanya report `SUBMITTED` dapat approve/refuse.
- AC-REP-07: Refuse tanpa alasan ditolak; alasan tercatat dalam `expense_approvals` dan report.
- AC-REP-08: Owner dapat mengembalikan report `REFUSED` ke `DRAFT`; histori lama tetap ada.
- AC-REP-09: Concurrent request tidak menghasilkan transisi ganda/status tidak konsisten.

## Posting/reimbursement

- AC-PAY-01: Finance yang memiliki permission `expense.post` dapat mem-post report `APPROVED` ke `POSTED`.
- AC-PAY-02: Report hanya masuk antrean reimbursement setelah `POSTED`.
- AC-PAY-03: Report draft/submitted/refused tidak dapat direimburse.
- AC-PAY-04: Satu report tidak dapat memiliki dua reimbursement, termasuk request bersamaan.
- AC-PAY-05: Mark paid menyinkronkan record reimbursement, report, expense, tanggal, metode, aktor, dan audit secara atomik.
- AC-PAY-06: Finance yang tidak memiliki scope/permission tidak bisa membayar laporan melalui POST langsung.

## Roles, reports, export, and visual analytics

- AC-RBAC-01: Employee hanya dapat melihat expense/report sendiri.
- AC-RBAC-02: Manager hanya dapat melihat/memutuskan report direct report.
- AC-RBAC-03: Finance memiliki akses posting/reimbursement, bukan approval manager.
- AC-RBAC-04: User non-admin mendapat 403 pada pengelolaan master data.
- AC-RBAC-05: Peran Manager tidak memiliki hak membuat pengeluaran dan laporan klaim pribadi (D-018).
- AC-CSV-01: Export menerapkan filter dan authorization scope yang sama dengan list.
- AC-CSV-02: Teks mirip formula spreadsheet tidak dieksekusi (proteksi CSV injection).
- AC-DASH-01: Angka dashboard cocok dengan query agregat yang mendasarinya.
- AC-CHART-01 - AC-CHART-05: Integrasi Chart.js UMD mandiri (offline) dan visualisasi tren pengeluaran serta distribusi kategori di dashboard & analitik.

## Security regression checklist

- Coba SQL injection di login/search/filter/sort.
- Masukkan HTML/script di deskripsi, komentar, nama file, dan pastikan di-escape.
- Kirim form tanpa token CSRF atau token salah.
- Ubah ID expense/report/receipt lewat URL/POST.
- Ubah `employee_id`, `created_by`, `approved_by`, `paid_by`, `role_id`, `amount`, dan `status` secara manual.
- Uji ekstensi ganda, ukuran berlebih, path traversal, MIME palsu, dan unduhan receipt oleh user lain.
- Coba approve/reimburse berulang dan request paralel.
- Pastikan error production tidak menunjukkan stack trace/SQL.

## Definition of done

Fitur dianggap selesai bila acceptance criteria relevan lulus, kolom/status cocok dengan SQL baseline, route terlindungi, validasi server aktif, UI jelas, migration/seed tercatat, tidak ada tombol placeholder, dan dokumentasi perubahan diperbarui. Jangan menyatakan tes lulus kecuali benar-benar telah dijalankan.

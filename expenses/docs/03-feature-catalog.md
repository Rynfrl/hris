# 03 — Feature Catalog

## A. Autentikasi dan profil

- Login dengan email/username dan password (sesuaikan UX, gunakan kolom yang tersedia).
- Pesan login gagal generik; akun nonaktif tidak dapat login.
- Logout melalui POST + CSRF.
- Profil menampilkan `employees.full_name`, email, departemen, role, dan manager berdasarkan relasi schema.
- Session ID diregenerasi setelah login.

## B. Dashboard

Kartu dinamis mengikuti role:

- Employee: expense draft, report menunggu approval/ditolak, report berjalan, total pembayaran selesai.
- Manager: report submitted yang menunggu keputusan dan total nilainya.
- Finance: report approved yang menunggu posting, report posted yang menunggu pembayaran, pembayaran periode.
- Admin: ringkasan agregat sesuai permission.

Grafik MVP memakai HTML/CSS atau SVG/Canvas native. Semua angka wajib berasal dari database dan dibatasi sesuai scope akses.

## C. Pengeluaran

Field: nomor expense, employee, kategori, tanggal transaksi, deskripsi, nominal IDR, merchant opsional, metode pembayaran, pilihan bank (bank_name), receipt tunggal, status, notes, dan timestamp.

Aksi: buat, detail, update/delete ketika `DRAFT`, unggah/ganti/hapus receipt ketika `DRAFT`, filter, pencarian, dan masukkan expense ke report draft. `employee_id` dan `created_by` selalu ditentukan server-side.

## D. Laporan pengeluaran

Field: nomor report unik, title, employee, rentang tanggal opsional, deskripsi, total, status, timestamp, rejection reason.

Aksi owner: buat report, masukkan expense draft milik employee yang sama, edit ketika draft, submit, revisi report refused, lihat timeline/comment. Detail item disimpan di `expense_report_items`; tidak ada kolom `expenses.report_id`.

Aksi manager: antrean report `SUBMITTED`, lihat item dan receipt, approve/refuse dengan alasan wajib. Hanya manager yang terkait lewat `employees.manager_id` dan memiliki permission yang boleh membuat keputusan. Sesuai keputusan D-018, peran Manager difokuskan murni sebagai peninjau/approver dan tidak memiliki akses untuk menambah pengeluaran baru maupun membuat laporan klaim pribadi demi menjaga pemisahan tugas (*segregation of duties*).

## E. Posting dan reimbursement

- Finance dengan permission `expense.post` mengubah report `APPROVED` menjadi `POSTED`.
- Finance memindahkan report `POSTED` ke `REIMBURSEMENT_PENDING` dengan record `reimbursements` status `PENDING`.
- Finance mencatat proses pembayaran, metode, referensi, dan tanggal; saat selesai report/expense serta reimbursement berstatus `PAID`.
- Satu report hanya boleh punya satu reimbursement pada MVP.

## F. Master data

Admin mengelola users, role-permission mapping, kategori, employee mapping, dan departments sesuai cakupan MVP. Role disimpan dalam tabel `roles`; jangan membuat role kolom string dalam `users`. Nonaktifkan kategori/user yang telah direferensikan, jangan menghapus histori transaksi.

## G. Pencarian dan ekspor

Filter sesuai izin: rentang tanggal, status, kategori, employee, department. Search pada description/expense number/report number/nama employee. Pagination server-side dan CSV dengan perlindungan formula injection.

## H. Komentar, timeline, audit

- Komentar umum report disimpan di `expense_comments` yang dibuat oleh migration GT Expense.
- Histori workflow keputusan disimpan di `expense_approvals`.
- Aktivitas aplikasi memakai `activity_logs`; audit perubahan memakai `audit_logs`.
- Notifikasi internal memakai `notifications`.
- Log tidak dapat diedit/dihapus oleh user umum.

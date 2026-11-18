# 06 — Information Architecture

## Navigasi utama

### Menu Umum (Semua Pengguna Login)

- Dashboard (disesuaikan dengan peran pengguna)
- Profil Akun & Informasi Pegawai
- Logout (POST + CSRF)

### Employee (Karyawan)

- Pengeluaran Saya (Pencatatan draft & unggah kuitansi)
- Laporan Saya (Pembuatan, penggabungan expense, submit klaim, revisi refused)

### Manager (Peninjau / Approver)

- Persetujuan Tim (Antrean laporan berstatus `SUBMITTED` dari bawahan langsung)
- Laporan Tim (Pemantauan riwayat laporan bawahan langsung)
- Analitik Tim (Grafik pengeluaran khusus divisi yang dipimpin)
*(Catatan: Sesuai D-018, Manajer tidak memiliki menu pencatatan pengeluaran atau pengajuan klaim pribadi).*

### Finance (Keuangan)

- Pembukuan / Posting (Antrean laporan `APPROVED` yang menunggu pembukuan)
- Reimbursement (Antrean laporan `POSTED`/`REIMBURSEMENT_PENDING` yang menunggu pembayaran)
- Laporan Pengeluaran Organisasi
- Analitik & Ekspor CSV

### Admin (Administrator Sistem)

- Manajemen Pengguna & Pemetaan Karyawan (`/settings/users`)
- Master Kategori Pengeluaran (`/settings/categories`)
- Master Departemen / Divisi (`/settings/departments`)
- Audit Log & Rekam Jejak Keamanan (`/settings/audit-logs`)
- Analitik Keseluruhan Perusahaan & Ekspor CSV (`/analytics`)

Menu hanya dirender sesuai role, tetapi server tetap wajib memeriksa izin pada setiap route.

## Halaman dan tujuan

| Halaman | Tujuan | Aksi utama |
|---|---|---|
| Login | Autentikasi | Masuk |
| Dashboard | Ringkasan sesuai role | Buka daftar terkait |
| Expense list | Melihat pengeluaran sendiri atau cakupan yang diizinkan | Cari, filter, buat |
| Expense create/edit | Mengelola expense draft | Simpan, unggah kuitansi |
| Expense detail | Rincian, receipt, status | Edit jika draft, masukkan ke laporan |
| Report list | Mengelola/meninjau laporan | Cari, filter, buka detail |
| Report form/detail | Ringkasan laporan, item, total, riwayat | Tambah item, submit, revisi |
| Approval queue | Laporan submitted dalam cakupan manager | Review, approve, reject |
| Reimbursement queue | Report `POSTED`/`REIMBURSEMENT_PENDING` yang belum `PAID` | Tandai pembayaran |
| Master categories | Kelola kategori | Tambah/edit/nonaktifkan |
| Users | Kelola akun dan relasi tim | Tambah/edit/nonaktifkan |
| Departments | Kelola departemen | Tambah/edit/nonaktifkan |
| Reports/export | Ringkasan dan CSV | Filter, ekspor |
| Audit log | Penelusuran aktivitas | Filter/read-only |

## Komponen layout

- Sidebar desktop yang dapat diciutkan jika tidak menambah kompleksitas berlebihan.
- Navbar atas dengan nama halaman, pencarian bila relevan, dan menu akun.
- Breadcrumb pada halaman bertingkat.
- Konten dalam container responsif dengan jarak antarbagian konsisten.
- Tabel desktop dengan kolom prioritas; pada layar kecil gunakan horizontal overflow atau card layout yang tetap bisa digunakan.
- Badge status selalu menyertakan teks, tidak hanya warna.
- Form memiliki label eksplisit, petunjuk singkat, dan pesan error dekat dengan field.

## Pola interaksi

- Gunakan modal Bootstrap hanya untuk aksi singkat seperti konfirmasi atau penolakan.
- Form utama dan detail lebih baik sebagai halaman tersendiri supaya URL dapat dibagikan dalam cakupan akses yang benar.
- Semua perubahan data dilakukan lewat POST; jangan mengubah status melalui GET.
- Setelah perubahan sukses, gunakan redirect-after-POST untuk menghindari pengiriman ganda.
- Gunakan pagination server-side, dan pertahankan filter yang sedang dipakai saat pindah halaman.

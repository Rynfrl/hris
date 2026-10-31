# 06 — Information Architecture

## Navigasi utama

### Semua pengguna yang login

- Dashboard
- Pengeluaran Saya
- Laporan Saya
- Profil/Akun

### Manager

- Persetujuan Tim
- Laporan Tim

### Finance

- Menunggu Reimbursement
- Laporan Pengeluaran
- Laporan/Export

### Admin

- Pengguna
- Departemen
- Kategori Pengeluaran
- Audit Log
- Pengaturan aplikasi sederhana

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

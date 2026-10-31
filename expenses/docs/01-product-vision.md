# 01 — Product Vision

## Ringkasan

**GT Expense** adalah aplikasi web internal untuk mencatat, mengajukan, meninjau, menyetujui, dan mengganti biaya operasional yang dikeluarkan karyawan untuk kepentingan organisasi.

## Masalah yang diselesaikan

- Bukti transaksi dan nominal tersebar di chat, kertas, atau spreadsheet.
- Karyawan kesulitan mengetahui status pengajuan.
- Manajer memerlukan konteks transaksi, kuitansi, dan alasan pengeluaran sebelum mengambil keputusan.
- Tim keuangan membutuhkan daftar pengajuan yang disetujui tetapi belum dibayar.
- Organisasi memerlukan jejak audit untuk mengetahui siapa yang mengubah atau memutuskan sesuatu.

## Tujuan produk

1. Menyatukan pencatatan pengeluaran karyawan dalam satu tempat.
2. Mengurangi pengajuan tanpa bukti transaksi atau informasi wajib.
3. Memiliki proses persetujuan yang konsisten dan dapat dilacak.
4. Memudahkan tim keuangan menandai reimbursement dan mengekspor data.
5. Menyediakan ringkasan pengeluaran yang benar berdasarkan data database.

## Pengguna utama

- Karyawan: membuat pengeluaran pribadi dan laporan biaya.
- Manajer/approver: memeriksa laporan anggota tim dan menyetujui atau menolak.
- Keuangan: melihat laporan yang disetujui dan mencatat reimbursement.
- Administrator: mengelola pengguna, kategori, dan pengaturan dasar.

## Prinsip produk

- **Traceable:** tindakan penting mempunyai pencatat, waktu, dan konteks.
- **Least privilege:** pengguna hanya melihat dan mengubah data yang diizinkan.
- **Clear state:** setiap pengeluaran/laporan mempunyai status yang mudah dimengerti.
- **Low friction:** form ringkas, validasi dekat dengan input, unggah bukti mudah.
- **Responsive:** dapat digunakan di desktop dan layar ponsel.
- **Data integrity:** status, nominal, lampiran, dan reimbursement konsisten di database.

## Indikator keberhasilan MVP

- Pengguna dapat menyelesaikan alur membuat pengeluaran sampai laporan diajukan tanpa bantuan administrator.
- Manajer hanya dapat memutuskan laporan yang memang berada dalam kewenangannya.
- Laporan yang ditolak wajib memiliki alasan.
- Tim keuangan dapat menandai laporan disetujui sebagai telah diganti dan melihat tanggal serta referensi pembayaran.
- Ringkasan dashboard cocok dengan hasil query untuk rentang waktu dan filter yang sama.
- Tidak ada akses ke data/lampiran lintas pengguna melalui manipulasi ID.

## Acuan fitur

Fitur utama terinspirasi dari pola umum pada [Odoo Expenses](https://www.odoo.com/app/expenses-features), khususnya pencatatan bukti, laporan pengeluaran, persetujuan, komentar, dan pelacakan status. GT Expense tetap memakai nama, identitas, layout, dan implementasi sendiri.

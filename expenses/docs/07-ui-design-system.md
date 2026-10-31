# 07 — UI Design System

## Arah visual

GT Expense menggunakan gaya **modern, rapi, percaya diri, dan profesional**, dengan tipografi yang mudah dibaca, ruang kosong cukup, panel/kartu putih, border halus, dan hirarki informasi yang jelas. Hindari dekorasi berlebihan, gradien berat, dan dashboard penuh widget yang tidak membantu keputusan.

## Token warna

| Token | Nilai | Penggunaan |
|---|---|---|
| Primary | `#2563EB` | CTA utama, link, state aktif, focus ring |
| Primary Hover | `#1D4ED8` | Hover CTA |
| Primary Subtle | `#EFF6FF` | Highlight lembut dan latar badge netral-biru |
| Page Background | `#F8FAFC` | Latar halaman |
| Surface | `#FFFFFF` | Card, panel, tabel, modal |
| Text Primary | `#0F172A` | Heading dan teks utama |
| Text Secondary | `#64748B` | Deskripsi dan metadata |
| Border | `#E2E8F0` | Border input/card/divider |
| Success | `#15803D` | Status sukses seperti `APPROVED` atau `PAID` |
| Warning | `#B45309` | Status menunggu atau perhatian |
| Danger | `#B91C1C` | Reject, error, destructive actions |
| Info | `#0369A1` | Informasi proses |

Jangan mengandalkan warna sebagai satu-satunya indikator. Pasangkan warna dengan label teks dan, bila berguna, ikon.

## Tipografi dan jarak

- Gunakan font system stack agar halaman cepat dan stabil.
- Heading halaman: sekitar 1.6–2rem desktop, responsif di mobile.
- Body: 0.925–1rem; metadata minimal tetap terbaca.
- Gunakan sistem jarak konsisten berdasarkan kelipatan 4/8px.
- Form field mempunyai tinggi sentuh yang memadai; tombol primer paling menonjol, aksi destruktif tidak dijadikan CTA utama.

## Komponen

- Sidebar/nav dengan active state biru.
- Header halaman dengan judul, deskripsi singkat, dan CTA utama.
- Metric card untuk ringkasan dashboard.
- Table header yang jelas, zebra stripe opsional sangat halus, hover row, empty state.
- Status badge dipetakan dari enum uppercase: Draft, Menunggu Persetujuan, Disetujui, Ditolak, Dibukukan, Menunggu Pembayaran, Dibayar, Dibatalkan.
- Form controls memakai kelas Bootstrap dengan validasi server-side.
- Alert sukses/error mengandung teks yang menjelaskan hasil dan langkah berikutnya.
- Modal konfirmasi untuk hapus atau aksi penting; reject modal memuat field alasan wajib.
- File upload menampilkan nama/ukuran file dan status upload, tanpa membuka data privat lewat URL publik.

## Layout dashboard

1. Page header: “Dashboard” dan sapaan/label ruang kerja yang seperlunya.
2. Baris 3–4 metric cards sesuai role.
3. Area utama: grafik pengeluaran bulanan bila data memadai dan/atau daftar aktivitas terbaru.
4. Area antrean tindakan: laporan pending yang relevan dengan role.
5. Hindari menampilkan semua metrik global kepada Employee.

## Pola halaman

- List: toolbar filter/search → tabel → pagination.
- Detail laporan: identitas laporan dan status → tabel expense → total → kuitansi/link preview yang aman → komentar dan timeline → aksi sesuai izin.
- Detail expense: informasi transaksi → receipt tunggal → report/status yang terhubung → histori yang relevan.
- Mobile: navigasi dapat menjadi offcanvas Bootstrap; tombol penting mudah dijangkau; form tidak menggunakan layout dua kolom sempit.

## Aksesibilitas

- Label terhubung dengan input melalui `for`/`id`.
- Tombol mempunyai teks yang jelas atau `aria-label`.
- Gunakan outline focus yang terlihat.
- Error tidak disampaikan hanya dengan border merah.
- Pastikan modal mendukung keyboard sesuai Bootstrap.
- Hindari kontras rendah dan teks abu-abu terlalu tipis.

## CSS architecture

Simpan token di `public/assets/css/app.css` menggunakan CSS custom properties. Bootstrap dipakai untuk grid/utilities/components; CSS proyek hanya menangani brand token, layout khusus, dan penyesuaian komponen. Jangan menulis ulang seluruh framework Bootstrap.


## Pemetaan status database ke UI

| Status database | Label Bahasa Indonesia |
|---|---|
| `DRAFT` | Draft |
| `SUBMITTED` | Menunggu persetujuan |
| `APPROVED` | Disetujui |
| `REFUSED` | Ditolak |
| `POSTED` | Dibukukan |
| `REIMBURSEMENT_PENDING` | Menunggu pembayaran |
| `PAID` | Dibayar |
| `CANCELLED` | Dibatalkan |

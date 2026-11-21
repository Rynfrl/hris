# 05 — Workflows and State Machines

## Model Workflow Terkonsolidasi (Consolidated Expense Management)

Sistem menerapkan arsitektur workflow terpusat:
`ADMIN EXPENSE → MANAGER EXPENSE → FINANCE`

Peraturan Bisnis Pokok:
1. Karyawan biasa tidak lagi membuat pengajuan expense mandiri. Seluruh pencatatan dan pelaporan dikelola terpusat oleh **Admin Expense**.
2. **Laporan Gabungan (Consolidated Report):** Admin Expense dapat memasukkan banyak karyawan lintas departemen dalam 1 laporan gabungan (misal EXP-2026-001 memuat Andi Pratama - IT, Budi Santoso - HR, Citra Amelia - Finance).
3. **Persetujuan Manajer (Manager Expense):** Manager memeriksa dan memberikan satu keputusan utuh (`APPROVE` atau `REFUSE`) pada level laporan, bukan keputusan terpisah per karyawan. Penolakan wajib menyertakan alasan penolakan.
4. **Pembukuan Keuangan (Finance Posting):** Finance membukukan satu laporan yang telah disetujui manajer menjadi 1 jurnal posting.
5. **Reimbursement Penuh Tunggal:**
   **1 LAPORAN = 1 REIMBURSEMENT = 1 PEMBAYARAN PENUH.**
   Finance mencairkan total dana seluruh pengeluaran dalam satu laporan sekaligus kepada penanggung jawab / rekening pencairan. Sistem secara tegas melarang pembayaran parsial/bertahap.

## Status database

Status pada `expenses` dan `expense_reports` memakai enum uppercase yang disediakan database: `DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`.

## 1. Siklus report dan expense

```text
DRAFT → SUBMITTED → APPROVED → POSTED → REIMBURSEMENT_PENDING → PAID
                    ↘ REFUSED → DRAFT
```

`CANCELLED` hanya boleh dipakai pada pembatalan yang sah sebelum pembayaran selesai. Transisi harus eksplisit di service; tidak boleh ada update status bebas dari form.

- Expense dicatat oleh Admin Expense atas nama karyawan terkait berstatus `DRAFT`.
- Menambahkan peserta karyawan dicatat pada `expense_report_employees`.
- Transaksi pengeluaran dikaitkan ke laporan gabungan melalui `expense_report_items`.
- Saat report disubmit oleh Admin Expense, status report dan seluruh item expense berubah ke `SUBMITTED`.
- Manager Expense yang berwenang memeriksa dan menyetujui (`APPROVED`) atau menolak (`REFUSED`) report secara utuh.
- Report yang ditolak dapat direvisi oleh Admin Expense kembali ke `DRAFT`; histori penolakan tetap tersimpan pada `expense_approvals`/audit log.
- Finance mem-posting report yang disetujui, memasukkannya ke antrean reimbursement tunggal, lalu mencatat pembayaran lunas penuh.

## 2. Transisi report

| Aksi | Status awal | Status akhir | Pelaku | Syarat |
|---|---|---|---|---|
| Buat report gabungan | — | `DRAFT` | Admin Expense | Peran ADMIN / ADMIN_EXPENSE |
| Tambah peserta karyawan | `DRAFT` | `DRAFT` | Admin Expense | Karyawan aktif, belum ada dalam laporan yang sama |
| Tambah transaksi karyawan | `DRAFT` | `DRAFT` | Admin Expense | Karyawan terdaftar dalam laporan, kategori aktif |
| Submit | `DRAFT` | `SUBMITTED` | Admin Expense | Minimal satu transaksi, total valid > 0 |
| Approve | `SUBMITTED` | `APPROVED` | Manager Expense | Peran MANAGER / izin `expense.approve` |
| Refuse | `SUBMITTED` | `REFUSED` | Manager Expense | Alasan penolakan wajib diisi (minimal 5 karakter) |
| Revisi | `REFUSED` | `DRAFT` | Admin Expense | Membuka kunci revisi laporan dan transaksi |
| Post (Pembukuan) | `APPROVED` | `POSTED` | Finance | Izin `expense.post` |
| Masukkan antrean reimbursement | `POSTED` | `REIMBURSEMENT_PENDING` | Finance | 1 record reimbursement tunggal per report |
| Pembayaran Penuh | `REIMBURSEMENT_PENDING` | `PAID` | Finance | 1 kali bayar penuh 100% nominal laporan; status report & expense otomatis `PAID` |
| Batalkan draft | `DRAFT` | `CANCELLED` | Pemilik/administrator berwenang | Belum pernah diajukan |

Tidak ada transisi langsung dari `DRAFT` ke `APPROVED`, `SUBMITTED` ke `POSTED`, atau status belum dibayar ke `PAID`.

## 3. Submit

Sebelum submit, server harus:

1. Memastikan pemilik report adalah employee yang terhubung dengan user aktif.
2. Memastikan report `DRAFT` dan berisi minimal satu expense.
3. Memastikan setiap expense dimiliki employee yang sama, berstatus `DRAFT`, dan tidak terkait report lain.
4. Memastikan kategori aktif, tanggal valid, deskripsi wajib terisi, nominal positif, receipt memenuhi kebijakan, dan total konsisten.
5. Menghitung total dari tabel relasi dan expense di database; jangan menerima total dari browser.
6. Memperbarui report, item expenses, `total_amount`, `submitted_at`, approval history, activity log, dan audit log dalam satu transaksi.
7. Menulis aksi `SUBMITTED` di `expense_approvals` dan membuat notifikasi untuk manager yang terhubung.

## 4. Approval/rejection

- Hanya report `SUBMITTED` yang bisa diputuskan.
- Aktor harus memiliki permission `expense.approve`, role `MANAGER`, serta merupakan manajer langsung employee pemilik report berdasarkan `employees.manager_id` dan `users.employee_id`.
- Approve tidak menerima nominal/status dari client. Isi `approved_by`/`approved_at` pada expenses dan `approved_at` pada report.
- Refuse mensyaratkan alasan nonkosong; isi `rejected_by`, `rejected_at`, dan `rejection_reason` pada expense serta `refused_at`/`rejection_reason` pada report.
- Simpan aktor, waktu, aksi, dan alasan ke `expense_approvals`; catat event relevan pada `activity_logs` dan `audit_logs`.
- Semua status yang saling terkait berubah secara atomik.

## 5. Revisi setelah ditolak

- Pemilik dapat mengubah report `REFUSED` kembali menjadi `DRAFT`.
- Expense pada report kembali ke `DRAFT` dan kolom penolakan aktif boleh dibersihkan untuk memulai pengajuan baru.
- Jangan menghapus record historis di `expense_approvals`, `activity_logs`, atau `audit_logs`.
- Report hanya dapat diajukan ulang setelah validasi seluruh item dilakukan ulang.

## 6. Posting dan reimbursement

- Finance hanya dapat posting report `APPROVED` dengan permission `expense.post`.
- Setelah posting (`POSTED`), finance dapat membuat record `reimbursements` tunggal dan mengubah status report/expenses ke `REIMBURSEMENT_PENDING`.
- Record reimbursement menggunakan status sendiri: `PENDING`, `PROCESSING`, `PAID`, `FAILED`, atau `CANCELLED`.
- Penandaan lunas harus mengunci report dan reimbursement, memverifikasi belum dibayar, menyimpan `payment_method`, `payment_reference`, `payment_date`, `processed_by`, dan `processed_at`, lalu menyetel report serta expense ke `PAID` dan mengisi `paid_by`/`paid_at` pada expense.
- Gunakan transaksi dan unique constraint migration pada `reimbursements.report_id` agar tidak terjadi pembayaran ganda. Catat aksi dengan `expense_approvals`, `activity_logs`, dan `audit_logs`.
- Setelah `PAID`, nilai dan item tidak dapat diedit melalui alur normal.

## 7. Penghapusan dan histori

- Expense hanya dapat dihapus saat `DRAFT` dan belum terhubung pada report; jika berada dalam report draft, lepas melalui aksi remove item yang sah terlebih dahulu.
- Report draft boleh dibatalkan/dihapus sesuai kebijakan. Report yang pernah disubmit tidak boleh hard delete melalui UI.
- Kategori dan akun yang telah dipakai dinonaktifkan, bukan dihapus permanen.
- Receipt tunggal hanya dapat diganti/dihapus ketika expense `DRAFT` dan user berwenang.

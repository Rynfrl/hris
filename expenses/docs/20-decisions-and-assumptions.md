# 20 — Decisions and Assumptions Log

Dokumen ini menyimpan keputusan produk/teknis yang memengaruhi implementasi. Tambahkan entri saat ada perubahan yang disetujui.

## Keputusan default MVP

| ID | Keputusan | Alasan |
|---|---|---|
| D-001 | Bahasa UI Indonesia | Target penggunaan awal |
| D-002 | Currency hanya IDR | Membatasi kompleksitas kurs dan pembulatan |
| D-003 | PHP native tanpa framework | Persyaratan stack |
| D-004 | Bootstrap 5.3 + JS native tanpa jQuery | Persyaratan stack frontend |
| D-005 | Gunakan database `erp_database` dari SQL pengguna | Menjaga integrasi dengan skema existing dan menghindari tabel duplikat |
| D-006 | Satu perusahaan per instalasi GT Expense | Scope MVP |
| D-007 | Report adalah unit approval | Keputusan dibuat atas kumpulan expense |
| D-008 | Manager scope berdasarkan `employees.manager_id` | Kolom manager berada di employee, bukan users/report |
| D-009 | Finance menjalankan posting dan mencatat pembayaran secara manual | Tidak ada integrasi bank/jurnal eksternal |
| D-010 | Satu receipt per expense untuk MVP | Skema baseline menyediakan satu set kolom `receipt_*` |
| D-011 | CSV sebagai format ekspor pertama | Tanpa library eksternal |
| D-012 | Gunakan `expense_approvals`, `activity_logs`, `audit_logs`, dan `notifications` yang sudah tersedia | Tidak menduplikasi history/audit core tables |
| D-013 | Migration tambahan menegakkan satu expense per report dan satu reimbursement per report | Constraint baseline tidak cukup menjamin aturan tersebut |
| D-014 | Tabel `expense_comments` ditambahkan via migration terpisah | Schema baseline tidak memiliki komentar umum report |
| D-015 | Role `EMPLOYEE` serta permission posting/reimbursement ditambahkan oleh seed | Role/permission baseline belum melengkapi kebutuhan GT Expense |
| D-016 | Integrasi Chart.js 4.4 UMD mandiri (offline) untuk visualisasi Dashboard & Analitik | Menyediakan visualisasi tren bulanan dan komposisi kategori yang interaktif tanpa menambah dependensi npm/Composer atau CDN eksternal di runtime |
| D-017 | Penambahan kolom pilihan bank (`bank_name VARCHAR(100) NULL`) di tabel expenses via Migration 002 | Memfasilitasi pilihan bank transaksi/rekening pembayaran saat pencatatan dan perbaikan pengeluaran (default sesuai bank profil karyawan) |
| D-018 | Penghapusan fitur tambah pengeluaran dan buat laporan pada peran MANAGER | Memastikan pemisahan peran yang tegas (*strict segregation*); Manajer bertindak murni sebagai peninjau dan pemberi persetujuan (*Approver*) atas pengajuan tim bawahan |

## Asumsi yang perlu dikonfirmasi sebelum skala besar

- Apakah satu employee dapat memiliki beberapa akun login atau beberapa approver?
- Apakah expense non-reimbursable masuk ke report yang sama, atau hanya expense reimbursable yang dapat diajukan?
- Apakah receipt wajib untuk seluruh kategori atau hanya di atas ambang tertentu?
- Apakah Finance memerlukan persetujuan terpisah sebelum pembayaran?
- Apakah posting di GT Expense perlu membuat jurnal/accounting entry di modul ERP lain?
- Apakah diperlukan retensi dokumen/audit sesuai kebijakan legal internal?
- Apakah kelak perlu multiple receipts, multi-currency, pajak, atau integrasi payroll?

## Batasan teknis sumber database

- SQL baseline menggunakan nama kolom dan status enum spesifik. Jangan mengubahnya hanya untuk mencocokkan kode.
- SQL baseline tidak aman dijalankan ulang tanpa kontrol karena beberapa tabel di-drop sedangkan tabel lain dibuat tanpa drop.
- SQL baseline tidak menyediakan akun demo.
- Migration integritas memiliki precheck sebelum menambahkan unique index; jangan menghapus duplikat lama otomatis.

## Aturan perubahan

- Jangan mengubah status workflow, role scope, atau skema utama secara diam-diam.
- Setiap perubahan mencatat konteks, keputusan, dan dampak ke PRD, database, workflow, routes, security, dan test.
- Bila asumsi belum dikonfirmasi, pilih opsi aman dengan cakupan terkecil dan dokumentasikan.

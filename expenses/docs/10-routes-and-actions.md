# 10 — Routes and Actions

Gunakan satu front controller `public/index.php` dan route eksplisit berdasarkan HTTP method. Nama path boleh disesuaikan, namun kontrak fungsi dan kontrol akses harus dipertahankan. Semua operasi perubahan data menggunakan POST + CSRF.

## Auth

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/login` | Form login | Guest |
| POST | `/login` | Validasi kredensial/login | Guest + rate limit |
| POST | `/logout` | Logout dan hapus sesi | Auth + CSRF |

## Dashboard

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/dashboard` | Ringkasan sesuai role dan scope | Auth |

## Expenses

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/expenses` | Daftar pengeluaran dengan filter/pagination | Auth + record scope |
| GET | `/expenses/create` | Form expense | Auth + employee mapping aktif |
| POST | `/expenses` | Simpan expense draft | Auth + CSRF |
| GET | `/expenses/{id}` | Detail expense | Auth + record authorization |
| GET | `/expenses/{id}/edit` | Form edit draft | Pemilik + status `DRAFT` |
| POST | `/expenses/{id}/update` | Update draft | Pemilik + `DRAFT` + CSRF |
| POST | `/expenses/{id}/delete` | Hapus expense belum terikat report | Pemilik + rules + CSRF |
| POST | `/expenses/{id}/receipt` | Unggah/ganti receipt tunggal | Pemilik + `DRAFT` + CSRF |
| GET | `/expenses/{id}/receipt` | Preview/download receipt privat | Record authorization |
| POST | `/expenses/{id}/receipt/remove` | Hapus metadata/file receipt | Pemilik + `DRAFT` + CSRF |

## Reports

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/reports` | Daftar report sesuai role/scope | Auth + scope |
| GET | `/reports/create` | Form report | Auth + employee mapping aktif |
| POST | `/reports` | Buat report draft | Auth + CSRF |
| GET | `/reports/{id}` | Detail report, items, timeline | Auth + record authorization |
| GET | `/reports/{id}/edit` | Edit metadata report draft | Pemilik + `DRAFT` |
| POST | `/reports/{id}/update` | Update title/description/date range | Pemilik + `DRAFT` + CSRF |
| POST | `/reports/{id}/items` | Tambah expense draft | Pemilik + report `DRAFT` + CSRF |
| POST | `/reports/{id}/items/{expenseId}/remove` | Lepas expense dari report draft | Pemilik + report `DRAFT` + CSRF |
| POST | `/reports/{id}/submit` | Ajukan report | Pemilik + workflow validation + CSRF |
| POST | `/reports/{id}/revise` | Kembalikan report `REFUSED` ke draft | Pemilik + CSRF |
| POST | `/reports/{id}/comments` | Tambah komentar umum | User yang dapat mengakses report + CSRF |

## Approval

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/approvals` | Antrean report `SUBMITTED` | Manager + permission `expense.approve` |
| POST | `/reports/{id}/approve` | Approve report | Manager berwenang + `SUBMITTED` + CSRF |
| POST | `/reports/{id}/refuse` | Tolak dengan alasan | Manager berwenang + `SUBMITTED` + CSRF |

## Finance/posting/reimbursement

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/finance/posting` | Daftar report `APPROVED` | Finance + `expense.post` |
| POST | `/reports/{id}/post` | Post report approved | Finance + `APPROVED` + CSRF |
| GET | `/reimbursements` | Daftar report yang siap diproses | Finance + `expense.reimburse` |
| POST | `/reports/{id}/reimbursement` | Masukkan report `POSTED` ke antrean pembayaran | Finance + CSRF |
| POST | `/reimbursements/{id}/processing` | Set status proses pembayaran | Finance + CSRF |
| POST | `/reimbursements/{id}/paid` | Catat pembayaran final | Finance + CSRF |
| POST | `/reimbursements/{id}/failed` | Catat kegagalan pembayaran | Finance + CSRF |
| GET | `/reimbursements/history` | Riwayat pembayaran | Finance/Admin sesuai permission |

## Admin/master data

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET/POST | `/settings/users` | Daftar/buat user | Admin; POST CSRF |
| POST | `/settings/users/{id}/update` | Update akun/role/employee mapping/aktif | Admin + CSRF |
| GET/POST | `/settings/categories` | Daftar/buat kategori | Admin + CSRF untuk POST |
| POST | `/settings/categories/{id}/update` | Update/nonaktifkan kategori | Admin + CSRF |
| GET/POST | `/settings/departments` | Daftar/buat departemen bila disertakan di MVP | Admin + CSRF untuk POST |
| POST | `/settings/departments/{id}/update` | Update/nonaktifkan departemen | Admin + CSRF |
| GET | `/settings/audit-logs` | Lihat audit log | Admin + permission |

## Reporting

| Method | Path | Aksi | Akses |
|---|---|---|---|
| GET | `/analytics` | Laporan agregat terfilter | Role dan scope sesuai izin |
| GET | `/exports/expenses.csv` | Ekspor expense CSV | Role/scope sesuai izin |
| GET | `/exports/reports.csv` | Ekspor report CSV | Role/scope sesuai izin |

## Aturan router

- Validasi ID sebagai integer positif.
- Route tidak dikenal menghasilkan 404; method salah tidak boleh dieksekusi sebagai aksi lain.
- Sort key dipetakan ke daftar kolom tetap; jangan menyisipkan input pengguna ke SQL.
- Jangan melakukan perubahan data melalui GET.
- Setiap endpoint menjalankan autentikasi, permission check, record-level authorization, dan CSRF untuk request state-changing.
- Resolusi path menggunakan class helper `App\Core\Url::to()` yang secara otomatis mendeteksi base path, sehingga bekerja transparan baik saat diakses secara mandiri (`http://127.0.0.1:8000/`) maupun sebagai sub-modul terintegrasi (`http://localhost/hris/expenses/`).
- Sesudah POST sukses, gunakan redirect + flash message. Unduh receipt harus mengambil path dari record database setelah izin diverifikasi, bukan dari parameter path bebas.

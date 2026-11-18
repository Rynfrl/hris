# 11 — Security Requirements

Dokumen ini adalah persyaratan minimum, bukan pengganti security review.

## Autentikasi dan sesi

- Hash password dengan `password_hash()` dan verifikasi dengan `password_verify()`.
- Jangan menyimpan password plaintext atau membuat hash sendiri.
- Pesan login gagal generik; pertimbangkan rate limit/backoff untuk login gagal.
- Setelah login, panggil `session_regenerate_id(true)`.
- Nama cookie session terstandarisasi: `expenses_session` untuk mendukung pembagian sesi SSO (*Single Sign-On*) dengan portal utama HRIS.
- Cookie session production harus `HttpOnly`, `Secure` saat HTTPS, dan `SameSite=Lax` atau lebih ketat sesuai kebutuhan.
- Terapkan timeout idle dan logout server-side yang jelas.
- Regenerasi/invalidasi sesi setelah perubahan kredensial/role yang relevan.

## Otorisasi

- Default deny: route/action harus diizinkan secara eksplisit.
- Periksa role dan kepemilikan/scope pada server untuk setiap permintaan.
- Periksa ulang akun aktif; jangan hanya mengandalkan role yang mungkin tersimpan lama di sesi.
- Lindungi halaman detail, update, delete, download, komentar, export, dan aksi workflow.
- Gunakan relasi database tervalidasi; jangan percaya `employee_id`, `created_by`, `role_id`, status, atau identitas manajer dari form. Pemilik report diwakili `expense_reports.employee_id`; manager berasal dari `employees.manager_id`.

## CSRF

- Token acak per session/strategi yang aman, disisipkan pada seluruh form POST state-changing.
- Validasi token di server dengan perbandingan aman (`hash_equals`).
- Logout juga POST + CSRF.
- GET hanya untuk membaca dan tidak boleh mengubah state.

## SQL injection

- PDO prepared statements untuk semua nilai input.
- Tidak melakukan concatenation nilai pengguna ke SQL.
- Identifier sort/group-by yang tidak bisa di-bind harus dipetakan dari allowlist tetap.
- Hindari query dinamis yang mematikan parameter binding.

## XSS dan output

- Escape string saat dirender ke HTML (`htmlspecialchars` dengan flag yang sesuai dan UTF-8).
- Jangan memasukkan data user langsung ke `innerHTML`; gunakan `textContent` atau API DOM aman.
- Untuk atribut/URL, validasi konteks output secara khusus.
- Jangan render HTML mentah dari komentar atau deskripsi.

## Upload kuitansi

- Allowlist MIME yang diperiksa dengan `finfo`: JPEG, PNG, WebP, PDF.
- Batas ukuran default 5 MB per receipt; boleh dikonfigurasi.
- Abaikan ekstensi/MIME browser sebagai sumber kebenaran.
- Beri nama penyimpanan acak, pisahkan nama asli untuk tampilan, dan cegah path traversal.
- Simpan di luar public document root; unduh lewat controller yang melakukan otorisasi.
- Jangan mengizinkan PHP/HTML/SVG/script/executable sebagai file bukti.
- Skema baseline mendukung satu receipt per expense. Tangani kegagalan sinkronisasi database/filesystem dengan mengganti file hanya setelah upload berhasil dan membersihkan file lama setelah transaksi commit.
- Saat menyajikan file, set Content-Type yang sudah diverifikasi, `X-Content-Type-Options: nosniff`, serta `Content-Disposition` yang aman. PDF dapat dipaksa sebagai attachment bila kebijakan lebih ketat.

## Validasi bisnis dan konsistensi

- Status workflow tidak boleh dipilih bebas dari browser.
- Validasi nominal, kategori aktif, ownership, manager scope, dan status terkini di server.
- Gunakan transaksi dan/atau locking sesuai kebutuhan untuk menghindari double approval/double reimbursement akibat request bersamaan.
- Gunakan unique constraint untuk nomor expense/report dan, setelah migration integritas GT Expense diterapkan, reimbursement per report serta satu report per expense.

## Ekspor dan logging

- CSV export harus menerapkan otorisasi serta filter yang sama dengan daftar.
- Cegah CSV formula injection: untuk cell teks berawalan `=`, `+`, `-`, `@`, tab, atau carriage return, terapkan strategi escaping yang aman.
- Jangan log password, token, cookie, full request body sensitif, atau file.
- Audit log dibatasi ke pihak berwenang dan tidak dapat diedit melalui UI umum.

## Konfigurasi dan server

- Tidak commit `.env`, kredensial, atau kuitansi.
- Debug dinonaktifkan di production.
- Pastikan document root menunjuk ke folder `public/`, bukan root proyek.
- Batasi akses HTTP langsung ke `storage/`, config, migrations, dan file environment.
- Wajib HTTPS untuk deployment yang dapat diakses melalui jaringan.
- Siapkan backup database dan storage, lalu uji pemulihannya.

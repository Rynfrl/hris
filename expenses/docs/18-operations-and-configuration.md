# 18 — Operations and Configuration

## Konfigurasi environment yang disarankan

`DB_DATABASE` harus menunjuk ke `erp_database`, yaitu database yang dibuat oleh SQL baseline. Jangan menggunakan nama database GT Expense terpisah untuk implementasi default.

Sediakan `.env.example` atau daftar environment variable setara, tanpa secret nyata:

```dotenv
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
SESSION_NAME=gt_expense_session
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_database
DB_USERNAME=gt_expense_app
DB_PASSWORD=change_me
UPLOAD_MAX_BYTES=5242880
STORAGE_PATH=../storage
```

Jika aplikasi tidak memakai parser `.env`, variabel harus disediakan oleh OS/web server. Jangan menyimpan rahasia ke source control.

## Logging

- Pisahkan application error log dan audit trail.
- Log error berisi timestamp, severity, route, request/correlation ID, dan konteks teknis minimum.
- Jangan log password, session ID, CSRF token, file contents, atau keseluruhan request body.
- Pada production, detail error hanya untuk log privat.

## Backup dan restore

Backup database dan `storage/receipts` sebagai satu kesatuan operasional. File storage tanpa metadata DB atau database tanpa file receipt dapat membuat bukti transaksi hilang.

Checklist backup:

- Tetapkan frekuensi, retensi, lokasi penyimpanan aman, dan akses terbatas.
- Enkripsi backup ketika disimpan/ditransfer jika sistem mendukung.
- Uji restore berkala ke lingkungan terpisah.
- Catat prosedur pemulihan dan urutan restore database/storage.

## Retensi

Tetapkan kebijakan organisasi terkait retensi kuitansi, audit logs, dan laporan. Jangan menghapus histori keuangan secara otomatis tanpa kebijakan eksplisit. Tabel `audit_logs` dan histori workflow tidak memiliki tombol delete untuk user umum.

## Metrik pemantauan

- Error 5xx dan exception DB.
- Kapasitas storage receipt dan jumlah upload gagal.
- Login gagal berulang.
- Durasi request pada list/report/export.
- Kegagalan migrasi/backup.

## Operasional akun

- Nonaktifkan akun karyawan yang keluar, jangan hard-delete histori.
- Tinjau role dan manager relation secara berkala.
- Ganti kredensial demo sebelum go-live.
- Pastikan minimal ada satu admin aktif.

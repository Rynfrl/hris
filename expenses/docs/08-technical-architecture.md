# 08 — Technical Architecture

## Prinsip

Arsitektur sederhana berlapis di PHP native: entry point/routing, middleware ringan, controller, service (aturan bisnis), repository (SQL), template/view, dan aset statis. Ini adalah struktur proyek internal, bukan framework.

## Struktur folder yang disarankan

```text
gt-expense/
├── app/
│   ├── Core/                 # Router, Database, Session, View, CSRF, Flash
│   ├── Middleware/           # AuthRequired, Role/permission checks
│   ├── Controllers/          # Request orchestration
│   ├── Services/             # Workflow, Authorization, Upload, CSV, Audit
│   ├── Repositories/         # PDO queries
│   └── Views/
│       ├── layouts/
│       ├── auth/
│       ├── dashboard/
│       ├── expenses/
│       ├── reports/
│       ├── approvals/
│       ├── reimbursements/
│       ├── settings/
│       └── errors/
├── config/                   # App and DB configuration from environment
├── database/
│   ├── erp_database.sql       # baseline dari pengguna; jangan re-import di runtime
│   ├── migrations/            # migration tambahan, dicatat dalam schema_migrations
│   └── seeds/                 # role/permission GT Expense + demo via PHP CLI
├── public/
│   ├── index.php             # Single entry point
│   ├── .htaccess             # If Apache is used
│   └── assets/{css,js,img}/
├── storage/
│   ├── receipts/              # Private; must not be web-accessible
│   └── logs/                  # Private application logs
├── bin/                      # Migration/seed/utility scripts
├── tests/                    # CLI tests or manual scenarios
├── .env.example
├── .gitignore
└── README.md
```

## Request flow

1. Web server directs request ke `public/index.php`.
2. Router mencocokkan HTTP method dan route yang eksplisit.
3. Session/CSRF/auth middleware dijalankan sesuai kebutuhan route.
4. Controller memvalidasi bentuk request, memanggil service, dan memilih response.
5. Service memeriksa aturan bisnis dan izin; repository menjalankan query PDO.
6. View menerima data terstruktur dan melakukan escaping saat output.
7. Aksi POST mengembalikan redirect dengan flash message atau response yang sesuai.

## Komponen inti

- `Database`: membuat PDO dengan exception mode, associative fetch, native prepared statements bila tersedia.
- `Router`: route eksplisit untuk method/path; 404 untuk route tidak dikenal; 405 bila path ada dengan method salah jika implementasinya memungkinkan.
- `Auth`: current user, login, logout, dan pemeriksaan akun aktif.
- `Csrf`: menghasilkan dan memvalidasi token session untuk request state-changing.
- `AuthorizationService`: pemeriksaan role dan record scope.
- `ExpenseService`: create/update/delete dan manajemen lampiran.
- `ReportWorkflowService`: submit, approve, reject, revise, serta perubahan status expense terkait.
- `ReimbursementService`: pencatatan pembayaran satu kali.
- `AuditService`: menulis event penting.
- `CsvExportService`: menghasilkan ekspor dengan pemeriksaan otorisasi dan mitigasi formula injection.

## Konfigurasi dan lingkungan

- Rahasia dan kredensial melalui environment variables, bukan commit ke repository.
- `APP_ENV=development|production`, `APP_DEBUG=false` untuk production.
- Konfigurasi timezone `Asia/Jakarta`.
- DB credentials di environment.
- Path penyimpanan kuitansi privat ditetapkan konfigurasi, berada di luar public document root.

## Database dan konsistensi

- Integrasikan ke `erp_database` yang disediakan pengguna; baca `docs/21-existing-database-integration.md` dan gunakan nama kolom/status nyata dari `database/erp_database.sql`. Jangan menciptakan versi kedua tabel expense.
- `expenses` terhubung ke `expense_reports` melalui `expense_report_items`; receipt tunggal menggunakan kolom `receipt_*`.
- User role dibaca melalui `users.role_id → roles.role_id`; employee ownership melalui `users.employee_id`; direct manager melalui `employees.manager_id`.
- Gunakan InnoDB.
- Foreign key untuk relasi penting.
- Transaksi untuk create report submit, approve/reject, dan mark reimbursement.
- Index pada kolom filter/status/pemilik/tanggal yang sering dipakai.
- Gunakan `DECIMAL(15,2)` untuk uang.
- Nomor laporan dibuat unik di server dan dijamin constraint database.

## Frontend

- Bootstrap 5.3 untuk layout, form, navbar, offcanvas, modal, badge, alert, pagination.
- Vanilla JS untuk interaksi kecil, validasi pengalaman pengguna, preview metadata file, dan konfirmasi.
- Jangan menjadikan validasi JS sebagai pengganti validasi PHP.
- Semua halaman penting tetap berfungsi saat JavaScript tidak aktif, kecuali interaksi tambahan yang memang non-esensial.

## Error handling

- Tampilkan pesan ramah dengan correlation/request ID opsional.
- Catat detail teknis di log privat tanpa password, session cookie, CSRF token, atau isi kuitansi.
- Gunakan halaman 403, 404, 419/CSRF expired, dan 500.
- Jangan menampilkan stack trace atau query SQL dalam production.

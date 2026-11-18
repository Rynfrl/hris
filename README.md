# HRIS & ERP Management System

Sistem Informasi Manajemen Sumber Daya Manusia (HRIS) dan Enterprise Resource Planning (ERP) terintegrasi berbasis web menggunakan PHP Native dan MySQL.

---

## 1. Arsitektur & Modul Sistem

Sistem ini menggabungkan modul HRIS inti dengan modul operasional bisnis ERP dalam satu database terpadu (`hris`):

- **Human Resource Management (HRIS)**
  - Dashboard eksekutif HR & ringkasan metrik pegawai
  - Manajemen Divisi & Jabatan (`divisi`, `departments`)
  - Manajemen Data Karyawan & Rekening Pegawai (`employees`)
  - Manajemen Penggajian (`payrolls`)
  - Manajemen Pengguna, Hak Akses, & Keamanan (`users`, `roles`, `permissions`)
- **ERP Modules**
  - **Purchasing** (`/purchasing`): Purchase Requisition (PR), Purchase Order (PO), Goods Receipt Note (GRN), Purchase Return, serta Vendor Invoices & Scorecard.
  - **Sales** (`/sales`): Manajemen pelanggan (`customers`), pencatatan pesanan penjualan (`sales_orders`), penerbitan faktur (`invoices`), dan laporan penjualan.
  - **Inventory** (`/Inventory`): Katalog master produk (`products`, `categories`), pelacakan pergerakan stok (`stock_movements`), laporan inventaris gudang.
  - **Expenses — GT Expense** (`/expenses`): Pencatatan biaya dinas (*expense claim*), unggah bukti kuitansi privat (*receipt*), pengelompokan laporan (*expense report*), alur persetujuan manajer (*approval/refusal*), pembukuan keuangan (*posting*), dan pencairan dana (*reimbursement*).
- **Autentikasi Terpadu & Keamanan**
  - Portal login tunggal (*Unified Login Portal*) di `index.php` dengan pengalihan otomatis (*role-based redirect*) dan pembagian sesi SSO (*Single Sign-On*).
  - Jejak audit dan log aktivitas menyeluruh (`activity_logs` & `audit_logs`).
  - Proteksi CSRF, Prepared Statements PDO/MySQLi, dan penanganan unggahan berkas privat.

---

## 2. Prasyarat Sistem (*System Prerequisites*)

- **PHP >= 8.2** (disarankan 8.2+ untuk kompatibilitas penuh seluruh fitur native dan modul Expenses).
  - Ekstensi PHP wajib aktif: `pdo`, `pdo_mysql`, `mysqli`, `fileinfo`, `mbstring`, `session`.
- **MySQL / MariaDB >= 8.0** (InnoDB, karakter `utf8mb4`).
- **Web Server**: Apache dengan modul `mod_rewrite` aktif (Laragon, XAMPP, atau Apache mandiri).
- **Web Browser Modern**: Chrome, Edge, Firefox, Safari.

---

## 3. Instalasi & Setup Database

### Langkah 1: Clone Repository
Letakkan folder proyek di direktori web root server lokal Anda (misal `C:\laragon\www\hris` atau `C:\xampp\htdocs\hris`):
```bash
git clone https://github.com/Rynfrl/hris.git
```

### Langkah 2: Buat & Impor Database
1. Buka MySQL CLI, phpMyAdmin, atau HeidiSQL.
2. Buat database baru bernama `hris`:
   ```sql
   CREATE DATABASE IF NOT EXISTS hris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Impor skema dan data awal dari file `Database/hris.sql` atau `hris.sql`:
   ```powershell
   mysql -u root -p hris < Database/hris.sql
   ```

### Langkah 3: Konfigurasi Koneksi Database
Periksa konfigurasi koneksi pada file `config/database.php`:
```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'hris',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];
```

Untuk modul **Expenses**, pastikan file konfigurasi `expenses/.env` telah terpasang (salin dari `expenses/.env.example` jika belum ada). Konfigurasi default di `expenses/.env` otomatis membaca database `hris`:
```ini
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
SESSION_NAME=expenses_session
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hris
DB_USERNAME=root
DB_PASSWORD=
UPLOAD_MAX_BYTES=5242880
STORAGE_PATH=../storage
```

---

## 4. Menjalankan Aplikasi

### Opsi A: Melalui Web Server Lokal (Apache / Laragon / XAMPP) — *Direkomendasikan*
1. Pastikan server Apache dan MySQL sedang berjalan.
2. Buka peramban (*browser*) dan akses portal utama:
   👉 **`http://localhost/hris/`**
3. Masuk menggunakan akun yang sesuai. Sistem akan secara otomatis mengarahkan Anda ke dashboard modul masing-masing:
   - Pengguna HR (`Admin_HR`) ➔ `http://localhost/hris/dashboard.php`
   - Pengguna Purchasing (`Admin_Purchase`, `Purchasing_Staff`) ➔ `http://localhost/hris/purchasing/dashboard.php`
   - Pengguna Sales (`Admin_Sales`, `Marketing_Staff`) ➔ `http://localhost/hris/sales/index.php`
   - Pengguna Inventory (`Admin_Warehouse`, `Warehouse_Staff`) ➔ `http://localhost/hris/Inventory/index.php`
   - Pengguna Expenses (`ADMIN`, `MANAGER`, `EMPLOYEE`, `FINANCE`) ➔ `http://localhost/hris/expenses/`

### Opsi B: Menjalankan Modul Expenses Standalone
Modul Expenses juga dapat dijalankan secara terpisah menggunakan server bawaan PHP:
```powershell
cd expenses
php -S 127.0.0.1:8000 -t public
```
Akses di peramban: **`http://127.0.0.1:8000`**

---

## 5. Ringkasan Akun Demo & Hak Akses

Berikut adalah daftar akun demo terdaftar di dalam database `hris`:

| Modul | Peran (*Role*) | Username | Email | Keterangan & Akses Utama |
|---|---|---|---|---|
| **Expenses** | `ADMIN` | `admin_gte` | `admin@example.test` | Master kategori, manajemen pengguna, jejak audit global |
| **Expenses** | `MANAGER` | `rina_manager` | `manager@example.test` | Peninjau & pemberi persetujuan (*Approve/Refuse*) laporan tim bawahan |
| **Expenses** | `EMPLOYEE` | `budi_employee` | `budi@example.test` | Pencatatan pengeluaran dinas, unggah struk, pengajuan laporan klaim |
| **Expenses** | `EMPLOYEE` | `sari_employee` | `sari@example.test` | Karyawan rekan kerja Budi, simulasi revisi laporan yang ditolak |
| **Expenses** | `FINANCE` | `dimas_finance` | `finance@example.test` | Pembukuan laporan (*Post*), pencairan dana reimbursement (*Mark Paid*) |
| **HRIS** | `Admin_HR` | `Yuraa_n` | `yudha@gmail.com` | Pengelolaan data karyawan, divisi, master user, payroll |
| **Purchasing** | `Admin_Purchase` | `saruma` | `salma@gmail.com` | Pengelolaan PR, PO transaksi pembelian, verifikasi vendor |
| **Purchasing** | `Purchasing_Staff` | `alfckqih` | `arafat@gmail.com` | Input purchase requisition & penerimaan barang (GRN) |
| **Sales** | `Admin_Sales` | `fayyaz` | `afsaal@gmail.com` | Pengelolaan pesanan penjualan, invoice, dan rekap omzet |
| **Sales** | `Marketing_Staff` | `zakiee` | `zakie@gmail.com` | Input order pelanggan & transaksi penjualan |
| **Inventory** | `Admin_Warehouse` | `masbiku` | `sabiq@gmail.com` | Master katalog produk, stok gudang, laporan mutasi barang |
| **Inventory** | `Warehouse_Staff` | `halimawan` | `thio@gmail.com` | Pencatatan penerimaan barang & pergerakan stok keluar/masuk |

> [!TIP]
> **Password Akun Expenses:** Seluruh 5 akun demo modul Expenses menggunakan kata sandi terstandarisasi: **`Password123!`** (disimpan menggunakan `password_hash()`).

---

## 6. Dokumentasi Modul Terkait

Untuk dokumentasi lebih mendalam mengenai masing-masing modul, silakan merujuk pada dokumen berikut:

- **Inventory Module:**
  - [Dokumentasi Modul Inventory](file:///D:/hris/Inventory/README.md)
- **Expenses Module (GT Expense):**
  - [Spesifikasi Modul Expenses](file:///D:/hris/expenses/README.md)
  - [Panduan Cara Menjalankan Expenses](file:///D:/hris/expenses/CARA_MENJALANKAN.md)
  - [Daftar Lengkap Akun & Fitur Expenses](file:///D:/hris/expenses/DAFTAR_AKUN_DAN_FITUR.md)
  - [Panduan Database Expenses](file:///D:/hris/expenses/database/README.md)
  - [Daftar Dokumen Spesifikasi (Manifest)](file:///D:/hris/expenses/MANIFEST.md)

---

## Lisensi

Proyek ini dikembangkan untuk kebutuhan internal organisasi dan portofolio pengembangan sistem enterprise terintegrasi.

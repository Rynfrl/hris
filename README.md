# HRIS & ERP Management System

Sistem Informasi Manajemen Sumber Daya Manusia (HRIS) dan Enterprise Resource Planning (ERP) berbasis web menggunakan PHP dan MySQL.

## Fitur Utama

- **Human Resource Management (HRIS)**
  - Manajemen Divisi & Jabatan
  - Manajemen Data Karyawan
  - Manajemen Akun & Hak Akses Pengguna (Role-based access)
  - Penggajian / Payroll Management
- **ERP Modules**
  - **Purchasing**: Manajemen pengadaan dan pesanan pembelian
  - **Sales**: Manajemen penjualan dan pencatatan transaksi
  - **Inventory**: Pengelolaan stok barang dan inventaris
  - **Expenses**: Pelacakan pengeluaran operasional
- **Autentikasi & Keamanan**
  - Manajemen sesi pengguna & verifikasi login
  - Activity log / audit trail

## Prasyarat Sistem

- PHP >= 8.0
- MySQL / MariaDB (disarankan versi 8.0+)
- Web Server (Apache / Nginx / Laragon / XAMPP)

## Instalasi & Menjalankan Program

1. **Clone repository:**
   ```bash
   git clone https://github.com/Rynfrl/hris.git
   ```

2. **Konfigurasi Database:**
   - Buat database baru bernama `hris` di MySQL / phpMyAdmin.
   - Import file skema dan data awal dari `Database/hris.sql` atau `hris.sql`.
   - Sesuaikan konfigurasi koneksi database di file `config/database.php`:
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

3. **Jalankan Aplikasi:**
   - Letakkan folder project di web root (misalnya `htdocs` atau Laragon `www`).
   - Buka browser dan akses melalui `http://localhost/hris`.

## Lisensi

Proyek ini dikembangkan untuk kebutuhan internal organisasi / portofolio.

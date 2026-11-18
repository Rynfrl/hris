# Modul Inventory (Manajemen Inventaris & Gudang)

Modul **Inventory** merupakan bagian terintegrasi dari sistem HRIS & ERP yang bertugas mengelola katalog produk, kategori barang, pergerakan stok keluar/masuk, serta laporan pergudangan.

---

## 1. Arsitektur & Integrasi Sistem

- **Berbagi Sesi Terpadu (*Shared Session*):** Modul Inventory berbagi sesi login terpadu dengan portal HRIS melalui `session.php`. Pengguna wajib melakukan autentikasi melalui portal utama di `http://localhost/hris/index.php`.
- **Database Bersama:** Menggunakan database tunggal `hris` (tabel `products`, `categories`, `stock_movements`, `suppliers`, `customers`).
- **Web Server & Routing:** Web server Apache wajib mengaktifkan modul `mod_rewrite` dan mengizinkan file `.htaccess` (*AllowOverride All*) agar permintaan langsung ke halaman antarmuka tetap tervalidasi melalui pemeriksaan sesi dan peran pengguna (*role guard*).

---

## 2. Inisialisasi Database

- **Instalasi Database Baru:**
  Cukup lakukan impor skema utama `Database/hris.sql` (atau `hris.sql` di root proyek) yang sudah mencakup seluruh tabel dan data modul Inventory.
- **Pembaruan Skema Bertahap (*Incremental Migration*):**
  Jika memperbarui database HRIS lama yang belum memiliki tabel Inventory, jalankan file migrasi:
  ```powershell
  Database/migrations/20261011_inventory_integration.sql
  ```
  > [!WARNING]
  > Jangan menjalankan kedua jalur skema secara bersamaan untuk mencegah konflik DDL.

---

## 3. Peran Pengguna (*Roles*) & Hak Akses

| Peran (*Role*) | Akses & Kewenangan Utama |
|---|---|
| **`Admin_Warehouse`** | Memiliki akses penuh terhadap pemeliharaan master produk (*tambah, ubah, hapus*), manajemen kategori, pelacakan mutasi barang, laporan gudang (*reports*), dan konfigurasi pengaturan (*settings*). |
| **`Warehouse_Staff`** | Memiliki akses operasional untuk melihat ringkasan dashboard gudang, menjelajahi katalog produk, dan mencatat mutasi/pergerakan stok (*stock movements*). |

---

## 4. Alur Pergerakan Stok (*Stock Ledger Flows*)

Setiap perubahan jumlah barang dicatat secara transaksional pada tabel `stock_movements`:
- **Penerimaan Barang Pembelian (*Goods Receipts / GRN*):** Penambahan stok yang berasal dari modul Purchasing secara otomatis membukukan mutasi stok masuk.
- **Pengiriman Pesanan Penjualan (*Sales Orders*):** Pengurangan stok yang berasal dari modul Sales secara otomatis membukukan mutasi stok keluar.
- **Penyesuaian Mandiri (*Manual Adjustment*):** Staf gudang dapat mencatat penyesuaian stok opname (*adjustment/in/out*) langsung melalui antarmuka modul Inventory.

---

## 5. Tautan Terkait

- [README Utama HRIS & ERP](file:///D:/hris/README.md)
- [Modul Expenses (GT Expense)](file:///D:/hris/expenses/README.md)

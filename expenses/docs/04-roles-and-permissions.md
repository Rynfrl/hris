# 04 — Roles and Permissions

## Role database

Role ditentukan lewat `users.role_id` → `roles.role_id`, bukan kolom `users.role`. Gunakan nama uppercase: `EMPLOYEE`, `MANAGER`, `FINANCE`, `ADMIN`. Role `EMPLOYEE` dan permission GT Expense tertentu ditambahkan oleh `database/seeds/gt_expense_roles_permissions.sql`.

| Kemampuan | EMPLOYEE | MANAGER | FINANCE | ADMIN |
|---|---:|---:|---:|---:|
| Lihat dashboard pribadi | Ya | Ya | Ya | Ya |
| Buat dan kelola expense pribadi yang masih draft | Ya | Tidak (Khusus persetujuan tim) | Jika memiliki employee mapping, untuk biaya sendiri | Jika memiliki employee mapping, untuk biaya sendiri |
| Buat dan ajukan report pribadi | Ya | Tidak (Khusus persetujuan tim) | Jika memiliki employee mapping | Jika memiliki employee mapping |
| Lihat report tim | Tidak | Ya, direct report | Untuk proses keuangan | Ya sesuai izin/audit |
| Approve/refuse report | Tidak | Ya, hanya laporan bawahan langsung dan permission `expense.approve` | Tidak | Tidak secara default |
| Posting report approved | Tidak | Tidak | Ya, permission `expense.post` | Tidak secara default |
| Memproses reimbursement / menandai paid | Tidak | Tidak | Ya, permission `expense.reimburse` | Tidak secara default |
| Kelola kategori dan akun pengguna | Tidak | Tidak | Tidak | Ya dengan permission terkait |
| Lihat audit log global | Tidak | Histori keputusan yang dapat diakses | Log proses keuangan yang relevan | Ya |
| Ekspor data | Data sendiri | Data tim dalam scope | Data untuk fungsi keuangan | Data global sesuai izin |

Role ERP lain seperti `HR`, `PURCHASING`, `WAREHOUSE`, dan `SALES` tidak otomatis memperoleh akses GT Expense. Permission tambahan harus diberikan secara eksplisit.

## Prinsip otorisasi

1. **Permission check** dan **record-level check** wajib dilakukan di server untuk setiap route/action.
2. Menyembunyikan tombol di UI bukan otorisasi.
3. Karyawan mengakses report miliknya melalui `users.employee_id = expense_reports.employee_id`.
4. Expense milik pengguna diverifikasi menggunakan `expenses.created_by = users.user_id` serta `expenses.employee_id = users.employee_id`.
5. Manajer hanya dapat melihat/memutuskan laporan ketika akun aktifnya terpetakan ke employee yang ditunjuk oleh `employees.manager_id` pada employee pemilik report.
6. Finance dapat mengakses data yang diperlukan untuk posting/reimbursement, tetapi tidak dapat mengubah keputusan approval.
7. Admin mengelola akun dan master data. Hak admin tidak otomatis membuatnya approver atau finance.
8. Akun nonaktif harus ditolak pada login dan semua request privat, termasuk saat sesi masih ada.
9. Endpoint receipt dan ekspor CSV harus menjalankan pemeriksaan akses yang sama ketatnya dengan halaman detail.

## Pola implementasi

Buat `AuthorizationService` dengan fungsi semantik seperti `canViewExpense(user, expense)`, `canEditExpense(user, expense)`, `canViewReport(user, report)`, `canDecideReport(user, report)`, `canPostReport(user, report)`, dan `canReimburseReport(user, report)`. Fungsi dapat mengambil `user_id`, `employee_id`, `role_name`, serta permission dari database/session tervalidasi.

## Skenario wajib diuji

- Employee A tidak dapat membuka expense/report/receipt Employee B.
- Manager A tidak dapat approve/refuse report employee di luar direct report-nya.
- Finance tidak bisa mengirim aksi approve melalui POST langsung.
- Employee tidak bisa memalsukan `employee_id`, `created_by`, `approved_by`, `paid_by`, `role_id`, atau `status` di request.
- Admin tanpa permission Finance tidak dapat posting atau menandai reimbursement paid.
- Role ERP yang tidak memiliki permission GT Expense ditolak di endpoint modul.

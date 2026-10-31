# 13 — Reporting and CSV Exports

## Dashboard measures

Semua angka harus dihitung dari database dan memakai definisi periode yang konsisten.

- **Expense draft:** count/sum `expenses.status = 'DRAFT'` dalam scope user.
- **Menunggu persetujuan:** report `expense_reports.status = 'SUBMITTED'` yang manager login-nya berwenang.
- **Menunggu posting Finance:** report berstatus `APPROVED`.
- **Menunggu reimbursement:** report berstatus `REIMBURSEMENT_PENDING` dan/atau reimbursement record berstatus `PENDING`/`PROCESSING`.
- **Telah dibayar:** report dan reimbursement record dengan status `PAID`; filter waktu gunakan `reimbursements.payment_date` atau `expense_reports.paid_at` dan beri label yang jelas.
- **Total pengeluaran periode:** total nilai expense menurut `expenses.expense_date` pada rentang yang dipilih. Jangan mencampur tanggal expense dengan tanggal pembayaran.

Status database disimpan uppercase (`DRAFT`, `SUBMITTED`, `APPROVED`, `REFUSED`, `POSTED`, `REIMBURSEMENT_PENDING`, `PAID`, `CANCELLED`). Label Indonesia hanya untuk UI.

## Halaman analytics

Filter minimum: tanggal mulai/akhir, status, kategori. Finance/Admin dapat melihat filter department/employee sesuai izin; Manager hanya melihat direct-report scope; Employee hanya melihat data sendiri.

Tampilkan total nominal, count expense dan report, breakdown bulanan, breakdown kategori, serta report terbaru/nilai tertinggi bila relevan. Gunakan join `expense_report_items` untuk agregasi item per report; jangan mengandalkan kolom `expenses.report_id` yang tidak ada.

Semua kartu, grafik, tabel, dan ekspor harus menggunakan filter dan authorization scope yang konsisten. Labelkan dasar tanggal dengan jelas (`expense_date`, `submitted_at`, `paid_at` atau `payment_date`).

## CSV expense

Header yang disarankan:

`expense_id, expense_number, expense_date, description, category_code, category_name, amount, currency, merchant, employee_number, employee_name, department_code, department_name, status, report_number, is_reimbursable, created_at`

## CSV report

Header yang disarankan:

`report_id, report_number, title, employee_number, employee_name, department_name, status, expense_count, total_amount, submitted_at, approved_at, posted_at, refused_at, rejection_reason, paid_at`

Kolom payment reference hanya disertakan dalam ekspor Finance yang berwenang.

## Ketentuan ekspor

- Export menggunakan filter yang sama dengan list yang relevan dan scope otorisasi sama.
- Terapkan batas hasil/streaming untuk menghindari penggunaan memori berlebihan.
- Set response headers CSV dan nama file yang aman, misalnya `gt-expense-reports-2026-10-11.csv`.
- Pastikan UTF-8; pertimbangkan BOM bila diperlukan untuk kompatibilitas spreadsheet.
- Escape CSV sesuai aturan tanda kutip, koma, dan baris baru.
- Mitigasi formula injection pada kolom teks.
- Jangan mengekspor password hash, path privat receipt, sesi, secret, atau audit detail yang tidak diperlukan.

## Batas MVP

CSV saja. PDF, Excel/XLSX, scheduled email report, accounting journal export, dan multi-currency berada di luar MVP.

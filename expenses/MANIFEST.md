# Expenses — Manifest File

Paket terdiri dari 28 file: 3 file root, 21 dokumen spesifikasi, dan 4 file database/setup.

## File root

- `README.md` — cara menggunakan paket, database yang disertakan, serta urutan pembacaan.
- `AGENTS.md` — aturan wajib untuk AI coding assistant.
- `MANIFEST.md` — daftar file paket.

## Database

- `database/erp_database.sql` — salinan SQL baseline yang diunggah pengguna.
- `database/README.md` — instruksi impor, warning destructive SQL, dan pemeriksaan schema.
- `database/seeds/gt_expense_roles_permissions.sql` — role/permission tambahan untuk Expenses.
- `database/migrations/001_gt_expense_integrity.sql` — unique index dan tabel komentar report.

## Dokumen spesifikasi (`docs/`)

1. `01-product-vision.md` — visi dan batasan produk.
2. `02-product-requirements.md` — PRD, ruang lingkup, kebutuhan fungsional/nonfungsional.
3. `03-feature-catalog.md` — katalog modul dan fitur.
4. `04-roles-and-permissions.md` — role, permission, dan record-level access.
5. `05-workflows-and-states.md` — state machine expense/report/reimbursement.
6. `06-information-architecture.md` — navigasi dan struktur layar.
7. `07-ui-design-system.md` — warna biru, komponen UI, status, dan aksesibilitas.
8. `08-technical-architecture.md` — struktur folder dan pola arsitektur PHP.
9. `09-database-schema.md` — skema aktual dan source-of-truth rules.
10. `10-routes-and-actions.md` — kontrak route dan action.
11. `11-security-requirements.md` — autentikasi, authorization, CSRF, upload, audit.
12. `12-validation-and-business-rules.md` — validasi field serta business logic.
13. `13-reports-and-exports.md` — dashboard, agregasi, dan CSV.
14. `14-testing-and-acceptance.md` — acceptance criteria dan security regression.
15. `15-setup-and-deployment.md` — setup lokal dan deployment.
16. `16-implementation-plan.md` — fase implementasi.
17. `17-demo-data.md` — akun/organisasi/data demo development.
18. `18-operations-and-configuration.md` — env, backup, logging, operasi.
19. `19-master-prompt-for-coding-ai.md` — prompt implementasi untuk AI.
20. `20-decisions-and-assumptions.md` — keputusan/ketidakpastian.
21. `21-existing-database-integration.md` — peta integrasi terhadap SQL yang diunggah.

## Catatan

`database/erp_database.sql` adalah file dari pengguna yang disertakan ke dalam paket. Jangan menjalankan ulang file baseline terhadap database berisi data. Baca `database/README.md` sebelum import.

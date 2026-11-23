# Inventory module

Inventory shares the HRIS login session and the `hris` MySQL database. Apache
must allow `.htaccess` overrides and have `mod_rewrite` enabled so direct
requests for static page URLs pass through the session and role checks.

For a new database, import `Database/hris.sql`. To update an existing HRIS
database, apply `Database/migrations/20261011_inventory_integration.sql` once
instead; do not apply both schema paths.

`Admin_Warehouse` can access product maintenance, reports, and settings.
`Warehouse_Staff` can view the dashboard and product catalog and record stock
movements. Both roles must sign in through the HRIS login page.
Stock changes are recorded in `stock_movements`; Purchasing goods receipts
and Sales order updates continue to use their existing shared-ledger flows.

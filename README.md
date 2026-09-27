# MATrack

MATrack is a plain PHP and PostgreSQL department materials inventory project.
The app currently includes Materials, Borrowers, and Transactions pages. Each
page uses the shared layout and keeps database operations in separate PHP
handlers.

## Project structure

```text
MATrack/
├── assets/css/style.css
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   └── migrations/
│       └── 001_add_material_color.sql
├── materials/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── borrowers/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── dashboard/
│   └── index.php
├── transactions/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   ├── delete.php
│   └── validation.php
├── index.php
└── README.md
```

## Connect to the shared PostgreSQL database

The app starts in frontend preview mode, so its pages can be opened before
PostgreSQL is ready. It displays sample materials, borrowers, and transactions;
create, edit, and delete submissions are not saved in preview mode. Once the
database is ready, set `DATABASE_ENABLED` to `true` in `config/app.php` and
configure `config/database.php` as follows:

If PostgreSQL is enabled but cannot be reached, list and dashboard pages fall
back to the sample preview data and show a connection error. Write operations
still require a working PostgreSQL connection and are never stored as demo CRUD.

1. Edit `config/database.php` with the PostgreSQL database name, user, password,
   and the Windows laptop's reachable IPv4 address.
2. Configure PostgreSQL on that laptop to accept connections from your Mac,
   allow the PostgreSQL port (normally `5432`) through its firewall, and make
   sure both computers can reach each other over the network.
3. Confirm that your partner has created the existing `materials` and
   `borrowers` tables, along with the provided `transactions` table. The
   existing `materials` table also needs the migration in
   `database/migrations/001_add_material_color.sql` applied once to add the
   nullable `color VARCHAR(30)` column. The
   Transactions page uses `transaction_id`, `material_id`, `borrower_id`,
   `transaction_type`, `quantity`, `transaction_date`, `expected_return_date`,
   `return_date`, `status`, and `notes`. Transaction types are `BORROWED`,
   `RETURNED`, `USED`, and `DISPOSED`; statuses are `ACTIVE`, `COMPLETED`,
   `OVERDUE`, and `CANCELLED`.
   The Borrowers page uses only `borrower_id`, `borrower_name`, `contact`, and
   `department`.
   The app expects string primary keys formatted as `MAT-001` and `BOR-001`,
   with transaction IDs formatted as `TRX-001`; foreign-key columns must use
   matching string types. This project does not alter the partner's schema.
4. From this folder, start PHP's local development server:

   ```sh
   php -S localhost:8000
   ```

5. Open `http://localhost:8000` in your browser.

The Materials, Borrowers, and Transactions pages display their records and open
right-side add/edit drawers. Each module submits to its own `create.php`,
`edit.php`, and `delete.php` handlers. Transaction records do not automatically
change material stock. The dashboard reads its counts, recent transactions,
and inventory status from PostgreSQL when the database is enabled. Successful
changes write directly to PostgreSQL. Session data is used only to show
validation errors and restore form values after a failed submission; it is not
used to store or simulate CRUD records. Do not commit real database passwords
to GitHub; replace the sample settings locally or move credentials to
environment variables before publishing a public repository.

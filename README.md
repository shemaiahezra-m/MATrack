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

The app starts in frontend demo mode, so its pages can be opened before
PostgreSQL is ready. It displays sample materials, borrowers, and transactions,
and does not save create, edit, or delete submissions. To connect the live database later, set
`DATABASE_ENABLED` to `true` in `config/app.php`, then follow these steps:

1. Edit `config/database.php` with the PostgreSQL database name, user, password,
   and the Windows laptop's reachable IPv4 address.
2. Configure PostgreSQL on that laptop to accept connections from your Mac,
   allow the PostgreSQL port (normally `5432`) through its firewall, and make
   sure both computers can reach each other over the network.
3. Confirm that your partner has created the existing `materials` and
   `borrowers` tables, along with the provided `transactions` table. The
   Transactions page uses `transaction_id`, `material_id`, `borrower_id`,
   `transaction_type`, `quantity`, `transaction_date`, `expected_return_date`,
   `status`, and `notes`. Transaction types are `ADDED`, `BORROWED`,
   `RETURNED`, and `USED`; statuses are `ACTIVE`, `RETURNED`, `OVERDUE`, and
   `CANCELLED`.
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
change material stock. With the database enabled, successful changes write
directly to PostgreSQL. Do not commit real database passwords to GitHub; replace
the sample settings locally or move credentials to environment variables before
publishing a public repository.

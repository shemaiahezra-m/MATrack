# MATrack

MATrack is a plain PHP and PostgreSQL department materials inventory project.
The app currently includes Materials and Borrowers CRUD pages. Each page has
add/edit dialogs and keeps its database operations in separate PHP handlers.

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
├── index.php
└── README.md
```

## Connect to the shared PostgreSQL database

The app starts in frontend demo mode, so its pages can be opened before
PostgreSQL is ready. It displays sample materials and borrowers, and does not
save create, edit, or delete submissions. To connect the live database later, set
`DATABASE_ENABLED` to `true` in `config/app.php`, then follow these steps:

1. Edit `config/database.php` with the PostgreSQL database name, user, password,
   and the Windows laptop's reachable IPv4 address.
2. Configure PostgreSQL on that laptop to accept connections from your Mac,
   allow the PostgreSQL port (normally `5432`) through its firewall, and make
   sure both computers can reach each other over the network.
3. Confirm that your partner has created the existing `materials` and
   `borrowers` tables. The Borrowers page uses only `borrower_id`,
   `borrower_name`, `contact`, and `department`.
4. From this folder, start PHP's local development server:

   ```sh
   php -S localhost:8000
   ```

5. Open `http://localhost:8000` in your browser.

The Materials and Borrowers pages display their records and open add/edit
dialogs. Each page submits to its own `create.php`, `edit.php`, and `delete.php`
handlers. With the database enabled, successful changes write directly to
PostgreSQL. Do not commit real database passwords to GitHub; replace the sample
settings locally or move credentials to environment variables before
publishing a public repository.

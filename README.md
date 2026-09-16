# Verve e-commerce

PHP and MySQL storefront with a separate admin panel. Prices display in Kenyan shillings (KSh).

## Run locally with XAMPP

1. Clone this repository into `C:\xampp\htdocs\verve-ecommerce`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `verve/sql/schema.sql` to create the database and demo data.
4. Copy `verve/config/database.example.php` to `verve/config/database.php`. Set your local database credentials in the copied file; Git ignores it.
5. If using a different folder or port, update `BASE_URL` in `verve/config/config.php`.
6. Open `http://localhost/verve-ecommerce/verve/`.

The admin login is at `/verve/pages/admin/login.php`, with demo credentials `admin@verve.test` / `Admin123!`. Change the demo password before deploying publicly.

See [the project guide](verve/README.md) for requirements and features. Payment processing, including M-Pesa, is not connected yet.

## Sharing

For a private GitHub repository, invite your friend through the repository's collaborator settings. They can clone it and follow the setup above. GitHub stores the source code; this PHP/MySQL application needs a PHP server and database to run, and cannot run on GitHub Pages.

Share only the included schema and demo data, not database exports containing real customers or orders. Keep API keys and other credentials out of source control.

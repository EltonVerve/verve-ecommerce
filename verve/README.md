# Verve — a general-purpose e-commerce store

A full online store built in plain PHP + MySQL (no framework), covering everything a real
shop needs: a browsable catalogue with filters, product variants (size/colour/etc.),
cart, checkout, order history, wishlist, product reviews, coupon codes, and a full admin
panel for managing products, categories, orders and customer messages.



## Requirements

- PHP 8.1+ with the `pdo_mysql`, `mbstring` and `gd` extensions (all standard in XAMPP/WAMP/MAMP)
- MySQL 5.7+ or MariaDB 10.3+
- Any local server stack: XAMPP, WAMP, MAMP, or `php -S` for quick testing

## Setup

1. **Create the database.** Import `sql/schema.sql` into MySQL (via phpMyAdmin, or
   `mysql -u root -p < sql/schema.sql`). It creates the `verve` database, every table,
   and some sample products/categories so the site isn't empty on first run.

2. **Set your database credentials.** Copy `config/database.example.php` to
   `config/database.php` (ignored by Git), then update `DB_HOST`,
   `DB_USER`, `DB_PASS` if they differ from the XAMPP defaults.

3. **Set your site URL.** Open `config/config.php` and update `BASE_URL` to match where
   you'll access the site, e.g. `http://localhost/verve`.

4. **Make the uploads folder writable.** `public/assets/products/` needs to be writable
   by the web server so the admin panel can save uploaded product photos.

5. **Open the site.** Visit `BASE_URL` in your browser for the storefront, or
   `BASE_URL/pages/admin/login.php` for the admin panel.

   Demo admin login: **admin@verve.test** / **Admin123!**
   (Change this password immediately in a real deployment — update it via SQL or add an
   admin "change password" flow before going live.)

## How the code is organised

```
config/     Site settings + the database connection
includes/   Shared HTML (header, footer, product card) + helper functions
models/     All database queries, grouped by subject (Product.php, Cart.php, Order.php…)
pages/      Every page a visitor sees — one file per page, plain PHP + HTML
actions/    Form handlers — never output HTML, just validate, save, and redirect
public/     CSS, JS, and uploaded product images
sql/        The database schema + starter data
```

Every page follows the same shape: `require config.php`, do a bit of PHP to fetch
what the page needs, then `require header.php` → HTML → `require footer.php`.
Every action follows the same shape: check the request, verify the CSRF token,
validate input, do the database work, then redirect with a flash message.

## Notes on what's "real" vs. demo

- **Payments** — checkout collects a payment method but doesn't process a real charge.
  Wire in a real payment gateway (Stripe, Paystack, etc.) inside `actions/place_order.php`.
- **Email** — password reset links and order confirmations are logged/flashed rather than
  emailed. Plug in a mailer (PHPMailer + SMTP, or an email API) where the code comments
  point it out in `actions/forgot_password.php`.
- **Images** — sample products use placeholder images (via placehold.co) until you upload
  real photos through the admin panel.

## Security features already built in

- Prepared statements everywhere (no raw SQL string concatenation)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- CSRF tokens on every form that changes data
- Session-based auth with a strict separation between customer and admin roles
- Guest orders are only viewable with a private, unguessable access token
- Contact form is rate-limited by session and by a hashed (never raw) IP address

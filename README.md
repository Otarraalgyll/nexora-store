# NEXORA STORE

A responsive dark-themed store built with native PHP, MySQL, Bootstrap 5 and JavaScript.

## Features

- Product catalog, categories, filters, pagination, search suggestions and quick view
- Customer registration, login, password reset, profile and saved address
- Persistent customer carts, guest carts, wishlists, reviews and coupons
- Transactional checkout, stock validation and order history
- Separate administrator access, product/image management, categories, customers, orders, coupons and messages
- Chart.js dashboard; responsive layouts and reduced-motion support

GCash, Maya and card options are **unpaid demonstrations**. They do not process payments or collect card details. Cash-on-delivery orders are recorded as due on delivery.

## XAMPP setup

1. Install XAMPP with PHP 8.1 or newer (the code uses PHP's `never` return type).
2. Copy this folder into `htdocs/nexora-store`.
3. Start Apache and MySQL from the XAMPP control panel.
4. Open `http://localhost/phpmyadmin`, choose **Import**, and select `database.sql`. The script creates `ecommerce_db` and sample products. Import into a fresh database.
5. `config/database.php` defaults to host `localhost`, database `ecommerce_db`, username `root`, and an empty password. These match a standard local XAMPP installation.
6. Open `http://localhost/nexora-store/`.
7. Open `http://localhost/nexora-store/admin/` and sign in with `admin@nexora.local` / `Nexora@2026!`. You must change this installation password before accessing management features.

For different credentials, create an untracked `config/local.php`:

```php
<?php
return [
    'host' => 'localhost',
    'name' => 'ecommerce_db',
    'user' => 'root',
    'password' => '',
    'port' => '3306',
];
```

Alternatively, set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` and `DB_PORT` environment variables. Local configuration takes precedence. Enable PDO MySQL, mbstring and fileinfo. Make `storage/` and both `uploads/` subdirectories writable by PHP.

## Local development server

With MySQL running and the schema imported, from the project directory:

```sh
php -d opcache.enable=0 -S 127.0.0.1:8080 -t . tools/router.php
```

Open `http://127.0.0.1:8080/`. The router blocks access to private directories and source exports, as Apache's `.htaccess` files do. Do not expose PHP's development server publicly.

## Password reset

In local mode, reset emails are written to the private `storage/mail.log` file. Read that file locally to retrieve a reset link; it is blocked from browser access and excluded from Git. Set `APP_URL` to your actual site URL so links point to the correct installation.

For a live installation, set `APP_ENV=production`, configure PHP's mail transport and `MAIL_FROM`, use HTTPS and dedicated database credentials, change the seeded admin password, and replace demonstration store policies with your actual policies. External fonts and Bootstrap, Font Awesome and Chart.js assets require internet access.

## Repository contents

`database.sql` is a clean schema with demonstration seed data, not an export of the running database. Local database configuration, reset emails and uploaded customer/store files are excluded from version control. Empty upload folders are retained through `.gitkeep` files.

## Checks

Syntax-check PHP files with `php -l`. The `tests/integration.php` and `tools/create-test-db.php` scripts support isolated integration testing; use a new database name ending in `_test` and review their instructions before running. Never run integration tests against a live store database.

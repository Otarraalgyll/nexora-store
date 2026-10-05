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

## Install as a phone app (PWA)

Open the store in Chrome on Android and choose **Add to Home screen → Install**, or use **Install Nexora** in the footer when Chrome offers it. On iPhone, open in Safari and use **Share → Add to Home Screen**. The app retains the same accounts, cart and backend as the website.

Installation requires HTTPS on a hosted site. Local development at `http://127.0.0.1:8080` also works; keep PHP and MySQL running in Termux to use that local app. A local installation does not make the store available to other phones. GitHub stores the code but does not host this PHP/MySQL backend.

The service worker caches only a public offline notice. Live products, accounts, carts, payments and orders require a connection to the server. Purchases are never queued or replayed offline. The manifest and service-worker URLs are relative to the installation, supporting both the server root and XAMPP's `/nexora-store/` folder.

## Realistic sample product imagery

The seeded catalog includes six AI-generated studio product photos matching the NEXORA collection. They are demo imagery, not official third-party product photographs. Generation prompts and provenance are in `docs/catalog-photography.md`.

For an existing installation, after copying the new image files, run `php tools/upgrade-catalog-photos.php`. This replaces only original sample SVG image references and removes their old gallery sketches. It preserves customer accounts, orders, prices, stock and uploaded product images. Fresh installations use the updated `database.sql` automatically.

The sample catalog contains 25 products across six categories. Existing installations can add the 13 extended demo entries with `php tools/expand-catalog.php`; repeat runs skip existing product names. Related sample models share the six category photographs.

### Distinct catalog update

The current catalog replaces the repeated models with 19 different products and locally saved images from DummyJSON, alongside the six original NEXORA samples. There are 25 active products with 25 unique main images. Source links are in `docs/catalog-sources.md`. For an older installation, run `php tools/distinct-catalog.php` after copying the files. It archives the known repeated demo listings and preserves historical orders. New imports already contain the distinct catalog; the older expansion script is not needed.

# LocalSpot — Find your next local spot

A local destination **content management system** for Angeles City, Pampanga, built on **SpotCore**, a small PHP MVC framework written from scratch for the IPT final project.

- Visitors browse published places, search by name or address, and filter by category and starting estimated budget.
- Each place page shows **Angeles City area weather** from the Open-Meteo API (no API key needed).
- Each place page shows payment options and an **Open in Google Maps** button. Payment info ("Cash and online payment (GCash and other e-wallets)") is the developer's own local knowledge and is labelled that way.
- **Public accounts:** anyone can sign up (password stored only as a hash). Members write one review per shop, edit or delete only their own, and can report another review as spam, abusive or off-topic.
- **Shop owners** list their own shop (details, prices, hours, payment, photos, menu), upload private proof (business permit and a photo of the shop; JPG/PNG/WEBP/PDF up to 5 MB, type checked from the file's bytes, stored outside `public/`), and submit it. It stays hidden until approved. Owners can edit, hide/show or delete only their own shops; a new permit sends the shop back for approval.
- **Staff** never create, edit or delete a shop or a review:

  | Action | Developer | Manager | Customer Service |
  |---|---|---|---|
  | See submissions and open the proof | ✓ | ✓ | ✓ |
  | Add a check note | ✓ | ✓ | ✓ |
  | Approve / reject a shop (reason required) | ✓ | ✓ | ✗ |
  | Suspend / reinstate a shop (reason required) | ✓ | ✓ | ✗ |
  | See review reports | ✓ | ✓ | ✓ |
  | Hide a reported review / dismiss the report | ✓ | ✓ | ✗ |
  | Add, change or remove staff | ✓ | below Developer only | ✗ |

  Nobody changes their own role and the last developer cannot be removed. Rules live in `app/Support/Roles.php` and are checked on the server (403 otherwise).
- The 10 demo listings are managed by a labelled test account, `demo-owner@localspot.test` (it cannot log in); no permit is on file for them.

> Listings are **10 real Angeles City places** (8 published, 2 draft), imported with their source link and the date checked. Unknown prices, settings and hours are shown as "not confirmed", never guessed. Each listing has **two photos** shown with credit, but **reuse permission is not verified** for any of the 20.

## Updating a database created before Nov 15, 2026
Run these once, in this order, from the project folder (MySQL running in XAMPP):
```
C:\xampp\php\php.exe database\migrate.php up
C:\xampp\php\php.exe database\import-listings.php --update
C:\xampp\php\php.exe database\import-menus.php
```
Make the first developer: sign up on the site with your email, then run
`C:\xampp\php\php.exe database\set-role.php you@example.com developer`. After that, developers and managers add staff from **Staff** in the staff panel (`/admin`). Step G turns the old admin account into a developer.
`import-menus.php` adds the estimated budgets (shown with "Est."), menu notes and menu photos/links for the 8 published places.
`migrate.php up` lets budgets, setting and photo be "unknown", adds price unit, source link, date checked and a demo flag, renames "Study Spaces" to "Study & Work Spaces", moves the 8 old fictional places to draft (marked "Demo record" in the admin table), creates the `place_images` table (several photos per place) and the `reviews` table, adds budget estimates and menus, and (step F) adds `users.role` (existing accounts become admin), `reviews.user_id` and the payment columns. It deletes nothing and skips steps already done. `import-listings.php --update` fills in the payment options and never touches the Est. budgets.
If you already ran an earlier version of the import, run it with `--update` so the old listings get the new details.
To undo: `import-listings.php --remove`, then `import-menus.php --remove` and `migrate.php down` seven times (it undoes one step at a time; undoing step F deletes member accounts and undoing the reviews step deletes reviews, so both ask you to type yes first).

## Requirements (checked on this laptop)
- XAMPP with **PHP 8.0.30** (code is PHP 8.0-compatible) and MariaDB/MySQL
- PHP extensions: `pdo_mysql`, `curl` (both enabled in `C:\xampp\php\php.ini`), `mbstring`
- Internet access only for the weather box. Everything else works offline.

## Setup on Windows + XAMPP

Open **Command Prompt** in the project folder. These commands use XAMPP's PHP directly, so `php` does not need to be on your PATH.

1. **Start only MySQL** in the XAMPP Control Panel. Apache isn't needed (see "Why not Apache" below).
2. **Create the database and tables:**
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
   ```
   (Or in phpMyAdmin: **Import** tab → choose `database/schema.sql` → Go.)
3. **Create your private settings file:**
   ```
   copy .env.example .env
   ```
   The defaults match XAMPP (user `root`, empty password). Change `DB_PASSWORD` if yours differs.
4. **Seed categories and the admin account** (asks you to choose the admin password), then **import the listings**:
   ```
   C:\xampp\php\php.exe database\seed.php
   C:\xampp\php\php.exe database\import-listings.php
   ```
5. **Start the app:**
   ```
   C:\xampp\php\php.exe -S localhost:8000 -t public router.php
   ```
6. Open **http://localhost:8000**. Log in (staff, owner or member) at http://localhost:8000/login; sign up at http://localhost:8000/register.

### Running the seeder again
`seed.php` and `import-listings.php` never delete data. Re-running the seeder adds only missing categories and creates the admin only if no user exists. Re-running the import skips listings that already exist (add `--update` to refresh their sourced details; it never changes published/draft status). To start over completely, drop the `localspot` database in phpMyAdmin and repeat steps 2–4.

### Why not Apache (htdocs)?
Apache serves everything inside `C:\xampp\htdocs`, including files that must stay private (`.env`, `database/`, `storage/logs`). The root `.htaccess` blocks the whole project except `public/` as a safety net. The tested way to run the app is still PHP's built-in server with `public/` as the document root.

If you do use Apache, either:
- point a **virtual host's DocumentRoot at `public/`**, or
- open it as `http://localhost/<folder>/public/` and set `APP_BASE_PATH=/<folder>/public` in `.env`.

Note: the Apache setup was **not tested**. Only the built-in server setup was.

## Tests
The same 46 tests run with **PHPUnit 9.6** (installed by Composer as a development-only tool) or with SpotCore's built-in runner. They use in-memory SQLite and a fake HTTP client, so they need no database or internet.
```
composer install
C:\xampp\php\php.exe vendor\bin\phpunit
```
`composer install` runs once and downloads PHPUnit into `vendor/`. Composer may use another PHP on your PC (for example `C:\php`, PHP 8.5); `composer.json` pins the platform to PHP 8.0.30, so it still installs the PHP 8.0-compatible PHPUnit 9.6. Always **run** the tests with XAMPP's PHP (8.0.30), the same PHP that runs the app.
Without Composer:
```
C:\xampp\php\php.exe tests\run.php
```
Composer is used **only** for PHPUnit. The app and SpotCore don't use Composer's autoloader or any packages, so LocalSpot runs even without a `vendor/` folder.

## Project structure
```
public/           Web root: index.php (front controller), .htaccess, css/js/images
core/             SpotCore framework: App, Router, Request, Response, Database, Model,
                  Controller, View, Session, Csrf, Auth, Validator, Config, Env, Logger,
                  Http/ (HttpClient + cURL), Testing/ (test framework), helpers.php
app/Controllers/  PlaceController, AuthController, DashboardController, AdminPlaceController
app/Models/       Place, PlaceImage, Category, User          (all SQL lives here)
app/Middleware/   AuthMiddleware, CsrfMiddleware, LoggingMiddleware, SecurityHeadersMiddleware
app/Services/     WeatherService (Open-Meteo)
app/Support/      PlaceFilters, PlaceFormat
app/Views/        layouts, places, auth, admin, errors, partials
config/           app, database, weather, middleware, images  (read values from .env)
routes/web.php    Every URL
database/         schema.sql, seed.php, migrate.php, import-listings.php (CLI only)
storage/          cache/ (weather) and logs/ (outside public/)
tests/            *Test.php, run.php (SpotCore runner), phpunit-bootstrap.php, setup.php
composer.json     Dev-only dependency: phpunit/phpunit ^9.6 (PHP 8.0 compatible)
bootstrap.php     Loads autoloader, .env, config, database
router.php        Router script for PHP's built-in server
```

## Troubleshooting
| Problem | Fix |
|---|---|
| `Could not connect to the database` | Start MySQL in XAMPP; check `.env` DB values; import `database/schema.sql`. |
| `.env file not found` | Step 3: `copy .env.example .env`. |
| Weather always "temporarily unavailable" | Check internet. In `storage/logs/app-*.log`, look for "Open-Meteo request failed". For SSL errors, make sure `curl.cainfo` in php.ini points to `C:\xampp\apache\bin\curl-ca-bundle.crt`. Never disable SSL verification. After a failure the app waits 2 minutes before trying again (15 minutes after HTTP 429). |
| "Form expired" (419) | Refresh the page and submit again (the CSRF token was old). |
| Port 8000 busy | Use `-S localhost:8080 ...` instead. |

## Credits
Weather data by [Open-Meteo.com](https://open-meteo.com/) (CC BY 4.0). Inter font by The Inter Project Authors (SIL Open Font License), bundled locally. Venue photos are credited on each place page; reuse permission is not verified.

# BUILD_STATUS — LocalSpot

_Last updated: Nov 15, 2026 (Asia/Manila). No passwords are stored in this file._

## Completed
1. SpotCore framework (`core/`): Router (positive-integer IDs, 404/405 + Allow), Request (base path), Response (internal-only redirects), App kernel + middleware pipeline, Database (PDO prepared statements), Model, Controller, View, Session (HttpOnly, SameSite, Secure on HTTPS), Csrf, Auth, Validator (incl. `exists`, `gte`, `in`), Config + Env (.env), Logger, Http client (cURL, TLS on), built-in test framework.
2. Database: `database/schema.sql` (users, categories, places; FKs RESTRICT; indexes on category_id, created_by, status) and CLI-only `database/seed.php` (non-destructive, safe to re-run).
3. Auth, CSRF on every POST, protected admin routes, places CRUD with validation and draft/published.
4. Public explore page (keyword name OR address, category, starting-budget filter, combined with AND) and detail page (404 for drafts).
5. WeatherService (Open-Meteo, 15-min cache, 2-h stale fallback, cooldowns, 429 handling, attribution).
6. Redesign 2 (Nov 15, afternoon): "professional local discovery guide". Palette #F7F7F2 / #FFFFFF / #202722 / #646D66 / #245541 / #E2E6DF, Inter (bundled, OFL), 8px spacing, 10-14px radii, subtle borders. Compact header, intro, 48px-control search form, category chips (empty categories hidden, counts from published rows), 3/2/1 card grid with real photos or a "Photo coming soon" block, detail page with credit, source link, last-checked date and area weather. Admin restyled to match. All cartoon illustrations, the purple hero and Poppins/Lora removed.
   Photos (Nov 15, later): 10 listings with 2 photos each (20 files, checksums match the pack) in a new place_images table (migration step B). Cards show photo 1; the detail page shows both with credit and source. Photos are shown whole over a blurred fill, so watermarks are never cropped. Mozaic and Arte were superseded by the 10-shop pack. Published 8, draft 2 (WorkSpace45, The Coffee Lab & Lounge).
   Detail page + reviews (Nov 15, 12:45, at the student's request): the first photo is the page backdrop (darkened, fading into the About text, credited under the title); a slideshow below shows the photos, advancing every 5 seconds, with Pause/Play, previous/next and thumbnails, pausing on hover or focus and not autoplaying for reduced-motion users (without JavaScript all photos are listed). Visitor reviews (migration step C, `reviews` table): name, 1-5 rating, comment; shown immediately; admin Reviews page can delete; CSRF, honeypot, 10-minute per-place cooldown per session; output escaped. No reviews are seeded. This overrides the earlier brief's 'no reviews' scope note.
   Budgets and menus (Nov 15, 13:05, from the student's menus pack): migration step D (budget_is_estimate, budget_note, menu_note, place_menus) and `database/import-menus.php`. 7 places show an "Est." per-person budget with its basis; Let's Drink shows "Budget awaiting verification". 4 Restaurant Guru menu photos and 2 Foodpanda links behind "View menu"; photos labelled as third-party, not official. Historical/delivery prices only in labelled menu notes. Pack issue: the two Retro menu files are identical; the two Pineapple files show the same cocktail page.
   Earlier content step: reversible migration `database/migrate.php` (nullable budget/setting/photo, price_unit, source_url, checked_on, is_demo; "Study & Work Spaces"); idempotent `database/import-listings.php` (2 published: Lala Garden Cafe, Aling Lucing Sisig; 3 draft: WorkSpace45, Mozaic, Arte). The 8 fictional records are archived as draft with is_demo = 1. Photo reuse permission is NOT verified.
   Accounts, payment and maps (Nov 15, 14:30, at the student's request): migration step F adds `users.role` (existing account = admin; sign-ups always member), `reviews.user_id` (SET NULL) and `places.payment_options` / `payment_note`. Public sign-up/log-in (one login page for both roles), one review per user per place, author-only edit/delete (server-side ownership check, 403 otherwise), admin can delete but not edit reviews, member opening /admin gets 403. Payment for all 10 listings: "Cash and online payment (GCash and other e-wallets)", reported by the student from local knowledge and labelled so. "Open in Google Maps" link on each place page. Admin can/cannot list on the dashboard. Validator rules `same` and `unique` (allowlisted). Menu panel shows only the menu (menu notes no longer displayed).
   Owners and staff (Nov 15, evening, at the student's request): migration step G (places.owner_id + review fields, place_documents, place_checks, reviews.is_hidden, review_reports; 'admin' -> 'developer'). Owners list and manage their own shops and upload private proof; staff roles Developer / Manager / Customer Service (key 'employee') check proof, approve/reject/suspend with reasons, hide reported reviews; managers manage staff only below Developer; no staff role edits shops or reviews. Old admin CRUD controllers/views removed. CLI database/set-role.php for the first developer. 64 end-to-end checks pass (sandbox, SQLite).
7. Tests: 46 unit tests (RolesTest, UploadedFileTest added); previously 39 (7 added for url/date/required_with rules and budget formatting, 2 for same/unique) (PHPUnit 9.6 via Composer dev dependency, or SpotCore runner) pass with the SpotCore runner.
8. README, documentation, ERD, presentation script, LocalSpot.zip.

## Start command (Windows, XAMPP)
```
C:\xampp\php\php.exe -S localhost:8000 -t public router.php
```
Full setup in README.md (schema import → copy .env → seed → serve).

## Important files
- Routes: `routes/web.php` · Front controller: `public/index.php` · Boot: `bootstrap.php`
- Weather: `app/Services/WeatherService.php`, config `config/weather.php`
- Place SQL: `app/Models/Place.php` · Filters: `app/Support/PlaceFilters.php`
- Tests: `tests/run.php`

## Environment facts
- Student laptop: XAMPP PHP **8.0.30**; php.ini has pdo_mysql, curl, pdo_sqlite enabled and `curl.cainfo` set.
- Project folder: `C:\xampp\htdocs\commission-shop\commission-shop` (inside htdocs; the root `.htaccess` denies everything except `public/`).

## Verified on the student's laptop
- Nov 15, 12:23: `migrate.php up` on MariaDB applied steps A and B (8 demo records archived, place_images created); `import-listings.php` added 10 listings and 20 photos (8 published, 2 draft). `migrate.php down` has only been tested on SQLite.
- PHPUnit 9.6.39 on XAMPP PHP 8.0.30: OK (39 tests, 88 assertions) (Nov 15, 14:48).
- Nov 15, 14:48: `migrate.php up` applied step F on MariaDB; `import-listings.php --update` updated 10 listings and 20 photos.
- Composer: `C:\php\php.ini` needed `extension=zip` enabled (done).

- MariaDB schema import + seed: OK; site loads; admin login works (log, 08:10).
- Live Open-Meteo call: 27.5 °C, Partly cloudy, 76 % at 08:09:41, cached as designed.

## Not yet verified
- Migration step G on MariaDB, and PHPUnit with 46 tests on the laptop (expected: OK, 46 tests, 117 assertions).
- Apache setup (only the built-in server is documented as tested).
- Photo reuse permission for all 20 venue photos.
- The redesigned UI on the laptop (copy the updated files, then refresh the browser with Ctrl+F5).

## Next step for the student
Fill in names/date/repo link in the documentation, export it to PDF, record the video.

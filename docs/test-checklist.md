# LocalSpot test checklist (Nov 15, 2026)

## Unit tests (46)
- `C:\xampp\php\php.exe tests\run.php` → **46 passed, 0 failed** (run on the XAMPP laptop, PHP 8.0.30).
- `C:\xampp\php\php.exe vendor\bin\phpunit` → same 46 tests under PHPUnit 9.6 (Composer is a dev-only tool for this).

## End-to-end checks (64, all passing in the sandbox)
Run against the built-in server with separate sessions for a developer, a manager, Customer Service, an owner and members.

| Area | What was checked |
|---|---|
| Staff management | CLI makes the first developer; developer adds a manager and Customer Service; nobody changes their own role; managers cannot create or remove a developer; Customer Service cannot open the Staff page; a shop owner cannot become staff |
| Access | members get 403 on `/admin`; staff cannot list a shop; another user's shop gives 404 |
| Owner flow | shop starts as a draft (hidden from public, owner can preview, staff cannot see it); submit refused without permit and shop photo; a script renamed to `.jpg` is rejected; evidence stored outside `public/`; only staff and the owner can open it; listing photo and menu added; submit → pending |
| Decisions | Customer Service can add notes but gets 403 on approve; reject needs a reason (min 10 chars) and the owner sees it; resubmission approved → published, account becomes owner; owner edits go live; suspend takes it off the site and locks it; reinstate; owner hide/show; a new permit sends it back for approval; owner deletes the shop and its uploaded photos are removed |
| Reviews | owner cannot review their own shop or delete a review; members report a review once; authors cannot report their own; Customer Service cannot hide; manager hides it; hidden review not public, still visible to its author, never deleted |

## Public pages (56 checks)
Published-only listing, filters (keyword, category, budget; unknown budgets not counted as free), empty state, detail page (Est. budgets, payment, Google Maps link, menu, photos with credit), weather fresh / older copy / unavailable, CSRF 419, wrong method 405, unknown URL 404.

## On the laptop (MariaDB)
- `migrate.php up` applied steps A–G; `import-listings.php --update` updated 10 listings and 20 photos.
- Owner flow tried by hand: shop created, permit uploaded, listing photo added after fixing the description length.

<?php
/**
 * Migrations for the sourced listings. COMMAND LINE ONLY.
 *   Step A (2026-11-15a): sourced listing columns (below)
 *   Step B (2026-11-15b): place_images table, so each place can have several photos
 *     (path, sort order, alt text, credit, source link, size). Removing it only
 *     removes photo records; image files are not touched.
 *   Step C (2026-11-15c): reviews table (visitor reviews: name, 1-5 rating, comment).
 *     Undoing it deletes all reviews, so the command asks you to type "yes" first.
 *   Step D (2026-11-15d): budget estimates and menus. Adds places.budget_is_estimate,
 *     places.budget_note, places.menu_note and a place_menus table (menu photos and links
 *     with their source). Undoing it removes those columns and menu records.
 *   Step E (2026-11-15e): menu_items table (typed-out menu: section, item name, price, source).
 *   Step F (2026-11-15f): public accounts and payment options. Adds users.role
 *     (existing accounts become 'admin', new sign-ups are 'member'), reviews.user_id
 *     (who wrote it; older reviews stay without an account) and places.payment_options /
 *     places.payment_note. Undoing it deletes member accounts, so it asks for "yes".
 *   Step G (2026-11-15g): owners and staff. Owners submit and manage their own shops;
 *     staff (developer | manager | employee) check evidence and approve, reject or suspend,
 *     but never edit shops or reviews. Adds places.owner_id and review fields,
 *     place_documents (private evidence files), place_checks (staff notes),
 *     reviews.is_hidden and review_reports. The old 'admin' role becomes 'developer'.
 * "up" applies whatever is missing; "down" undoes the newest applied step only.
 *
 *   C:\xampp\php\php.exe database\migrate.php up      apply
 *   C:\xampp\php\php.exe database\migrate.php down    undo
 *   C:\xampp\php\php.exe database\migrate.php status  show what is applied
 *
 * UP (safe to run twice; it skips what is already done):
 *   - budget_min, budget_max, setting, image_key may be NULL ("not confirmed")
 *   - adds price_unit, source_url, checked_on, is_demo
 *   - existing rows that have a budget get price_unit = 'person' (what the old
 *     form meant), so their prices keep the same meaning
 *   - renames the category "Study Spaces" to "Study & Work Spaces"
 *   - marks the 8 known fictional seed records as is_demo = 1 and moves them to
 *     draft. Nothing is deleted. Other records are not touched.
 *
 * DOWN restores the old structure. It refuses to run while any place has an
 * unknown budget, setting or photo, because the old columns cannot hold "unknown".
 * Remove the imported listings first:  php database\import-listings.php --remove
 * Demo records go back to their original published/draft status.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../bootstrap.php';

use SpotCore\Database;

function out(string $text): void { fwrite(STDOUT, $text . PHP_EOL); }

/** The fictional seed records, matched by name AND their "Demo ..." address. */
const DEMO_NAMES = [
    'Kapihan sa Kanto', 'Pandesal & Brew', 'Quiet Pages Study Hub', 'Deadline Desk Co-study',
    'Acacia Grove Park', 'Riverside Picnic Lawn', 'Silog Station', 'Ihaw-Ihaw Corner',
];
const DEMO_ORIGINALLY_DRAFT = ['Ihaw-Ihaw Corner'];

$command = $argv[1] ?? 'status';
if (!in_array($command, ['up', 'down', 'status'], true)) {
    out('Usage: php database/migrate.php up|down|status');
    exit(1);
}

try {
    $pdo = Database::pdo();
} catch (PDOException $e) {
    out('Could not connect to the database. Is MySQL running in XAMPP?');
    out('Details: ' . $e->getMessage());
    exit(1);
}
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

function columns(string $driver): array
{
    if ($driver === 'sqlite') {
        return array_column(Database::fetchAll('PRAGMA table_info(places)'), 'name');
    }
    return array_column(Database::fetchAll('SHOW COLUMNS FROM places'), 'Field');
}

$applied = in_array('is_demo', columns($driver), true);

function hasImagesTable(string $driver): bool
{
    if ($driver === 'sqlite') {
        return (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'place_images'");
    }
    return Database::fetch("SHOW TABLES LIKE 'place_images'") !== null;
}
$appliedB = hasImagesTable($driver);

function hasReviewsTable(string $driver): bool
{
    if ($driver === 'sqlite') {
        return (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'reviews'");
    }
    return Database::fetch("SHOW TABLES LIKE 'reviews'") !== null;
}
$appliedC = hasReviewsTable($driver);
$appliedD = in_array('budget_is_estimate', columns($driver), true);

function hasMenuItemsTable(string $driver): bool
{
    if ($driver === 'sqlite') {
        return (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'menu_items'");
    }
    return Database::fetch("SHOW TABLES LIKE 'menu_items'") !== null;
}
$appliedE = hasMenuItemsTable($driver);

function userColumns(string $driver): array
{
    if ($driver === 'sqlite') {
        return array_column(Database::fetchAll('PRAGMA table_info(users)'), 'name');
    }
    return array_column(Database::fetchAll('SHOW COLUMNS FROM users'), 'Field');
}
$appliedF = in_array('role', userColumns($driver), true);

function hasTable(string $driver, string $table): bool
{
    if ($driver === 'sqlite') {
        return (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = :t", ['t' => $table]);
    }
    return Database::fetch('SHOW TABLES LIKE ' . Database::pdo()->quote($table)) !== null;
}
$appliedG = hasTable($driver, 'place_documents');

function applyStepG(string $driver): void
{
    if ($driver === 'sqlite') {
        foreach ([
            'ALTER TABLE places ADD COLUMN owner_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL',
            'ALTER TABLE places ADD COLUMN review_note TEXT NULL',
            'ALTER TABLE places ADD COLUMN submitted_at TEXT NULL',
            'ALTER TABLE places ADD COLUMN reviewed_at TEXT NULL',
            'ALTER TABLE places ADD COLUMN reviewed_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL',
            'ALTER TABLE places ADD COLUMN approved_at TEXT NULL',
            'ALTER TABLE reviews ADD COLUMN is_hidden INTEGER NOT NULL DEFAULT 0',
            'CREATE TABLE place_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,
                place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
                kind TEXT NOT NULL, stored_name TEXT NOT NULL, original_name TEXT NOT NULL,
                file_type TEXT NOT NULL, size INTEGER NOT NULL,
                uploaded_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL, created_at TEXT NOT NULL)',
            'CREATE TABLE place_checks (id INTEGER PRIMARY KEY AUTOINCREMENT,
                place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
                user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                note TEXT NOT NULL, created_at TEXT NOT NULL)',
            'CREATE TABLE review_reports (id INTEGER PRIMARY KEY AUTOINCREMENT,
                review_id INTEGER NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
                reporter_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                reason TEXT NOT NULL, note TEXT NULL, status TEXT NOT NULL DEFAULT \'open\',
                handled_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL, handled_at TEXT NULL,
                created_at TEXT NOT NULL, UNIQUE (review_id, reporter_id))',
        ] as $sql) {
            Database::query($sql);
        }
    } else {
        Database::query('ALTER TABLE places
            ADD COLUMN owner_id INT UNSIGNED NULL AFTER created_by,
            ADD COLUMN review_note VARCHAR(500) NULL AFTER status,
            ADD COLUMN submitted_at DATETIME NULL AFTER review_note,
            ADD COLUMN reviewed_at DATETIME NULL AFTER submitted_at,
            ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER reviewed_at,
            ADD COLUMN approved_at DATETIME NULL AFTER reviewed_by,
            ADD KEY places_owner_index (owner_id),
            ADD CONSTRAINT places_owner_fk FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT places_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE');
        Database::query('ALTER TABLE reviews ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER comment');
        Database::query('CREATE TABLE place_documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            place_id INT UNSIGNED NOT NULL,
            kind VARCHAR(20) NOT NULL,
            stored_name VARCHAR(64) NOT NULL,
            original_name VARCHAR(150) NOT NULL,
            file_type VARCHAR(10) NOT NULL,
            size INT UNSIGNED NOT NULL,
            uploaded_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY place_documents_place_index (place_id),
            CONSTRAINT place_documents_place_fk FOREIGN KEY (place_id) REFERENCES places (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT place_documents_user_fk FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        Database::query('CREATE TABLE place_checks (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            place_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            note VARCHAR(1000) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY place_checks_place_index (place_id),
            CONSTRAINT place_checks_place_fk FOREIGN KEY (place_id) REFERENCES places (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT place_checks_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        Database::query('CREATE TABLE review_reports (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            review_id INT UNSIGNED NOT NULL,
            reporter_id INT UNSIGNED NOT NULL,
            reason VARCHAR(20) NOT NULL,
            note VARCHAR(500) NULL,
            status VARCHAR(10) NOT NULL DEFAULT \'open\',
            handled_by INT UNSIGNED NULL,
            handled_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY review_reports_once (review_id, reporter_id),
            KEY review_reports_status_index (status),
            CONSTRAINT review_reports_review_fk FOREIGN KEY (review_id) REFERENCES reviews (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT review_reports_reporter_fk FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT review_reports_handler_fk FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    // The single 'admin' of step F becomes the first developer.
    Database::query("UPDATE users SET role = 'developer' WHERE role = 'admin'");
}

function applyStepF(string $driver): void
{
    if ($driver === 'sqlite') {
        Database::query("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'member'");
        Database::query('ALTER TABLE reviews ADD COLUMN user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL');
        Database::query('ALTER TABLE places ADD COLUMN payment_options TEXT NULL');
        Database::query('ALTER TABLE places ADD COLUMN payment_note TEXT NULL');
    } else {
        Database::query("ALTER TABLE users ADD COLUMN role VARCHAR(10) NOT NULL DEFAULT 'member' AFTER password_hash");
        Database::query('ALTER TABLE reviews
            ADD COLUMN user_id INT UNSIGNED NULL AFTER place_id,
            ADD KEY reviews_user_index (user_id),
            ADD CONSTRAINT reviews_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE SET NULL ON UPDATE CASCADE');
        Database::query('ALTER TABLE places ADD COLUMN payment_options VARCHAR(255) NULL AFTER hours_note');
        Database::query('ALTER TABLE places ADD COLUMN payment_note VARCHAR(255) NULL AFTER payment_options');
    }
    // Every account that existed before sign-ups is the administrator.
    Database::query("UPDATE users SET role = 'admin'");
}

function createMenuItemsTable(string $driver): void
{
    if ($driver === 'sqlite') {
        Database::query('CREATE TABLE menu_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
            section TEXT NOT NULL, name TEXT NOT NULL, price REAL NULL, source TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL)');
        return;
    }
    Database::query('CREATE TABLE IF NOT EXISTS menu_items (
        id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
        place_id    INT UNSIGNED      NOT NULL,
        section     VARCHAR(80)       NOT NULL,
        name        VARCHAR(150)      NOT NULL,
        price       DECIMAL(10,2)     NULL,       -- as listed in the source; NULL if unreadable
        source      VARCHAR(255)      NOT NULL,   -- where and when the menu was read, incl. branch
        sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        created_at  DATETIME          NOT NULL,
        PRIMARY KEY (id),
        KEY menu_items_place_index (place_id),
        CONSTRAINT menu_items_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function applyStepD(string $driver): void
{
    if ($driver === 'sqlite') {
        Database::query('ALTER TABLE places ADD COLUMN budget_is_estimate INTEGER NOT NULL DEFAULT 0');
        Database::query('ALTER TABLE places ADD COLUMN budget_note TEXT NULL');
        Database::query('ALTER TABLE places ADD COLUMN menu_note TEXT NULL');
        Database::query('CREATE TABLE place_menus (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
            kind TEXT NOT NULL, path TEXT NULL, url TEXT NULL, label TEXT NOT NULL, note TEXT NULL,
            credit TEXT NULL, source_url TEXT NULL, width INTEGER NULL, height INTEGER NULL,
            sort_order INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL)');
        return;
    }
    Database::query("ALTER TABLE places
        ADD COLUMN budget_is_estimate TINYINT(1) NOT NULL DEFAULT 0 AFTER price_unit,
        ADD COLUMN budget_note VARCHAR(255) NULL AFTER budget_is_estimate,
        ADD COLUMN menu_note TEXT NULL AFTER hours_note");
    Database::query('CREATE TABLE IF NOT EXISTS place_menus (
        id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
        place_id    INT UNSIGNED      NOT NULL,
        kind        VARCHAR(10)       NOT NULL,   -- image | link
        path        VARCHAR(255)      NULL,       -- image file under public/assets/ (kind = image)
        url         VARCHAR(255)      NULL,       -- external menu page (kind = link)
        label       VARCHAR(255)      NOT NULL,
        note        VARCHAR(255)      NULL,       -- e.g. "partial menu", "upload date unconfirmed"
        credit      VARCHAR(255)      NULL,
        source_url  VARCHAR(255)      NULL,
        width       SMALLINT UNSIGNED NULL,
        height      SMALLINT UNSIGNED NULL,
        sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        created_at  DATETIME          NOT NULL,
        PRIMARY KEY (id),
        KEY place_menus_place_index (place_id),
        CONSTRAINT place_menus_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function createReviewsTable(string $driver): void
{
    if ($driver === 'sqlite') {
        Database::query('CREATE TABLE reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
            author_name TEXT NOT NULL, rating INTEGER NOT NULL, comment TEXT NOT NULL, created_at TEXT NOT NULL)');
        Database::query('CREATE INDEX reviews_place_index ON reviews (place_id)');
        return;
    }
    Database::query('CREATE TABLE IF NOT EXISTS reviews (
        id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
        place_id    INT UNSIGNED     NOT NULL,
        author_name VARCHAR(60)      NOT NULL,
        rating      TINYINT UNSIGNED NOT NULL,      -- 1 to 5 (checked by the app)
        comment     TEXT             NOT NULL,
        created_at  DATETIME         NOT NULL,
        PRIMARY KEY (id),
        KEY reviews_place_index (place_id),
        CONSTRAINT reviews_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

if ($command === 'status') {
    out('Step A (sourced listing columns): ' . ($applied ? 'applied' : 'NOT applied'));
    out('Step B (place_images table):      ' . ($appliedB ? 'applied' : 'NOT applied'));
    out('Step C (reviews table):           ' . ($appliedC ? 'applied' : 'NOT applied'));
    out('Step D (budget estimates, menus): ' . ($appliedD ? 'applied' : 'NOT applied'));
    out('Step E (menu_items table):        ' . ($appliedE ? 'applied' : 'NOT applied'));
    out('Step F (accounts, payment):       ' . ($appliedF ? 'applied' : 'NOT applied'));
    out('Step G (owners and staff):        ' . ($appliedG ? 'applied' : 'NOT applied'));
    if (!$applied || !$appliedB || !$appliedC || !$appliedD || !$appliedE || !$appliedF || !$appliedG) {
        out('Run: php database/migrate.php up');
    }
    exit(0);
}

function createImagesTable(string $driver): void
{
    if ($driver === 'sqlite') {
        Database::query('CREATE TABLE place_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            place_id INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
            path TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 1, alt TEXT NOT NULL,
            credit TEXT NOT NULL, source_url TEXT NULL, width INTEGER NOT NULL, height INTEGER NOT NULL,
            created_at TEXT NOT NULL, UNIQUE (place_id, path))');
        return;
    }
    Database::query('CREATE TABLE IF NOT EXISTS place_images (
        id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
        place_id    INT UNSIGNED      NOT NULL,
        path        VARCHAR(255)      NOT NULL,
        sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        alt         VARCHAR(255)      NOT NULL,
        credit      VARCHAR(255)      NOT NULL,
        source_url  VARCHAR(255)      NULL,
        width       SMALLINT UNSIGNED NOT NULL,
        height      SMALLINT UNSIGNED NOT NULL,
        created_at  DATETIME          NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY place_images_place_path_unique (place_id, path),
        CONSTRAINT place_images_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/** Matches demo rows by name (with or without the old " (Demo)" suffix) and a "Demo ..." address. */
function demoWhere(array $names, array &$params): string
{
    $parts = [];
    foreach ($names as $i => $name) {
        $parts[] = "(name = :n{$i} OR name = :d{$i} OR name = :dd{$i})";
        $params["n{$i}"] = $name;
        $params["d{$i}"] = $name . ' (Demo)';
        $params["dd{$i}"] = $name . ' (Demo, draft)';
    }
    return '(' . implode(' OR ', $parts) . ") AND address LIKE 'Demo %'";
}

/** SQLite cannot change a column's NULL rule, so the table is rebuilt with the same data. */
function sqliteRebuild(string $columnsSql, array $copyColumns): void
{
    $list = implode(', ', $copyColumns);
    Database::query('PRAGMA foreign_keys = OFF');
    Database::query("CREATE TABLE places_new ({$columnsSql})");
    Database::query("INSERT INTO places_new ({$list}) SELECT {$list} FROM places");
    Database::query('DROP TABLE places');
    Database::query('ALTER TABLE places_new RENAME TO places');
    Database::query('PRAGMA foreign_keys = ON');
}

const OLD_COLUMNS = ['id', 'category_id', 'created_by', 'name', 'description', 'address', 'city', 'budget_min', 'budget_max',
    'setting', 'hours_note', 'image_key', 'status', 'created_at', 'updated_at'];
const NEW_COLUMNS = ['id', 'category_id', 'created_by', 'name', 'description', 'address', 'city', 'budget_min', 'budget_max',
    'price_unit', 'setting', 'hours_note', 'image_key', 'source_url', 'checked_on', 'is_demo', 'status', 'created_at', 'updated_at'];

if ($command === 'up') {
    if ($applied && $appliedB && $appliedC && $appliedD && $appliedE && $appliedF && $appliedG) {
        out('Already applied. Nothing changed.');
        exit(0);
    }
    if ($applied) {
        if (!$appliedB) {
            createImagesTable($driver);
            out('Step B applied: place_images table created. Next: php database/import-listings.php');
        }
        if (!$appliedC) {
            createReviewsTable($driver);
            out('Step C applied: reviews table created.');
        }
        if (!$appliedD) {
            applyStepD($driver);
            out('Step D applied: budget estimate fields and place_menus table added.');
        }
        if (!$appliedE) {
            createMenuItemsTable($driver);
            out('Step E applied: menu_items table created.');
        }
        if (!$appliedF) {
            applyStepF($driver);
            out('Step F applied: user roles, review authors and payment options added.');
        }
        if (!$appliedG) {
            applyStepG($driver);
            out('Step G applied: owners, staff roles, evidence files and review reports added. The admin is now a developer.');
        }
        out('Next: php database/import-listings.php --update, then php database/import-menus.php');
        exit(0);
    }
    if ($driver === 'sqlite') {
        sqliteRebuild(
            'id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL, created_by INTEGER NOT NULL,
             name TEXT NOT NULL, description TEXT NOT NULL, address TEXT NOT NULL, city TEXT NOT NULL DEFAULT \'Angeles City\',
             budget_min REAL NULL, budget_max REAL NULL, price_unit TEXT NULL, setting TEXT NULL, hours_note TEXT NULL,
             image_key TEXT NULL, source_url TEXT NULL, checked_on TEXT NULL, is_demo INTEGER NOT NULL DEFAULT 0,
             status TEXT NOT NULL DEFAULT \'draft\', created_at TEXT NOT NULL, updated_at TEXT NOT NULL',
            OLD_COLUMNS
        );
    } else {
        // MySQL/MariaDB: each ALTER is applied on its own (DDL commits immediately).
        Database::query("ALTER TABLE places
            MODIFY budget_min DECIMAL(10,2) NULL,
            MODIFY budget_max DECIMAL(10,2) NULL,
            ADD COLUMN price_unit VARCHAR(20) NULL AFTER budget_max,
            MODIFY setting VARCHAR(10) NULL,
            MODIFY image_key VARCHAR(50) NULL,
            ADD COLUMN source_url VARCHAR(255) NULL AFTER image_key,
            ADD COLUMN checked_on DATE NULL AFTER source_url,
            ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER checked_on");
    }

    Database::transaction(function (): void {
        $now = date('Y-m-d H:i:s');
        Database::query("UPDATE places SET price_unit = 'person' WHERE budget_min IS NOT NULL AND price_unit IS NULL");
        Database::query("UPDATE categories SET name = 'Study & Work Spaces' WHERE name = 'Study Spaces'");
        $params = ['now' => $now];
        $n = Database::query("UPDATE places SET is_demo = 1, status = 'draft', updated_at = :now WHERE " . demoWhere(DEMO_NAMES, $params), $params)->rowCount();
        out("Marked {$n} fictional demo record(s) as demo and moved them to draft.");
    });
    out('Step A applied.');
    if (!$appliedB) {
        createImagesTable($driver);
        out('Step B applied: place_images table created.');
    }
    if (!$appliedC) {
        createReviewsTable($driver);
        out('Step C applied: reviews table created.');
    }
    if (!$appliedD) {
        applyStepD($driver);
        out('Step D applied: budget estimate fields and place_menus table added.');
    }
    if (!$appliedE) {
        createMenuItemsTable($driver);
        out('Step E applied: menu_items table created.');
    }
    if (!$appliedF) {
        applyStepF($driver);
        out('Step F applied: user roles, review authors and payment options added.');
    }
    if (!$appliedG) {
        applyStepG($driver);
        out('Step G applied: owners, staff roles, evidence files and review reports added. The admin is now a developer.');
    }
    out('Next: php database/import-listings.php, then php database/import-menus.php');
    exit(0);
}

// ------------------------------------------------------------------ down
if ($appliedG) {
    $docs = (int) Database::value('SELECT COUNT(*) FROM place_documents');
    $reports = (int) Database::value('SELECT COUNT(*) FROM review_reports');
    fwrite(STDOUT, "This deletes {$docs} evidence record(s), {$reports} review report(s) and all staff check notes, turns every staff account back into 'admin' and every owner into 'member'. Uploaded files stay in storage/uploads. Type yes to continue: ");
    if (trim((string) fgets(STDIN)) !== 'yes') {
        out('Cancelled. Nothing changed.');
        exit(1);
    }
    Database::query('DROP TABLE review_reports');
    Database::query('DROP TABLE place_checks');
    Database::query('DROP TABLE place_documents');
    if ($driver === 'sqlite') {
        foreach (['owner_id', 'review_note', 'submitted_at', 'reviewed_at', 'reviewed_by', 'approved_at'] as $col) {
            Database::query("ALTER TABLE places DROP COLUMN {$col}");
        }
        Database::query('ALTER TABLE reviews DROP COLUMN is_hidden');
    } else {
        Database::query('ALTER TABLE places DROP FOREIGN KEY places_owner_fk, DROP FOREIGN KEY places_reviewer_fk');
        Database::query('ALTER TABLE places DROP INDEX places_owner_index, DROP COLUMN owner_id, DROP COLUMN review_note,
            DROP COLUMN submitted_at, DROP COLUMN reviewed_at, DROP COLUMN reviewed_by, DROP COLUMN approved_at');
        Database::query('ALTER TABLE reviews DROP COLUMN is_hidden');
    }
    Database::query("UPDATE places SET status = 'draft' WHERE status NOT IN ('draft', 'published')");
    Database::query("UPDATE users SET role = 'admin' WHERE role IN ('developer', 'manager', 'employee')");
    Database::query("UPDATE users SET role = 'member' WHERE role = 'owner'");
    out('Step G undone. Run down again to undo step F.');
    exit(0);
}
if ($appliedF) {
    $members = (int) Database::value("SELECT COUNT(*) FROM users WHERE role <> 'admin'");
    if ($members > 0) {
        fwrite(STDOUT, "This deletes {$members} member account(s); their reviews stay without an author account. Type yes to continue: ");
        if (trim((string) fgets(STDIN)) !== 'yes') {
            out('Cancelled. Nothing changed.');
            exit(1);
        }
    }
    Database::transaction(function (): void {
        Database::query("UPDATE reviews SET user_id = NULL WHERE user_id IN (SELECT id FROM users WHERE role <> 'admin')");
        Database::query("DELETE FROM users WHERE role <> 'admin'");
    });
    if ($driver === 'sqlite') {
        Database::query('ALTER TABLE reviews DROP COLUMN user_id');
        Database::query('ALTER TABLE users DROP COLUMN role');
        Database::query('ALTER TABLE places DROP COLUMN payment_options');
        Database::query('ALTER TABLE places DROP COLUMN payment_note');
    } else {
        Database::query('ALTER TABLE reviews DROP FOREIGN KEY reviews_user_fk');
        Database::query('ALTER TABLE reviews DROP INDEX reviews_user_index, DROP COLUMN user_id');
        Database::query('ALTER TABLE users DROP COLUMN role');
        Database::query('ALTER TABLE places DROP COLUMN payment_options, DROP COLUMN payment_note');
    }
    out("Step F undone: member accounts ({$members}), review authors and payment options removed. Run down again to undo step E.");
    exit(0);
}
if ($appliedE) {
    $n = (int) Database::value('SELECT COUNT(*) FROM menu_items');
    Database::query('DROP TABLE menu_items');
    out("Step E undone: menu_items table removed ({$n} item(s)). Run down again to undo step D.");
    exit(0);
}
if ($appliedD) {
    $n = (int) Database::value('SELECT COUNT(*) FROM place_menus');
    Database::query('DROP TABLE place_menus');
    // Estimated budgets are planning figures, not confirmed prices: clear them so the
    // older structure does not present them as confirmed.
    Database::query('UPDATE places SET budget_min = NULL, budget_max = NULL, price_unit = NULL WHERE budget_is_estimate = 1');
    if ($driver === 'sqlite') {
        Database::query('ALTER TABLE places DROP COLUMN budget_is_estimate');
        Database::query('ALTER TABLE places DROP COLUMN budget_note');
        Database::query('ALTER TABLE places DROP COLUMN menu_note');
    } else {
        Database::query('ALTER TABLE places DROP COLUMN budget_is_estimate, DROP COLUMN budget_note, DROP COLUMN menu_note');
    }
    out("Step D undone: menu records ({$n}) and budget estimates removed. Run down again to undo step C.");
    exit(0);
}
if ($appliedC) {
    $n = (int) Database::value('SELECT COUNT(*) FROM reviews');
    if ($n > 0) {
        fwrite(STDOUT, "This deletes {$n} visitor review(s). Type yes to continue: ");
        if (trim((string) fgets(STDIN)) !== 'yes') {
            out('Cancelled. Nothing changed.');
            exit(1);
        }
    }
    Database::query('DROP TABLE reviews');
    out("Step C undone: reviews table removed ({$n} review(s)). Run down again to undo step B.");
    exit(0);
}
if ($appliedB) {
    $n = (int) Database::value('SELECT COUNT(*) FROM place_images');
    Database::query('DROP TABLE place_images');
    out("Step B undone: place_images table removed ({$n} photo record(s); the image files are untouched).");
    out('Run down again to undo step A.');
    exit(0);
}
if (!$applied) {
    out('Not applied. Nothing to undo.');
    exit(0);
}
$blocking = Database::fetchAll(
    'SELECT id, name FROM places WHERE budget_min IS NULL OR budget_max IS NULL OR setting IS NULL OR image_key IS NULL ORDER BY id'
);
if ($blocking !== []) {
    out('Cannot undo yet. These places have an unknown budget, setting or photo, which the old structure cannot store:');
    foreach ($blocking as $row) {
        out("  #{$row['id']} {$row['name']}");
    }
    out('Remove imported listings with: php database/import-listings.php --remove');
    out('Then fill in or delete any remaining ones in the admin panel, and run this again.');
    exit(1);
}

Database::transaction(function (): void {
    $now = date('Y-m-d H:i:s');
    $params = ['now' => $now];
    $where = demoWhere(DEMO_NAMES, $params);
    Database::query("UPDATE places SET status = 'published', updated_at = :now WHERE is_demo = 1 AND " . $where, $params);
    $params = ['now' => $now];
    $where = demoWhere(DEMO_ORIGINALLY_DRAFT, $params);
    Database::query("UPDATE places SET status = 'draft', updated_at = :now WHERE is_demo = 1 AND " . $where, $params);
    Database::query("UPDATE categories SET name = 'Study Spaces' WHERE name = 'Study & Work Spaces'");
});

if ($driver === 'sqlite') {
    sqliteRebuild(
        'id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL, created_by INTEGER NOT NULL,
         name TEXT NOT NULL, description TEXT NOT NULL, address TEXT NOT NULL, city TEXT NOT NULL DEFAULT \'Angeles City\',
         budget_min REAL NOT NULL, budget_max REAL NOT NULL, setting TEXT NOT NULL, hours_note TEXT NULL,
         image_key TEXT NOT NULL, status TEXT NOT NULL DEFAULT \'draft\', created_at TEXT NOT NULL, updated_at TEXT NOT NULL',
        OLD_COLUMNS
    );
} else {
    Database::query('ALTER TABLE places
        DROP COLUMN price_unit, DROP COLUMN source_url, DROP COLUMN checked_on, DROP COLUMN is_demo,
        MODIFY budget_min DECIMAL(10,2) NOT NULL,
        MODIFY budget_max DECIMAL(10,2) NOT NULL,
        MODIFY setting VARCHAR(10) NOT NULL,
        MODIFY image_key VARCHAR(50) NOT NULL');
}
out('Step A undone. The old structure is back.');

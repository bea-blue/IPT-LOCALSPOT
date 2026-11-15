<?php
/**
 * Imports the 10 sourced Angeles City listings and their 20 photos (2 each).
 * COMMAND LINE ONLY. Run database/migrate.php up first.
 *
 *   C:\xampp\php\php.exe database\import-listings.php            add what is missing
 *   C:\xampp\php\php.exe database\import-listings.php --update   also refresh sourced details and photo credits
 *   C:\xampp\php\php.exe database\import-listings.php --remove   delete ONLY the rows this script added
 *
 * Safe to run again: a listing is matched by name, a photo by (place, file path),
 * so nothing is added twice. --update never changes published/draft status.
 * Other places (admin-created, demo records) are never changed.
 *
 * Facts below come from the source pages checked on CHECKED_ON (details in
 * docs/listing-sources.md). Nothing is invented: no prices were found, so every
 * budget stays NULL ("Budget not confirmed"). Hours appear only where a source
 * states them, with the year or "date unknown". Photo credits are NOT a licence:
 * reuse permission is not verified (docs/image-sources.md).
 *
 * This list supersedes the earlier 5-shop list: Mozaic and Arte were dropped
 * because no verifiable photos or details were found. Their draft rows from the
 * earlier import are removed (only if still draft and unchanged in source).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../bootstrap.php';

use SpotCore\Database;

function say(string $text): void { fwrite(STDOUT, $text . PHP_EOL); }

const CHECKED_ON = '2026-11-15';
/** Reported by the student from local knowledge (Angeles City resident), Nov 15, 2026. */
const PAYMENT_OPTIONS = 'Cash and online payment (GCash and other e-wallets)';
const PAYMENT_NOTE = 'Reported by the developer from local knowledge, Nov 2026. Ask the shop which e-wallets it takes.';
const PUBLIC_ASSETS = __DIR__ . '/../public/assets/';

/** Photo helper: [file, width, height, alt, credit, source page]. The first photo is the card photo. */
function photo(string $folder, string $file, int $w, int $h, string $alt, string $credit, string $source): array
{
    return ['path' => "img/places/{$folder}/{$file}", 'width' => $w, 'height' => $h, 'alt' => $alt, 'credit' => $credit, 'source_url' => $source];
}

$WIP = 'Where In Pampanga';
$listings = [
    [
        'name' => 'Lala Garden Cafe', 'category' => 'Cafés', 'status' => 'published',
        'address' => 'Lot 22, Poinsettia Avenue, Golden Dragon Village, Barangay Pampang',
        'description' => 'A large garden-themed café with indoor greenery and tiered seating, serving coffee, pastries and meals.',
        'setting' => 'indoor', 'hours_note' => 'Reported in 2023 as daily, 8:00 AM to 12:00 midnight. Not confirmed since.',
        'source_url' => 'https://whereinpampanga.com/lala-garden-cafe/',
        'photos' => [
            photo('Lala-Garden-Cafe', 'Lala-Garden-Cafe-02.jpg', 1401, 1751, 'Lala Garden Cafe: tiered seating and garden', 'Lala Garden Cafe, via Where In Pampanga', 'https://whereinpampanga.com/lala-garden-cafe/'),
            photo('Lala-Garden-Cafe', 'Lala-Garden-Cafe-01.jpg', 576, 1024, 'Lala Garden Cafe: garden interior', 'Lala Garden Cafe, via Where In Pampanga', 'https://whereinpampanga.com/lala-garden-cafe/'),
        ],
    ],
    [
        'name' => 'Aling Lucing Sisig', 'category' => 'Food Spots', 'status' => 'published',
        'address' => 'Glaciano Valdez Street, Barangay Agapito del Rosario',
        'description' => 'The restaurant credited with creating modern sisig, the Kapampangan dish of chopped grilled pork served on a sizzling plate.',
        'setting' => null, 'hours_note' => null,
        'source_url' => 'https://www.zoytotheworld.com/blog/angeles-food-trip-the-search-for-the-best-sisig-at-its-birthplace',
        'photos' => [
            photo('Aling-Lucing-Sisig', 'Aling-Lucing-Sisig-01.png', 800, 600, 'Aling Lucing Sisig: restaurant storefront', 'Zoy To The World', 'https://www.zoytotheworld.com/blog/angeles-food-trip-the-search-for-the-best-sisig-at-its-birthplace'),
            photo('Aling-Lucing-Sisig', 'Aling-Lucing-Sisig-02.png', 800, 600, 'Aling Lucing Sisig: sisig on a sizzling plate', 'Zoy To The World', 'https://www.zoytotheworld.com/blog/angeles-food-trip-the-search-for-the-best-sisig-at-its-birthplace'),
        ],
    ],
    [
        'name' => 'Hammer Resto/Café', 'category' => 'Food Spots', 'status' => 'published',
        'address' => 'Unit 2E, Ground Floor, Glenn Heinen Building, Fields Avenue (Red Street), Barangay Balibago',
        'description' => 'A restaurant and café on Red Street serving steak, salmon and wood-fired pizza, with a large video wall in the dining room.',
        'setting' => 'indoor', 'hours_note' => 'Reported in 2025 as open 24 hours. Not confirmed since.',
        'source_url' => 'https://whereinpampanga.com/hammer-resto-cafe-angeles-citys-hottest-new-dining-playground/',
        'photos' => [
            photo('Hammer-Resto-Cafe', 'Hammer-Resto-Cafe-01.jpg', 1024, 768, 'Hammer Resto/Café: dining room', $WIP, 'https://whereinpampanga.com/hammer-resto-cafe-angeles-citys-hottest-new-dining-playground/'),
            photo('Hammer-Resto-Cafe', 'Hammer-Resto-Cafe-02.jpg', 1024, 768, 'Hammer Resto/Café: interior and video display wall', $WIP, 'https://whereinpampanga.com/hammer-resto-cafe-angeles-citys-hottest-new-dining-playground/'),
        ],
    ],
    [
        'name' => 'Let’s Drink Café', 'category' => 'Cafés', 'status' => 'published',
        'address' => 'Building 290, Unit B, Friendship Highway (in front of Donenoo Korean Restaurant)',
        'description' => 'A late-night café and bar serving coffee, milk tea and food, with karaoke and head spa services behind its red entrance.',
        'setting' => 'indoor', 'hours_note' => 'Listed as 7:00 PM to 5:00 AM (Monday to Saturday) and 7:00 PM to 3:00 AM (Sunday). Listing date unknown; not confirmed.',
        'source_url' => 'https://whereinpampanga.com/sip-relax-and-recharge-at-lets-drink-cafe-angeles-citys-premier-all-in-one-destination/',
        'photos' => [
            photo('Lets-Drink-Cafe', 'Lets-Drink-Cafe-01.jpg', 1024, 768, 'Let’s Drink Café: storefront with red entrance', $WIP, 'https://whereinpampanga.com/sip-relax-and-recharge-at-lets-drink-cafe-angeles-citys-premier-all-in-one-destination/'),
            photo('Lets-Drink-Cafe', 'Lets-Drink-Cafe-02.jpg', 1024, 768, 'Let’s Drink Café: interior seating', $WIP, 'https://whereinpampanga.com/sip-relax-and-recharge-at-lets-drink-cafe-angeles-citys-premier-all-in-one-destination/'),
        ],
    ],
    [
        'name' => 'Café Hola', 'category' => 'Cafés', 'status' => 'published',
        'address' => 'Don Juan D. Nepomuceno Avenue, Nepo Center',
        'description' => 'A café at the Nepo Quad serving pastries, cakes and meals.',
        'setting' => 'indoor', 'hours_note' => 'Listed as Monday to Sunday, 10:00 AM to 9:00 PM. Listing date unknown; not confirmed.',
        'source_url' => 'https://whereinpampanga.com/slice-of-sunshine-celebrating-one-delicious-year-with-cafe-hola/',
        'photos' => [
            photo('Cafe-Hola', 'Cafe-Hola-02.jpg', 1024, 768, 'Café Hola: counter and interior', $WIP, 'https://whereinpampanga.com/slice-of-sunshine-celebrating-one-delicious-year-with-cafe-hola/'),
            photo('Cafe-Hola', 'Cafe-Hola-01.jpg', 1024, 702, 'Café Hola: food selection', $WIP, 'https://whereinpampanga.com/slice-of-sunshine-celebrating-one-delicious-year-with-cafe-hola/'),
        ],
    ],
    [
        'name' => 'Beauté Beanery', 'category' => 'Cafés', 'status' => 'published',
        'address' => 'Lot 2, Block 19, corner San Jose Street, MacArthur Highway, Santo Domingo',
        'description' => 'A café-restaurant in the Beautéderm headquarters building, known for Ilocano dishes such as the Vigan bowl and bagnet.',
        'setting' => 'indoor', 'hours_note' => 'Reported in 2024 as 11:00 AM to 9:00 PM. Not confirmed since.',
        'source_url' => 'https://whereinpampanga.com/beaute-beanery/',
        'photos' => [
            photo('Beaute-Beanery', 'Beaute-Beanery-01.jpg', 1024, 683, 'Beauté Beanery: seating area', $WIP, 'https://whereinpampanga.com/beaute-beanery/'),
            photo('Beaute-Beanery', 'Beaute-Beanery-02.jpg', 1024, 683, 'Beauté Beanery: café counter and interior', $WIP, 'https://whereinpampanga.com/beaute-beanery/'),
        ],
    ],
    [
        'name' => 'Pineapple Coast', 'category' => 'Food Spots', 'status' => 'published',
        'address' => '8A Zeppelin Street, Hensonville',
        'description' => 'A taco spot and bar serving Mexican-inspired dishes such as tacos, nachos and ceviche.',
        'setting' => 'both', 'hours_note' => 'Reported in 2022 as Tuesday to Sunday, 5:00 PM to 11:00 PM. Not confirmed since.',
        'source_url' => 'https://www.zoytotheworld.com/blog/angeles-city-food-trip-pineapple-coast',
        'photos' => [
            photo('Pineapple-Coast', 'Pineapple-Coast-01.png', 800, 600, 'Pineapple Coast: entrance and outdoor seating', 'Zoy To The World', 'https://www.zoytotheworld.com/blog/angeles-city-food-trip-pineapple-coast'),
            photo('Pineapple-Coast', 'Pineapple-Coast-02.png', 800, 600, 'Pineapple Coast: indoor seating', 'Zoy To The World', 'https://www.zoytotheworld.com/blog/angeles-city-food-trip-pineapple-coast'),
        ],
    ],
    [
        'name' => 'Café Retro 252', 'category' => 'Cafés', 'status' => 'published',
        'address' => '1F Retro 252 Jake Building, Lot 13-15, Block 4, Cuayan, Friendship Highway',
        'description' => 'A café with vintage-style decor and a large lit sign, serving coffee, food and drinks.',
        'setting' => null, 'hours_note' => null,
        'source_url' => 'https://www.pinned.ph/?p=15133',
        'photos' => [
            photo('Cafe-Retro-252', 'Cafe-Retro-252-02.jpg', 2048, 2047, 'Café Retro 252: exterior entrance', 'Premium Cafe RETRO 252, credited by Safe Space', 'https://woorisafespace.wordpress.com/2022/01/16/5-must-visit-cafes-in-angeles-city-pampanga/'),
            photo('Cafe-Retro-252', 'Cafe-Retro-252-01.jpg', 1440, 1440, 'Café Retro 252: interior sign and seating', 'Premium Cafe RETRO 252, credited by Safe Space', 'https://woorisafespace.wordpress.com/2022/01/16/5-must-visit-cafes-in-angeles-city-pampanga/'),
        ],
    ],
    [
        // Website photos date from 2015 and its rates from 2019: current operation unclear.
        'name' => 'WorkSpace45', 'category' => 'Study & Work Spaces', 'status' => 'draft',
        'address' => 'Level 2, HotSpot Building, MacArthur Highway, Lourdes Sur East',
        'description' => 'A coworking space with shared desks, meeting facilities and a study area on the second floor of the HotSpot Building.',
        'setting' => 'indoor', 'hours_note' => null,
        'source_url' => 'https://workspace45.com/',
        'photos' => [
            photo('WorkSpace45', 'WorkSpace45-02.jpg', 1024, 576, 'WorkSpace45: desk and seating area', 'WorkSpace45 official gallery', 'https://workspace45.com/'),
            photo('WorkSpace45', 'WorkSpace45-01.jpg', 1024, 576, 'WorkSpace45: shared working area', 'WorkSpace45 official gallery', 'https://workspace45.com/'),
        ],
    ],
    [
        // Address comes from the photo pack's source page only; not independently confirmed.
        'name' => 'The Coffee Lab & Lounge', 'category' => 'Cafés', 'status' => 'draft',
        'address' => 'Ground Floor, Marlim Mansions Hotel, MacArthur Highway corner Marlim Avenue, Balibago',
        'description' => 'A café serving coffee, croissants, pastries and meals.',
        'setting' => null, 'hours_note' => null,
        'source_url' => 'https://whereinpampanga.com/the-art-of-the-croissant-at-the-coffee-lab-lounge-a-must-visit-cafe-in-angeles-city/',
        'photos' => [
            photo('The-Coffee-Lab-and-Lounge', 'The-Coffee-Lab-and-Lounge-01.jpg', 1024, 768, 'The Coffee Lab & Lounge: almond croissants', $WIP, 'https://whereinpampanga.com/the-art-of-the-croissant-at-the-coffee-lab-lounge-a-must-visit-cafe-in-angeles-city/'),
            photo('The-Coffee-Lab-and-Lounge', 'The-Coffee-Lab-and-Lounge-02.jpg', 1024, 768, 'The Coffee Lab & Lounge: assorted pastries', $WIP, 'https://whereinpampanga.com/the-art-of-the-croissant-at-the-coffee-lab-lounge-a-must-visit-cafe-in-angeles-city/'),
        ],
    ],
];

/** Rows added by the earlier 5-shop import that this list supersedes. */
$superseded = [
    ['name' => 'Mozaic Café, Bar + Kitchen', 'source_url' => 'https://mozaiccbk.com/'],
    ['name' => 'Arte Cafe and Restaurant', 'source_url' => 'https://www.artecafeandrestaurant.com/'],
];

$mode = $argv[1] ?? '';
if (!in_array($mode, ['', '--update', '--remove'], true)) {
    say('Usage: php database/import-listings.php [--update|--remove]');
    exit(1);
}

// Every photo file must exist before anything is written.
foreach ($listings as $l) {
    foreach ($l['photos'] as $ph) {
        if (!is_file(PUBLIC_ASSETS . $ph['path'])) {
            say('Missing image file: public/assets/' . $ph['path'] . '. Copy the photo folders into public/assets/img/places/ first.');
            exit(1);
        }
    }
}

try {
    $pdo = Database::pdo();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $cols = $driver === 'sqlite'
        ? array_column(Database::fetchAll('PRAGMA table_info(places)'), 'name')
        : array_column(Database::fetchAll('SHOW COLUMNS FROM places'), 'Field');
    $hasImages = $driver === 'sqlite'
        ? (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'place_images'")
        : Database::fetch("SHOW TABLES LIKE 'place_images'") !== null;
} catch (PDOException $e) {
    say('Could not connect to the database. Is MySQL running in XAMPP?');
    say('Details: ' . $e->getMessage());
    exit(1);
}
if (!in_array('is_demo', $cols, true) || !$hasImages) {
    say('Run the migration first: php database/migrate.php up');
    exit(1);
}

$findSql = 'SELECT id, status FROM places WHERE name = :name AND is_demo = 0 LIMIT 1';

if ($mode === '--remove') {
    $removed = 0;
    $hasReviews = $driver === 'sqlite'
        ? (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'reviews'")
        : Database::fetch("SHOW TABLES LIKE 'reviews'") !== null;
    $hasMenus = $driver === 'sqlite'
        ? (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'place_menus'")
        : Database::fetch("SHOW TABLES LIKE 'place_menus'") !== null;
    $hasItems = $driver === 'sqlite'
        ? (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'menu_items'")
        : Database::fetch("SHOW TABLES LIKE 'menu_items'") !== null;
    Database::transaction(function () use ($listings, $superseded, $hasReviews, $hasMenus, $hasItems, &$removed): void {
        foreach (array_merge($listings, $superseded) as $l) {
            // Only rows that still carry this script's source link are removed.
            $row = Database::fetch('SELECT id FROM places WHERE name = :name AND source_url = :src AND is_demo = 0',
                ['name' => $l['name'], 'src' => $l['source_url']]);
            if ($row) {
                Database::query('DELETE FROM place_images WHERE place_id = :id', ['id' => $row['id']]);
                if ($hasReviews) {
                    Database::query('DELETE FROM reviews WHERE place_id = :id', ['id' => $row['id']]);
                }
                if ($hasMenus) {
                    Database::query('DELETE FROM place_menus WHERE place_id = :id', ['id' => $row['id']]);
                }
                if ($hasItems) {
                    Database::query('DELETE FROM menu_items WHERE place_id = :id', ['id' => $row['id']]);
                }
                Database::query('DELETE FROM places WHERE id = :id', ['id' => $row['id']]);
                $removed++;
            }
        }
    });
    say("Removed {$removed} imported listing(s) with their photo records and reviews. Other places were not touched.");
    exit(0);
}

$adminId = Database::value('SELECT id FROM users ORDER BY id LIMIT 1');
if (!$adminId) {
    say('No admin account yet. Run: php database/seed.php');
    exit(1);
}
$categoryIds = [];
foreach (Database::fetchAll('SELECT id, name FROM categories') as $row) {
    $categoryIds[$row['name']] = (int) $row['id'];
}

$now = date('Y-m-d H:i:s');
$stats = ['added' => 0, 'updated' => 0, 'skipped' => 0, 'photos_added' => 0, 'photos_updated' => 0, 'retired' => 0];

// The 10 listings come from public sources, not from their real owners. They are
// managed by one clearly labelled test account that cannot log in (random password).
const DEMO_OWNER_EMAIL = 'demo-owner@localspot.test';
$demoOwnerId = Database::value('SELECT id FROM users WHERE email = :e', ['e' => DEMO_OWNER_EMAIL]);
if (!$demoOwnerId) {
    $demoOwnerId = Database::insert('users', [
        'name' => 'Demo Owner (test account)', 'email' => DEMO_OWNER_EMAIL,
        'password_hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
        'role' => 'owner', 'created_at' => $now,
    ]);
    say('Created the demo owner test account (it cannot log in).');
}

Database::transaction(function () use ($listings, $superseded, $categoryIds, $adminId, $demoOwnerId, $now, $mode, $findSql, &$stats): void {
    foreach ($superseded as $old) {
        $n = Database::query(
            "DELETE FROM places WHERE name = :name AND source_url = :src AND status = 'draft' AND is_demo = 0",
            ['name' => $old['name'], 'src' => $old['source_url']]
        )->rowCount();
        $stats['retired'] += $n;
    }

    foreach ($listings as $l) {
        if (!isset($categoryIds[$l['category']])) {
            throw new RuntimeException("Category \"{$l['category']}\" is missing. Run migrate.php up and seed.php.");
        }
        $fields = [
            'category_id' => $categoryIds[$l['category']],
            'description' => $l['description'],
            'address'     => $l['address'],
            'city'        => 'Angeles City',
            'setting'     => $l['setting'],
            'hours_note'  => $l['hours_note'],
            'payment_options' => PAYMENT_OPTIONS,
            'payment_note'    => PAYMENT_NOTE,
            'image_key'   => null,          // photos live in place_images now
            'source_url'  => $l['source_url'],
            'checked_on'  => CHECKED_ON,
            'owner_id'    => (int) $demoOwnerId,
            'updated_at'  => $now,
        ];
        $existing = Database::fetch($findSql, ['name' => $l['name']]);
        if ($existing) {
            $placeId = (int) $existing['id'];
            if ($mode === '--update') {
                Database::update('places', $fields, 'id = :id', ['id' => $placeId]);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        } else {
            // Budgets are owned by import-menus.php; new rows start unknown and
            // --update never touches them, so the Est. budgets survive a re-run.
            $placeId = Database::insert('places', $fields + [
                'budget_min' => null, 'budget_max' => null, 'price_unit' => null,
                'name' => $l['name'], 'created_by' => (int) $adminId, 'is_demo' => 0,
                'status' => $l['status'], 'created_at' => $now,
            ]);
            $stats['added']++;
        }

        // Published demo listings count as approved (there is no permit on file; staff pages say so).
        Database::query("UPDATE places SET approved_at = :now WHERE id = :id AND status = 'published' AND approved_at IS NULL",
            ['now' => $now, 'id' => $placeId]);

        foreach ($l['photos'] as $i => $ph) {
            $meta = ['sort_order' => $i + 1, 'alt' => $ph['alt'], 'credit' => $ph['credit'], 'source_url' => $ph['source_url'],
                     'width' => $ph['width'], 'height' => $ph['height']];
            $row = Database::fetch('SELECT id FROM place_images WHERE place_id = :p AND path = :path',
                ['p' => $placeId, 'path' => $ph['path']]);
            if (!$row) {
                Database::insert('place_images', $meta + ['place_id' => $placeId, 'path' => $ph['path'], 'created_at' => $now]);
                $stats['photos_added']++;
            } elseif ($mode === '--update') {
                Database::update('place_images', $meta, 'id = :id', ['id' => $row['id']]);
                $stats['photos_updated']++;
            }
        }
    }
});

say("Listings: added {$stats['added']}, updated {$stats['updated']}, already present {$stats['skipped']}.");
say("Photos: added {$stats['photos_added']}, updated {$stats['photos_updated']}.");
if ($stats['retired'] > 0) {
    say("Removed {$stats['retired']} superseded draft(s) from the earlier list (Mozaic, Arte).");
}
say('Published: 8. Draft until checked: WorkSpace45, The Coffee Lab & Lounge.');

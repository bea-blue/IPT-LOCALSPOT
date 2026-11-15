<?php
/**
 * Adds estimated budgets, menu notes and menu photos/links for the 8 published
 * listings. COMMAND LINE ONLY. Run migrate.php up and import-listings.php first.
 *
 *   C:\xampp\php\php.exe database\import-menus.php            apply (safe to run again)
 *   C:\xampp\php\php.exe database\import-menus.php --remove   clear what this script added
 *
 * Source: the student's "LocalSpot Shop Menus and Budgets" pack (Nov 15, 2026).
 * Rules followed:
 * - Budgets are ESTIMATES per person for food and one non-alcoholic drink, shown with
 *   "Est." and a note saying where the figure comes from. They are not quotations.
 * - Historical prices (2022, 2023) and delivery-app prices are kept out of the budget
 *   fields and shown only in the menu notes, labelled with their source and year.
 * - Menu photos are third-party uploads (Restaurant Guru), not official current menus;
 *   each is labelled that way with its source. Unreadable or unknown values are not filled in.
 * - Let's Drink Café has too little price evidence: its budget stays empty and is shown as
 *   "Budget awaiting verification".
 * Matching is by exact listing name; the listings must already exist.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../bootstrap.php';

use SpotCore\Database;

function say(string $text): void { fwrite(STDOUT, $text . PHP_EOL); }

const RG = 'Restaurant Guru user upload';
const RG_NOTE = 'Third-party photo, not an official menu. Upload date as listed; menu effective date unconfirmed.';

$data = [
    'Aling Lucing Sisig' => [
        'budget' => [350, 500],
        'budget_note' => 'Estimate for food and one non-alcoholic drink, based on Foodpanda delivery prices for the main branch (Nov 2026). Dine-in prices may differ.',
        'menu_note' => 'Foodpanda regular delivery prices before promos (Nov 2026): small sizzling sisig ₱285, medium sisig ₱590, lechon kawali ₱320, grilled boneless bangus ₱295, rice ₱40. Delivery prices can differ from dine-in.',
        'menus' => [
            ['kind' => 'link', 'url' => 'https://www.foodpanda.ph/restaurant/e2sl/aling-lucing-sisig-main', 'label' => 'Delivery menu on Foodpanda (main branch)', 'note' => 'Delivery prices; may differ from dine-in.'],
        ],
    ],
    'Beauté Beanery' => [
        'budget' => [450, 700],
        'budget_note' => 'Estimate based on 2023 menu prices and customer spending reports of ₱400–600 on Restaurant Guru.',
        'menu_note' => 'Historical 2023 prices (iOrbit News), not current: Salted Egg Carbonara ₱265, Chicken Pesto Cream ₱445, Creamy Beef Pot Roast ₱380, French Toast Latte ₱200.',
        'menus' => [
            ['kind' => 'image', 'path' => 'img/menus/Beaute_Beanery_menu.jpg', 'width' => 1200, 'height' => 800, 'label' => 'Full menu photo',
             'note' => 'Listed as uploaded about six months before Nov 2026. ' . RG_NOTE, 'credit' => RG,
             'source_url' => 'https://restaurantguru.com/Beaute-Beanery-Angeles/menu'],
        ],
    ],
    'Café Hola' => [
        'budget' => [300, 500],
        'budget_note' => 'Provisional estimate: Restaurant Guru directory range ₱200–400 plus a drink. Item prices not verified.',
        'menu_note' => 'Reported dishes (Where In Pampanga): Ambisyosang Manok, Kangkoy, Asadong Matua, Kilayin, pastries and cakes. Prices not verified.',
        'menus' => [],
    ],
    'Café Retro 252' => [
        'budget' => [350, 600],
        'budget_note' => 'Provisional estimate (Wanderlog, Restaurant Guru). Current item prices not verified.',
        'menu_note' => 'Reported categories: pasta, pizza, sandwiches, coffee and desserts. Current food prices not verified.',
        'menus' => [
            ['kind' => 'image', 'path' => 'img/menus/Cafe_Retro_252_menu.jpg', 'width' => 1200, 'height' => 1698, 'label' => 'Drinks and desserts menu photo',
             'note' => 'Shows drinks and desserts only. Listed as uploaded about a year before Nov 2026. ' . RG_NOTE, 'credit' => RG,
             'source_url' => 'https://restaurantguru.com/Retro-252-Angeles-2/menu'],
        ],
    ],
    'Hammer Resto/Café' => [
        'budget' => [500, 1000],
        'budget_note' => 'Estimate from Restaurant Guru. Premium steak orders may cost more.',
        'menu_note' => 'Reported (Where In Pampanga): steak, salmon, wood-fired pizza, Salmon Canapé, Western and Korean food. Prices not verified.',
        'menus' => [
            ['kind' => 'image', 'path' => 'img/menus/Hammer_Resto_Cafe_menu.jpg', 'width' => 1187, 'height' => 1674, 'label' => 'Risotto and pasta menu photo',
             'note' => 'Partial menu. Listed as uploaded about eight months before Nov 2026; branch not confirmed. ' . RG_NOTE, 'credit' => RG,
             'source_url' => 'https://restaurantguru.com/Hammer-Super-Club-Angeles/menu'],
        ],
    ],
    'Lala Garden Cafe' => [
        'budget' => [500, 700],
        'budget_note' => 'Estimate based on Foodpanda delivery prices (Nov 2026), e.g. Chicken Katsu ₱425 plus Spanish Latte from ₱199 = ₱624 before extra charges. Dine-in prices may differ.',
        'menu_note' => 'Foodpanda delivery prices (Nov 2026): Chicken Katsu ₱425, Cheesy Chicken Doria ₱370, Creamy Carbonara ₱389, Americano from ₱185, Spanish Latte from ₱199, plain croissant ₱159, blueberry cheesecake slice ₱219.',
        'menus' => [
            ['kind' => 'link', 'url' => 'https://www.foodpanda.ph/restaurant/smv9/lala-garden-cafe-angeles', 'label' => 'Delivery menu on Foodpanda', 'note' => 'Delivery prices; may differ from dine-in.'],
        ],
    ],
    'Let’s Drink Café' => [
        'budget' => null,
        'budget_note' => 'Awaiting verification: not enough price information yet.',
        'menu_note' => 'Reported menu (Where In Pampanga): silog meals, sizzling sisig, tokwa’t baboy, pasta, sandwiches, nachos, coffee and milk tea. Prices not verified.',
        'menus' => [],
    ],
    'Pineapple Coast' => [
        'budget' => [500, 1000],
        'budget_note' => 'Estimate for food and one non-alcoholic drink (Restaurant Guru cross-check). Excludes alcohol.',
        'menu_note' => 'Historical 2022 prices (Zoy To The World review), not current: Al Pastor tacos ₱290, Carne Asada ₱350, Birria Campechano ₱420, nachos ₱360.',
        'menus' => [
            ['kind' => 'image', 'path' => 'img/menus/Pineapple_Coast_menu_02.jpg', 'width' => 1200, 'height' => 1565, 'label' => 'Cocktail menu photo',
             'note' => 'Shows cocktails only, so it is not used for the food budget. Listed as uploaded about a year before Nov 2026. ' . RG_NOTE, 'credit' => RG,
             'source_url' => 'https://restaurantguru.com/Pineapple-Coast-Angeles/menu'],
        ],
    ],
];

/**
 * Typed-out menu items, read by hand from screenshots the student supplied on Nov 15, 2026.
 * The screenshots are titled "Aling Lucing Sisig - Friendship Highway Branch": a different
 * branch from the Glaciano Valdez main branch listed in LocalSpot, so the source says so.
 * Only names and prices are copied; the listing's own descriptions are not.
 * The screenshots may not show every item in every section.
 */
$menuItems = [
    'Aling Lucing Sisig' => [
        'source' => 'Friendship Highway branch online menu, read from screenshots on Nov 15, 2026. Prices may differ at the Glaciano Valdez main branch and may have changed.',
        'sections' => [
            'Favorites' => [['Lechon Kawali', 320], ['Patatim', 650], ['Sisig (good for 2)', 310], ['Chicharong Bulaklak', 285], ['Tokwa’t Baboy', 310]],
            'Bbq from the Griller' => [['Pork Barbeque', 85], ['Pinaputok na Tilapya', 180], ['Liempo', 300], ['Paldewut', 120], ['Chicken Leg Quarter', 155]],
            'Salads and Vegetables' => [['Paco Salad with Salted Egg', 150], ['Salted Egg', 150], ['Ensalada Mangga', 125]],
            'Soup' => [['Sinigang na Salmon Belly', 450], ['Bulalo', 450]],
            'Rice' => [['Plain Rice (1 cup)', 35]],
            'Drinks' => [['Coke in Can', 60], ['Coke Zero in Can', 60], ['Sprite in Can', 60], ['Royal in Can', 60],
                         ['Coke 1.5 Liter', 150], ['Coke Zero 1.5 Liter', 150], ['Sprite 1.5 Liter', 150], ['Royal 1.5 Liter', 150]],
            'Pasalubong' => [['Taba ng Talangka (Crab Paste)', 350]],
        ],
    ],
];

$remove = ($argv[1] ?? '') === '--remove';
if (($argv[1] ?? '') !== '' && !$remove) {
    say('Usage: php database/import-menus.php [--remove]');
    exit(1);
}

try {
    $pdo = Database::pdo();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $cols = $driver === 'sqlite'
        ? array_column(Database::fetchAll('PRAGMA table_info(places)'), 'name')
        : array_column(Database::fetchAll('SHOW COLUMNS FROM places'), 'Field');
} catch (PDOException $e) {
    say('Could not connect to the database. Is MySQL running in XAMPP?');
    exit(1);
}
if (!in_array('budget_is_estimate', $cols, true)) {
    say('Run the migration first: php database/migrate.php up');
    exit(1);
}

foreach ($data as $d) {
    foreach ($d['menus'] as $m) {
        if ($m['kind'] === 'image' && !is_file(__DIR__ . '/../public/assets/' . $m['path'])) {
            say('Missing menu image: public/assets/' . $m['path']);
            exit(1);
        }
    }
}

$now = date('Y-m-d H:i:s');
$updated = $menusAdded = $missing = 0;

$hasItems = $driver === 'sqlite'
    ? (bool) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'menu_items'")
    : Database::fetch("SHOW TABLES LIKE 'menu_items'") !== null;
if (!$remove && !$hasItems) {
    say('Run the migration first: php database/migrate.php up');
    exit(1);
}
$itemsWritten = 0;

Database::transaction(function () use ($data, $menuItems, $hasItems, $remove, $now, &$updated, &$menusAdded, &$missing, &$itemsWritten): void {
    foreach ($data as $name => $d) {
        $place = Database::fetch('SELECT id FROM places WHERE name = :n AND is_demo = 0 LIMIT 1', ['n' => $name]);
        if (!$place) {
            say("Not found (skipped): {$name}. Run import-listings.php first.");
            $missing++;
            continue;
        }
        $id = (int) $place['id'];

        if ($remove) {
            Database::query('DELETE FROM place_menus WHERE place_id = :id', ['id' => $id]);
            if ($hasItems) {
                Database::query('DELETE FROM menu_items WHERE place_id = :id', ['id' => $id]);
            }
            Database::update('places', [
                'budget_min' => null, 'budget_max' => null, 'price_unit' => null, 'budget_is_estimate' => 0,
                'budget_note' => null, 'menu_note' => null, 'updated_at' => $now,
            ], 'id = :id', ['id' => $id]);
            $updated++;
            continue;
        }

        Database::update('places', [
            'budget_min'         => $d['budget'][0] ?? null,
            'budget_max'         => $d['budget'][1] ?? null,
            'price_unit'         => $d['budget'] ? 'person' : null,
            'budget_is_estimate' => $d['budget'] ? 1 : 0,
            'budget_note'        => $d['budget_note'],
            'menu_note'          => $d['menu_note'],
            'updated_at'         => $now,
        ], 'id = :id', ['id' => $id]);
        $updated++;

        // Typed-out items are replaced as a set, so re-running gives the same result.
        if (isset($menuItems[$name])) {
            Database::query('DELETE FROM menu_items WHERE place_id = :id', ['id' => $id]);
            $order = 1;
            foreach ($menuItems[$name]['sections'] as $section => $items) {
                foreach ($items as [$itemName, $price]) {
                    Database::insert('menu_items', [
                        'place_id' => $id, 'section' => $section, 'name' => $itemName, 'price' => $price,
                        'source' => $menuItems[$name]['source'], 'sort_order' => $order++, 'created_at' => $now,
                    ]);
                    $itemsWritten++;
                }
            }
        }

        foreach ($d['menus'] as $i => $m) {
            $key = $m['kind'] === 'image' ? ['col' => 'path', 'val' => $m['path']] : ['col' => 'url', 'val' => $m['url']];
            $exists = Database::fetch("SELECT id FROM place_menus WHERE place_id = :p AND {$key['col']} = :v", ['p' => $id, 'v' => $key['val']]);
            $row = [
                'kind' => $m['kind'], 'path' => $m['path'] ?? null, 'url' => $m['url'] ?? null, 'label' => $m['label'],
                'note' => $m['note'] ?? null, 'credit' => $m['credit'] ?? null, 'source_url' => $m['source_url'] ?? ($m['url'] ?? null),
                'width' => $m['width'] ?? null, 'height' => $m['height'] ?? null, 'sort_order' => $i + 1,
            ];
            if ($exists) {
                Database::update('place_menus', $row, 'id = :id', ['id' => $exists['id']]);
            } else {
                Database::insert('place_menus', $row + ['place_id' => $id, 'created_at' => $now]);
                $menusAdded++;
            }
        }
    }
});

say($remove
    ? "Cleared budgets, menu notes and menus for {$updated} listing(s)."
    : "Updated {$updated} listing(s); added {$menusAdded} menu record(s); wrote {$itemsWritten} typed menu item(s)." . ($missing ? " {$missing} listing(s) not found." : ''));

# Listing sources and open questions

Checked: Nov 15, 2026. Imported by `database/import-listings.php` (10 listings, 20 photos).
Finding a page about a venue does not confirm it is open today. No listing has a price
confirmed by the venue: the budgets shown are estimates marked "Est.", added later by
`database/import-menus.php` (see "Budgets and menus" below), or "Budget awaiting verification"
where no usable figure was found. Hours appear only where a source states them, with the year
(or "listing date unknown").

Rule used: **published** when I could see a specific street address on a source page and
nothing suggested the business closed. **Draft** otherwise.

| Listing | Status | What the sources support | Still needs checking |
|---|---|---|---|
| Lala Garden Cafe | Published | Lot 22, Poinsettia Ave, Golden Dragon Village, Brgy. Pampang (Where In Pampanga; Windowseat.ph, 2023). Hours daily 8 AM to 12 MN (2023). | Current hours, prices, still open |
| Aling Lucing Sisig | Published | Glaciano Valdez St (Philstar 2014, TheSmartLocal, Zoy To The World food guide); Brgy. Agapito del Rosario (Century Properties, TravelBelles). | Hours, prices, seating |
| Hammer Resto/Café | Published | Unit 2E, G/F, Glenn Heinen Bldg., Fields Ave, Red Street, Brgy. Balibago; steak, salmon, wood-fired pizza; "open 24 hours" (Where In Pampanga; photos uploaded Oct 2025). | Current hours, prices |
| Let’s Drink Café | Published | Building 290 Unit B, Friendship Highway, in front of Donenoo Korean Restaurant; drinks, food, spa services; hours 7 PM to 5 AM Mon–Sat, 7 PM to 3 AM Sun (Where In Pampanga, undated). | Listing date, current hours, prices |
| Café Hola | Published | Don Juan D. Nepomuceno Ave., Nepo Center; at Nepo Quad; hours Mon–Sun 10 AM to 9 PM (Where In Pampanga, undated first-anniversary article). | Current hours, prices |
| Beauté Beanery | Published | Lot 2 Blk 19 cor. San Jose St., MacArthur Hwy, Sto. Domingo (iOrbit News, Jan 2023); Ilocano dishes, open 11 AM to 9 PM (ABS-CBN, Sep 2024). | Current hours, prices |
| Pineapple Coast | Published | 8A Zeppelin St., Hensonville (Zoy To The World; Awesome.blog, Apr 2022); tacos and bar; Tue–Sun 5 PM to 11 PM (2022). Indoor and outdoor seating shown in the photos. | Current hours, prices |
| Café Retro 252 | Published | 1F Retro 252 Jake Building, Lot 13-15, Block 4, Cuayan, Friendship Highway (Pinned.ph). Photos from Safe Space blog (Jan 2022). The pack's own note said the address needed reconfirming; Pinned.ph gave the full address. | Still open, hours, prices |
| WorkSpace45 | Draft | Level 2, HotSpot Building, MacArthur Highway (Coworker.com; business website). Website photos date from 2015 and rates from 2019. | Whether it still operates; current rates |
| The Coffee Lab & Lounge | Draft | Address (G/F Marlim Mansions Hotel, MacArthur Hwy cor. Marlim Ave., Balibago) comes only from the photo pack's source page, which I could not open; no second source found. | Address, still open, hours, prices |

Superseded: Mozaic Café, Bar + Kitchen and Arte Cafe and Restaurant (earlier 5-shop list).
The import removes their earlier draft rows only if they are still drafts with the original source link.

To publish a draft: confirm the missing details, edit it in the admin panel, set Status to Published.

## Budgets and menus (added Nov 15, 2026, from the student's menus pack)

Imported by `database/import-menus.php`. Budgets are **estimates per person for food and one
non-alcoholic drink**, shown with "Est." and a note on where the figure comes from. They are not
quotes and exclude delivery, service charges, alcohol, karaoke and spa services. Historical and
delivery prices are kept out of the budget fields and shown only in the menu notes, labelled.

| Listing | Est. budget | Basis | Menu shown |
|---|---|---|---|
| Aling Lucing Sisig | ₱350–500 | Foodpanda delivery prices, main branch (Nov 2026) | Link: Foodpanda delivery menu |
| Beauté Beanery | ₱450–700 | 2023 menu prices (iOrbit News) + Restaurant Guru spending reports ₱400–600 | Photo: full menu (Restaurant Guru upload) |
| Café Hola | ₱300–500 (provisional) | Restaurant Guru range ₱200–400 plus a drink | None (reported dishes only) |
| Café Retro 252 | ₱350–600 (provisional) | Wanderlog, Restaurant Guru; item prices not verified | Photo: drinks and desserts only |
| Hammer Resto/Café | ₱500–1,000 | Restaurant Guru; premium steaks may exceed | Photo: partial risotto and pasta menu; branch not confirmed |
| Lala Garden Cafe | ₱500–700 | Foodpanda delivery prices (Nov 2026) | Link: Foodpanda delivery menu |
| Let’s Drink Café | Awaiting verification | Not enough price evidence | None (reported dishes only) |
| Pineapple Coast | ₱500–1,000 | Restaurant Guru cross-check; excludes alcohol | Photo: cocktail menu only (not used for the budget) |

Pack issues found: `Cafe_Retro_252_menu.jpg` and `Cafe_Retro_252_menu_02.jpg` are the same file
(identical SHA-256), so only one is used. The two Pineapple Coast menu photos show the same cocktail
page; the clearer one (`Pineapple_Coast_menu_02.jpg`) is used. Prices were not read out of the photos.

### Aling Lucing typed menu

25 items (names and prices only) typed from screenshots the student supplied on Nov 15, 2026,
titled "Aling Lucing Sisig - Friendship Highway Branch". That is a different branch from the
Glaciano Valdez main branch listed in LocalSpot, and the page says so. The screenshots may not
show every item. The listing's own item descriptions were not copied.

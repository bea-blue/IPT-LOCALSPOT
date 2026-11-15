-- LocalSpot database schema (MySQL / MariaDB, InnoDB, utf8mb4)
-- Import with phpMyAdmin (Import tab) or:
--   C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
-- Safe to re-run: it only creates what is missing and never deletes data.

CREATE DATABASE IF NOT EXISTS localspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE localspot;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL DEFAULT 'member', -- developer | manager | employee (staff), owner, member
    created_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS places (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED  NOT NULL,
    created_by  INT UNSIGNED  NOT NULL,
    owner_id    INT UNSIGNED  NULL,                -- the shop owner who manages this listing
    name        VARCHAR(150)  NOT NULL,
    description TEXT          NOT NULL,
    address     VARCHAR(255)  NOT NULL,
    city        VARCHAR(100)  NOT NULL DEFAULT 'Angeles City',
    budget_min  DECIMAL(10,2) NULL,                -- starting price in PHP; NULL = not confirmed
    budget_max  DECIMAL(10,2) NULL,                -- >= budget_min (checked by the app); NULL = not confirmed
    price_unit  VARCHAR(20)   NULL,                -- person | meal | hour | day_pass (app allowlist)
    budget_is_estimate TINYINT(1) NOT NULL DEFAULT 0, -- 1 = planning estimate, shown with "Est."
    budget_note VARCHAR(255)  NULL,                -- where the budget figure comes from
    setting     VARCHAR(10)   NULL,                -- indoor | outdoor | both; NULL = not confirmed
    hours_note  VARCHAR(255)  NULL,                -- free text, not live open/closed status
    payment_options VARCHAR(255) NULL,             -- e.g. "Cash and online payment (GCash, e-wallets)"
    payment_note VARCHAR(255) NULL,                -- where the payment info comes from
    menu_note   TEXT          NULL,                -- reported dishes/prices, labelled by source and date
    image_key   VARCHAR(50)   NULL,                -- key from config/images.php; NULL = "Photo coming soon"
    source_url  VARCHAR(255)  NULL,                -- where the details were checked
    checked_on  DATE          NULL,                -- when they were checked
    is_demo     TINYINT(1)    NOT NULL DEFAULT 0,  -- 1 = fictional demonstration record
    status      VARCHAR(10)   NOT NULL DEFAULT 'draft', -- draft | pending | published | rejected | suspended | hidden
    review_note VARCHAR(500)  NULL,                -- staff reason for a rejection or suspension
    submitted_at DATETIME     NULL,
    reviewed_at DATETIME      NULL,
    reviewed_by INT UNSIGNED  NULL,                -- staff member who approved / rejected / suspended
    approved_at DATETIME      NULL,
    created_at  DATETIME      NOT NULL,
    updated_at  DATETIME      NOT NULL,
    PRIMARY KEY (id),
    KEY places_category_index (category_id),
    KEY places_created_by_index (created_by),
    KEY places_owner_index (owner_id),
    KEY places_status_index (status),
    CONSTRAINT places_category_fk FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT places_owner_fk FOREIGN KEY (owner_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT places_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT places_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos of each place (two per sourced listing). sort_order 1 = card photo.
-- path is relative to public/assets/ and is written only by database/import-listings.php.
CREATE TABLE IF NOT EXISTS place_images (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    place_id    INT UNSIGNED      NOT NULL,
    path        VARCHAR(255)      NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    alt         VARCHAR(255)      NOT NULL,
    credit      VARCHAR(255)      NOT NULL,   -- source credit; NOT a reuse licence
    source_url  VARCHAR(255)      NULL,
    width       SMALLINT UNSIGNED NOT NULL,
    height      SMALLINT UNSIGNED NOT NULL,
    created_at  DATETIME          NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY place_images_place_path_unique (place_id, path),
    CONSTRAINT place_images_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews by signed-in users, shown as soon as they are posted. The author can edit or
-- delete their own review; the admin can delete any review.
CREATE TABLE IF NOT EXISTS reviews (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    place_id    INT UNSIGNED     NOT NULL,
    user_id     INT UNSIGNED     NULL,           -- NULL for reviews posted before accounts existed
    author_name VARCHAR(60)      NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,      -- 1 to 5 (checked by the app)
    comment     TEXT             NOT NULL,
    is_hidden   TINYINT(1)       NOT NULL DEFAULT 0, -- hidden by staff after a report
    created_at  DATETIME         NOT NULL,
    PRIMARY KEY (id),
    KEY reviews_place_index (place_id),
    KEY reviews_user_index (user_id),
    CONSTRAINT reviews_place_fk FOREIGN KEY (place_id) REFERENCES places (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT reviews_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu photos and menu links for a place, with their source. Third-party photos are not
-- presented as official current menus. Written by database/import-menus.php.
CREATE TABLE IF NOT EXISTS place_menus (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    place_id    INT UNSIGNED      NOT NULL,
    kind        VARCHAR(10)       NOT NULL,   -- image | link
    path        VARCHAR(255)      NULL,       -- image file under public/assets/ (kind = image)
    url         VARCHAR(255)      NULL,       -- external menu page (kind = link)
    label       VARCHAR(255)      NOT NULL,
    note        VARCHAR(255)      NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Typed-out menu items (section, name, price) with where and when they were read.
CREATE TABLE IF NOT EXISTS menu_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Private evidence an owner uploads with a shop (business permit, shop photo, other).
-- Files live in storage/uploads/documents (outside public/) and only staff and the owner can open them.
CREATE TABLE IF NOT EXISTS place_documents (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    place_id      INT UNSIGNED NOT NULL,
    kind          VARCHAR(20)  NOT NULL,   -- permit | shop_photo | other
    stored_name   VARCHAR(64)  NOT NULL,   -- random name given by the server
    original_name VARCHAR(150) NOT NULL,
    file_type     VARCHAR(10)  NOT NULL,   -- jpeg | png | webp | pdf (detected from the file's bytes)
    size          INT UNSIGNED NOT NULL,
    uploaded_by   INT UNSIGNED NULL,
    created_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY place_documents_place_index (place_id),
    CONSTRAINT place_documents_place_fk FOREIGN KEY (place_id) REFERENCES places (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT place_documents_user_fk FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes staff write while checking a submission ("permit name matches the shop").
CREATE TABLE IF NOT EXISTS place_checks (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    place_id   INT UNSIGNED  NOT NULL,
    user_id    INT UNSIGNED  NULL,
    note       VARCHAR(1000) NOT NULL,
    created_at DATETIME      NOT NULL,
    PRIMARY KEY (id),
    KEY place_checks_place_index (place_id),
    CONSTRAINT place_checks_place_fk FOREIGN KEY (place_id) REFERENCES places (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT place_checks_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A signed-in user reports a review once (spam | abusive | off_topic); staff hide it or dismiss the report.
CREATE TABLE IF NOT EXISTS review_reports (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    review_id   INT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    reason      VARCHAR(20)  NOT NULL,
    note        VARCHAR(500) NULL,
    status      VARCHAR(10)  NOT NULL DEFAULT 'open', -- open | hidden | dismissed
    handled_by  INT UNSIGNED NULL,
    handled_at  DATETIME     NULL,
    created_at  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY review_reports_once (review_id, reporter_id),
    KEY review_reports_status_index (status),
    CONSTRAINT review_reports_review_fk FOREIGN KEY (review_id) REFERENCES reviews (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT review_reports_reporter_fk FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT review_reports_handler_fk FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablerino — D1 (SQLite) schema
-- Ported from the original MySQL data/schema.sql, with columns that the PHP
-- code used but were missing from the original schema folded in, and the
-- duplicate `reclame` definition resolved to the variant the code expects
-- (nullable titlu + durata).

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS restaurante (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    nume            TEXT NOT NULL,
    email           TEXT NOT NULL UNIQUE,
    parola          TEXT NOT NULL,                 -- bcrypt hash
    telefon         TEXT,
    tema            TEXT DEFAULT 'italian',
    logo            TEXT,
    text_bun_venit  TEXT,
    bg_imagine      TEXT,
    facebook        TEXT,
    instagram       TEXT,
    tiktok          TEXT,
    whatsapp        TEXT,
    limba           TEXT DEFAULT 'ro',
    parola_reset    TEXT,                          -- bcrypt hash for audit actions
    rezervare_token TEXT,                          -- public token for the reservation link
    rez_capacitate  INTEGER,                       -- total seats for reservations (NULL = sum of table capacities)
    rezervari_publice INTEGER DEFAULT 0,           -- 1 = listed on the public Tablerino reservations directory
    descriere       TEXT,                          -- short public description (shown on the reservations directory)
    categorie       TEXT,                          -- restaurant type (e.g. Pizzerie, Cafenea), for the public directory
    oras            TEXT,                          -- city (for search/filter on the public portal)
    evenimente_publice   INTEGER DEFAULT 0,        -- 1 = offers event spaces (weddings, baptisms, parties) — shown publicly
    evenimente_descriere TEXT,                      -- public description of event offerings (halls, menus)
    evenimente_capacitate INTEGER,                  -- max guests for an event (NULL = unspecified)
    promovat        INTEGER DEFAULT 0,             -- 1 = featured/promoted on the portal (set by platform admin)
    abonament_status TEXT DEFAULT 'activ',         -- activ | trial | expirat | anulat
    abonament_pret  REAL,                          -- monthly price (NULL = default 100)
    abonament_expira TEXT,                          -- next-payment / expiry date (YYYY-MM-DD)
    escalare_sec    INTEGER,                       -- seconds an order stays reserved for the table's waiter (NULL = default 120)
    activ           INTEGER DEFAULT 1,             -- 0 = pending approval
    created_at      TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mese (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    token         TEXT NOT NULL UNIQUE,
    capacitate    INTEGER,                       -- max persons per table (NULL = unspecified)
    ospatar_id    INTEGER,                       -- responsible waiter (NULL = none); new orders are reserved for them 2 min
    activa        INTEGER DEFAULT 1,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meniu_categorii (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    ordine        INTEGER DEFAULT 0,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meniu_produse (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    categorie_id  INTEGER NOT NULL,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    descriere     TEXT,
    pret          REAL NOT NULL,
    poza          TEXT,
    disponibil    INTEGER DEFAULT 1,
    ordine        INTEGER DEFAULT 0,
    ingrediente   TEXT,
    alergeni      TEXT,
    calorii       INTEGER,
    proteine      REAL,
    carbohidrati  REAL,
    grasimi       REAL,
    FOREIGN KEY (categorie_id) REFERENCES meniu_categorii(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS comenzi (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    masa_id       INTEGER NOT NULL,
    restaurant_id INTEGER NOT NULL,
    nr_ordine_zi  INTEGER,
    status        TEXT DEFAULT 'noua'
                    CHECK (status IN ('noua','in_pregatire','servita','plata_aleasa')),
    metoda_plata  TEXT CHECK (metoda_plata IN ('cash','card')),
    observatii    TEXT,
    preluat_de    INTEGER,                 -- waiter (ospatari.id) who claimed it, or NULL
    preluat_la    TEXT,                    -- when it was claimed (for time-to-claim stats)
    push_nou_trimis     INTEGER DEFAULT 0, -- new-order push already sent
    push_escalare_trimis INTEGER DEFAULT 0,-- escalation-warning push already sent
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (masa_id) REFERENCES mese(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

-- SQLite has no ON UPDATE CURRENT_TIMESTAMP; emulate with a trigger.
CREATE TRIGGER IF NOT EXISTS comenzi_updated_at
AFTER UPDATE ON comenzi
FOR EACH ROW BEGIN
    UPDATE comenzi SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TABLE IF NOT EXISTS comanda_produse (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    comanda_id   INTEGER NOT NULL,
    produs_id    INTEGER NOT NULL,
    nume_produs  TEXT NOT NULL,
    pret_unitar  REAL NOT NULL,
    cantitate    INTEGER DEFAULT 1,
    total        REAL NOT NULL,
    status       TEXT DEFAULT 'nou' CHECK (status IN ('nou','livrat')),
    pregatit     INTEGER DEFAULT 0,       -- kitchen (KDS) prepared flag, independent of waiter delivery
    FOREIGN KEY (comanda_id) REFERENCES comenzi(id) ON DELETE CASCADE,
    FOREIGN KEY (produs_id) REFERENCES meniu_produse(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS master_users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    email      TEXT NOT NULL UNIQUE,
    parola     TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Waiter accounts, created by a restaurant owner. They share the restaurant's
-- data but have a limited role (orders + product availability only).
CREATE TABLE IF NOT EXISTS ospatari (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id   INTEGER NOT NULL,
    nume            TEXT NOT NULL,
    email           TEXT NOT NULL UNIQUE,
    parola          TEXT NOT NULL,
    activ           INTEGER DEFAULT 1,
    masa_default_id INTEGER,                 -- default table the waiter handles
    created_at      TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE,
    FOREIGN KEY (masa_default_id) REFERENCES mese(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reclame (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    titlu         TEXT,
    text          TEXT,
    imagine       TEXT,
    durata        INTEGER DEFAULT 5,
    activa        INTEGER DEFAULT 1,
    ordine        INTEGER DEFAULT 0,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

-- Landing page customization (key/value), read by the public home page.
CREATE TABLE IF NOT EXISTS landing_settings (
    cheie   TEXT PRIMARY KEY,
    valoare TEXT
);

-- Table reservations made through the public reservation link (inspired by ialoc.ro).
CREATE TABLE IF NOT EXISTS rezervari (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume_client   TEXT NOT NULL,
    telefon       TEXT NOT NULL,
    email         TEXT,
    data          TEXT NOT NULL,                 -- YYYY-MM-DD
    ora           TEXT NOT NULL,                 -- HH:MM
    nr_persoane   INTEGER NOT NULL DEFAULT 2,
    observatii    TEXT,
    masa_id       INTEGER,                       -- assigned table (optional)
    status        TEXT DEFAULT 'noua'
                    CHECK (status IN ('noua','confirmata','anulata','onorata','no_show')),
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE,
    FOREIGN KEY (masa_id) REFERENCES mese(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_comenzi_masa ON comenzi(masa_id, status);
CREATE INDEX IF NOT EXISTS idx_comenzi_restaurant ON comenzi(restaurant_id, status);
CREATE INDEX IF NOT EXISTS idx_produse_categorie ON meniu_produse(categorie_id);
CREATE INDEX IF NOT EXISTS idx_categorii_restaurant ON meniu_categorii(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_mese_restaurant ON mese(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_reclame_restaurant ON reclame(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_comanda_produse_comanda ON comanda_produse(comanda_id);
CREATE INDEX IF NOT EXISTS idx_rezervari_restaurant ON rezervari(restaurant_id, data);

-- Verified reviews left from the tablet by a customer who ordered at a table.
CREATE TABLE IF NOT EXISTS recenzii (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    masa_id       INTEGER,
    rating        INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comentariu    TEXT,
    nume          TEXT,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE,
    FOREIGN KEY (masa_id) REFERENCES mese(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_recenzii_restaurant ON recenzii(restaurant_id);

-- Key/value app configuration (e.g. VAPID keys for Web Push). Values are secrets
-- kept in the database rather than in source.
CREATE TABLE IF NOT EXISTS app_config (
    cheie   TEXT PRIMARY KEY,
    valoare TEXT NOT NULL
);

-- Web Push subscriptions, one row per waiter device/browser.
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    ospatar_id    INTEGER NOT NULL,
    endpoint      TEXT NOT NULL UNIQUE,
    p256dh        TEXT NOT NULL,
    auth          TEXT NOT NULL,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE,
    FOREIGN KEY (ospatar_id) REFERENCES ospatari(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_push_ospatar ON push_subscriptions(ospatar_id);

-- FCM device tokens for the native Android app (background push via Firebase).
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    ospatar_id    INTEGER NOT NULL,
    token         TEXT NOT NULL UNIQUE,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE,
    FOREIGN KEY (ospatar_id) REFERENCES ospatari(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fcm_ospatar ON fcm_tokens(ospatar_id);

-- Offers/promotions shown on the public Tablerino portal (per restaurant).
CREATE TABLE IF NOT EXISTS oferte (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    titlu         TEXT NOT NULL,                   -- e.g. "-20% la prânz"
    detaliu       TEXT,                            -- e.g. "Luni–Vineri, 12:00–16:00"
    activa        INTEGER DEFAULT 1,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_oferte_restaurant ON oferte(restaurant_id);

-- Event-space requests (weddings, baptisms, parties) submitted by clients from the
-- public restaurant page; the restaurant manages them like leads.
CREATE TABLE IF NOT EXISTS evenimente (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id  INTEGER NOT NULL,
    tip            TEXT,                            -- nunta | botez | aniversare | corporate | altul
    data_eveniment TEXT,                            -- YYYY-MM-DD (optional)
    nr_invitati    INTEGER,                         -- estimated guests (optional)
    nume           TEXT NOT NULL,                   -- contact name
    telefon        TEXT,
    email          TEXT,
    mesaj          TEXT,                            -- free-text details
    status         TEXT NOT NULL DEFAULT 'nou',     -- nou | contactat | confirmat | anulat
    sala           TEXT,
    pachet_id          INTEGER,                     -- optional reference to a saved event-menu package
    meniu_json         TEXT,                        -- JSON snapshot of the custom menu for this event
    meniu_persoane     INTEGER,                     -- guest count used for the menu total
    meniu_pret_persoana REAL,                       -- price per guest
    meniu_total        REAL,                        -- computed total (persoane * pret_persoana)
    creat_la       TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_evenimente_restaurant ON evenimente(restaurant_id);

-- Reusable event-menu packages (e.g. "Nuntă Standard", "Botez Premium") per restaurant.
CREATE TABLE IF NOT EXISTS meniu_evenimente_pachete (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    descriere     TEXT,
    pret_persoana REAL DEFAULT 0,
    continut      TEXT,                              -- JSON: [{sectiune, feluri:[...]}, ...]
    activ         INTEGER DEFAULT 1,
    ordine        INTEGER DEFAULT 0,
    creat_la      TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_mepachete_restaurant ON meniu_evenimente_pachete(restaurant_id);

-- Event halls / spaces catalog (per restaurant) — shown on the public event form.
CREATE TABLE IF NOT EXISTS sali (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id  INTEGER NOT NULL,
    nume           TEXT NOT NULL,                   -- e.g. "Salonul Mare"
    capacitate     INTEGER,                         -- max guests for this hall
    descriere      TEXT,
    poza           TEXT,                            -- R2 image path
    ordine         INTEGER DEFAULT 0,
    activa         INTEGER DEFAULT 1,
    created_at     TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_sali_restaurant ON sali(restaurant_id);

-- Owner-side web-push subscriptions (new event requests land as push for the owner).
CREATE TABLE IF NOT EXISTS owner_push_subscriptions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    endpoint      TEXT NOT NULL UNIQUE,
    p256dh        TEXT NOT NULL,
    auth          TEXT NOT NULL,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_owner_push_restaurant ON owner_push_subscriptions(restaurant_id);

-- Subscription payments history (online billing — Netopia/PayU, processor-agnostic).
CREATE TABLE IF NOT EXISTS abonament_plati (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id  INTEGER NOT NULL,
    suma           REAL NOT NULL,
    valuta         TEXT DEFAULT 'RON',
    perioada_start TEXT,                            -- YYYY-MM-DD covered period start
    perioada_end   TEXT,                            -- YYYY-MM-DD covered period end
    status         TEXT NOT NULL DEFAULT 'initiata',-- initiata | platita | esuata | anulata
    procesator     TEXT,                            -- netopia | payu | manual
    ref_extern     TEXT,                            -- processor transaction / order id
    creat_la       TEXT NOT NULL DEFAULT (datetime('now')),
    platit_la      TEXT,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_abonament_plati_restaurant ON abonament_plati(restaurant_id);

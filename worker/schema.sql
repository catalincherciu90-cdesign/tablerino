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
    activ           INTEGER DEFAULT 1,             -- 0 = pending approval
    created_at      TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mese (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    token         TEXT NOT NULL UNIQUE,
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
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL,
    nume          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    parola        TEXT NOT NULL,
    activ         INTEGER DEFAULT 1,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
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

CREATE INDEX IF NOT EXISTS idx_comenzi_masa ON comenzi(masa_id, status);
CREATE INDEX IF NOT EXISTS idx_comenzi_restaurant ON comenzi(restaurant_id, status);
CREATE INDEX IF NOT EXISTS idx_produse_categorie ON meniu_produse(categorie_id);
CREATE INDEX IF NOT EXISTS idx_categorii_restaurant ON meniu_categorii(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_mese_restaurant ON mese(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_reclame_restaurant ON reclame(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_comanda_produse_comanda ON comanda_produse(comanda_id);

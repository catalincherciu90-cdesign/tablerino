-- HOSTIO - Schema baza de date

CREATE TABLE IF NOT EXISTS restaurante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    parola VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    tema VARCHAR(50) DEFAULT 'italian',
    logo VARCHAR(255) DEFAULT NULL,
    text_bun_venit VARCHAR(255) DEFAULT NULL,
    bg_imagine VARCHAR(255) DEFAULT NULL,
    activ TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mese (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    nume VARCHAR(50) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    activa TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meniu_categorii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    nume VARCHAR(100) NOT NULL,
    ordine INT DEFAULT 0,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meniu_produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    nume VARCHAR(150) NOT NULL,
    descriere TEXT,
    pret DECIMAL(10,2) NOT NULL,
    poza VARCHAR(255),
    disponibil TINYINT DEFAULT 1,
    ordine INT DEFAULT 0,
    FOREIGN KEY (categorie_id) REFERENCES meniu_categorii(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS comenzi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    masa_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    nr_ordine_zi INT DEFAULT NULL,
    status ENUM('noua','in_pregatire','servita','plata_aleasa') DEFAULT 'noua',
    metoda_plata ENUM('cash','card') DEFAULT NULL,
    observatii TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (masa_id) REFERENCES mese(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS comanda_produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comanda_id INT NOT NULL,
    produs_id INT NOT NULL,
    nume_produs VARCHAR(150) NOT NULL,
    pret_unitar DECIMAL(10,2) NOT NULL,
    cantitate INT DEFAULT 1,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('nou','livrat') DEFAULT 'nou',
    FOREIGN KEY (comanda_id) REFERENCES comenzi(id) ON DELETE CASCADE,
    FOREIGN KEY (produs_id) REFERENCES meniu_produse(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS master_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    parola VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_comenzi_masa ON comenzi(masa_id, status);

CREATE TABLE IF NOT EXISTS reclame (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    titlu VARCHAR(150) NOT NULL,
    text VARCHAR(255) DEFAULT NULL,
    imagine VARCHAR(255) DEFAULT NULL,
    activa TINYINT DEFAULT 1,
    ordine INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reclame (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    titlu VARCHAR(150) DEFAULT NULL,
    text VARCHAR(255) DEFAULT NULL,
    imagine VARCHAR(255) DEFAULT NULL,
    durata INT DEFAULT 5,
    activa TINYINT DEFAULT 1,
    ordine INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurante(id) ON DELETE CASCADE
);

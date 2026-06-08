# Tablerino - Platformă comenzi restaurant

## Structura

```
tablerino/
├── config.php              # Configurare DB (generat la setup)
├── setup.php               # Pagina de instalare
├── .htaccess
├── data/
│   └── schema.sql          # Schema baza de date
├── admin/                  # Dashboard restaurant
│   ├── login.php
│   ├── index.php           # Comenzi live
│   ├── meniu.php           # Gestionare meniu
│   ├── mese.php            # Gestionare mese
│   ├── api.php             # API privat (autentificat)
│   └── logout.php
└── masa/                   # Interfata tableta
    ├── index.php           # Pagina de comanda
    └── api.php             # API public (acces prin token)
```

## Instalare

1. Copiaza fisierele pe server (Apache + PHP 8+ + MySQL)
2. Creeaza o baza de date MySQL goala
3. Deschide `https://domeniu.tau/setup.php`
4. Completeaza datele de conexiune si apasa "Instalează"
5. Adauga primul restaurant direct in baza de date sau creaza un endpoint de inregistrare

## Adaugare restaurant (manual, SQL)

```sql
INSERT INTO restaurante (nume, email, parola, telefon)
VALUES ('Numele Restaurantului', 'email@restaurant.ro', '$2y$10$...', '0700000000');
```

Parola se genereaza cu:
```php
echo password_hash('parola_dorita', PASSWORD_DEFAULT);
```

## Flux tableta

1. Mergi la `Admin > Mese` si adauga mesele restaurantului
2. Copiaza link-ul unic al mesei (ex: `https://domeniu.tau/masa/?token=abc123...`)
3. Deschide link-ul pe tableta si seteaz-o in modul kiosk (full screen)
4. Tableta ramane permanent pe acea pagina

## Comenzi live

Dashboard-ul de la `/admin/` se actualizeaza automat la fiecare 5 secunde.
Notificarea sonora apare la comenzi noi.

## Cerinte server

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache cu mod_rewrite activat

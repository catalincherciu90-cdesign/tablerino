#!/usr/bin/env node
/**
 * Migrate data from the original MySQL database into a D1-compatible SQL file.
 *
 * Reads every table from MySQL and emits INSERT statements matching the D1
 * schema (worker/schema.sql). Run the output against D1 with wrangler.
 *
 * Setup:
 *   npm i mysql2                      # one-off, not a project dependency
 *   export MYSQL_HOST=localhost MYSQL_USER=tablerino MYSQL_PASS=... MYSQL_DB=tablerino
 *   node scripts/migrate-from-mysql.mjs > data.sql
 *   npx wrangler d1 execute tablerino --remote --file=data.sql
 *
 * Note: this migrates DATA only. Uploaded images (uploads/*) must be copied to
 * R2 separately — see DEPLOY.md.
 */

import mysql from 'mysql2/promise';

// Columns to copy per table (must exist in the D1 schema). Order matters.
const TABLES = {
  restaurante: ['id', 'nume', 'email', 'parola', 'telefon', 'tema', 'logo', 'text_bun_venit', 'bg_imagine', 'facebook', 'instagram', 'tiktok', 'whatsapp', 'limba', 'parola_reset', 'activ', 'created_at'],
  mese: ['id', 'restaurant_id', 'nume', 'token', 'activa', 'created_at'],
  meniu_categorii: ['id', 'restaurant_id', 'nume', 'ordine'],
  meniu_produse: ['id', 'categorie_id', 'restaurant_id', 'nume', 'descriere', 'pret', 'poza', 'disponibil', 'ordine', 'ingrediente', 'alergeni', 'calorii', 'proteine', 'carbohidrati', 'grasimi'],
  comenzi: ['id', 'masa_id', 'restaurant_id', 'nr_ordine_zi', 'status', 'metoda_plata', 'observatii', 'created_at', 'updated_at'],
  comanda_produse: ['id', 'comanda_id', 'produs_id', 'nume_produs', 'pret_unitar', 'cantitate', 'total', 'status'],
  master_users: ['id', 'email', 'parola', 'created_at'],
  reclame: ['id', 'restaurant_id', 'titlu', 'text', 'imagine', 'durata', 'activa', 'ordine', 'created_at'],
  landing_settings: ['cheie', 'valoare'],
};

function sqlValue(v) {
  if (v === null || v === undefined) return 'NULL';
  if (typeof v === 'number') return String(v);
  if (v instanceof Date) return `'${v.toISOString().slice(0, 19).replace('T', ' ')}'`;
  if (Buffer.isBuffer(v)) v = v.toString('utf8');
  return `'${String(v).replace(/'/g, "''")}'`;
}

async function main() {
  const conn = await mysql.createConnection({
    host: process.env.MYSQL_HOST || 'localhost',
    user: process.env.MYSQL_USER || 'tablerino',
    password: process.env.MYSQL_PASS || '',
    database: process.env.MYSQL_DB || 'tablerino',
  });

  console.log('PRAGMA foreign_keys = OFF;');
  console.log('BEGIN TRANSACTION;');

  for (const [table, cols] of Object.entries(TABLES)) {
    let rows;
    try {
      [rows] = await conn.query(`SELECT ${cols.join(', ')} FROM ${table}`);
    } catch (e) {
      console.error(`-- skipping ${table}: ${e.message}`);
      continue;
    }
    if (!rows.length) continue;
    console.error(`-- ${table}: ${rows.length} rows`);
    for (const row of rows) {
      const vals = cols.map((col) => sqlValue(row[col])).join(', ');
      console.log(`INSERT OR REPLACE INTO ${table} (${cols.join(', ')}) VALUES (${vals});`);
    }
  }

  console.log('COMMIT;');
  console.log('PRAGMA foreign_keys = ON;');
  await conn.end();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});

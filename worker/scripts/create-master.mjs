#!/usr/bin/env node
// Print the SQL to create (or update) a master admin user with a bcrypt password.
// Usage: node scripts/create-master.mjs <email> <password>
// Then run the printed statement, e.g.:
//   node scripts/create-master.mjs admin@tablerino.ro 'secret' | tee /tmp/m.sql
//   npx wrangler d1 execute tablerino --remote --file=/tmp/m.sql

import bcrypt from 'bcryptjs';

const [, , email, password] = process.argv;
if (!email || !password) {
  console.error('Usage: node scripts/create-master.mjs <email> <password>');
  process.exit(1);
}

const hash = bcrypt.hashSync(password, 10);
const esc = (s) => s.replace(/'/g, "''");
// Seed the master user ONCE. Do NOT overwrite an existing password on every
// deploy — otherwise a manual password change gets reset on the next deploy.
console.log(
  `INSERT INTO master_users (email, parola) VALUES ('${esc(email)}', '${esc(hash)}')\n` +
    `  ON CONFLICT(email) DO NOTHING;`
);

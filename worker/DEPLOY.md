# Deploying Tablerino to Cloudflare

Complete, copy-pasteable guide to take the Worker port live on Cloudflare
(Workers + D1 + R2). Run everything from the `worker/` directory on a machine
with Node.js installed.

> The remote Claude environment cannot reach `api.cloudflare.com` (network
> policy), so deployment is done from your own machine with `wrangler login`.

## 1. Install & authenticate

```bash
cd worker
npm install
npx wrangler login          # opens a browser to authorize
```

## 2. Create the resources

```bash
npx wrangler d1 create tablerino
# Copy the printed database_id and paste it into wrangler.toml,
# replacing REPLACE_WITH_D1_DATABASE_ID.

npx wrangler r2 bucket create tablerino-uploads
```

## 3. Apply the database schema

```bash
npm run db:remote          # runs schema.sql against the D1 database
```

## 4. Secrets

```bash
# A long random string for signing session cookies:
npx wrangler secret put SESSION_SECRET
# Your Groq API key (for the AI menu extractor). Rotate the old one first!
npx wrangler secret put GROQ_KEY
```

## 5. Create the first master (platform admin) user

```bash
node scripts/create-master.mjs admin@tablerino.ro 'your-strong-password' > /tmp/master.sql
npx wrangler d1 execute tablerino --remote --file=/tmp/master.sql
```

## 6. Deploy

```bash
npm run deploy
```

Wrangler prints the live URL, e.g. `https://tablerino.<subdomain>.workers.dev`.

| Path | Page |
|---|---|
| `/` | Landing + registration |
| `/master/login.html` | Platform admin (log in with the master user from step 5) |
| `/admin/login.html` | Restaurant dashboard |
| `/masa/?token=…` | Tablet ordering (token from a table you create in the dashboard) |

---

## (Optional) Migrate existing data from MySQL

If you have data in the old MySQL database and want to carry it over:

```bash
npm i mysql2     # one-off, not a project dependency
export MYSQL_HOST=... MYSQL_USER=... MYSQL_PASS=... MYSQL_DB=tablerino
node scripts/migrate-from-mysql.mjs > data.sql
npx wrangler d1 execute tablerino --remote --file=data.sql
```

Then copy the uploaded images into R2 (run from the repo root, where the old
`uploads/` directory is):

```bash
bash worker/scripts/upload-images-to-r2.sh ./uploads tablerino-uploads
```

Passwords are bcrypt hashes in MySQL and are copied as-is — existing logins keep
working.

---

## Custom domain

To serve on `tablerino.ro` instead of the `workers.dev` subdomain, add a route
in `wrangler.toml` (the zone must be on your Cloudflare account):

```toml
routes = [{ pattern = "tablerino.ro/*", zone_name = "tablerino.ro" }]
```

then `npm run deploy` again.

## Local development

```bash
cp .dev.vars.example .dev.vars   # fill in GROQ_KEY + SESSION_SECRET
npm run db:local                 # apply schema to the local D1
npm run dev                      # http://localhost:8787
```

# Tablerino — Cloudflare Workers port

Port of the original PHP/MySQL Tablerino app to **Cloudflare Workers + D1 + R2** (TypeScript, [Hono](https://hono.dev)).

The original PHP app remains at the repository root during the transition.

## Architecture

| Concern | Original (PHP) | This port |
|---|---|---|
| Runtime | Apache + PHP 8 | Cloudflare Worker (Hono router) |
| Database | MySQL | D1 (SQLite) — see `schema.sql` |
| Uploads | `uploads/` filesystem | R2 bucket, served at `/uploads/*` |
| Sessions | `$_SESSION` | Signed HMAC cookies (`src/auth.ts`) |
| Table access | `?token=` (stateless) | unchanged (`?token=`) |
| AI menu | Groq, key in `config.php` | Groq, key as Worker secret |
| Frontend | server-rendered `.php` | static assets in `public/` + API calls |

## API surface

- `/api/table/*` — table/tablet (was `masa/api.php`), token-based
- `/api/admin/*` — restaurant dashboard (was `admin/api.php`), cookie auth
- `/api/master/*` — platform admin (was `master/api.php`), cookie auth
- `/api/register` — public registration (was `api/inregistrare.php`)
- `/api/upload/*` — image uploads to R2 (was `admin/upload_*.php`)
- `/api/ai/*` — Groq menu extraction (was `admin/ai_meniu.php`)

## Setup

```bash
cd worker
npm install

# Create resources (requires `wrangler login`)
npx wrangler d1 create tablerino          # paste database_id into wrangler.toml
npx wrangler r2 bucket create tablerino-uploads

# Apply the schema
npm run db:remote     # or db:local for local dev

# Secrets
npx wrangler secret put GROQ_KEY          # Groq API key
npx wrangler secret put SESSION_SECRET    # random long string for cookie signing

# Develop / deploy
npm run dev
npm run deploy
```

## Build status

- [x] Phase 1 — scaffold, D1 schema, auth/db/r2/themes infrastructure
- [ ] Phase 2 — table API (`/api/table`)
- [ ] Phase 3 — admin API (`/api/admin`)
- [ ] Phase 4 — master API + registration
- [ ] Phase 5 — R2 uploads + Groq AI
- [ ] Phase 6 — frontend (home, masa, admin, master)
- [ ] Phase 7 — deploy config + secrets

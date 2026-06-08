import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { all, one, run } from '../db';
import {
  requireMaster,
  loginMaster,
  logoutMaster,
  currentMaster,
  verifyPassword,
  hashPassword,
} from '../auth';
import { LANDING_DEFAULTS } from '../landingDefaults';
import { extToContentType, fileExt } from '../r2';

// Platform admin API — port of master/api.php + master/login.php.
const master = new Hono<HonoEnv>();

// ── Auth ──

master.post('/login', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const user = (body.user ?? '').trim();
  const parola = body.parola ?? '';
  const m = await one<{ email: string; parola: string }>(
    c.env.DB,
    'SELECT email, parola FROM master_users WHERE email = ?',
    user
  );
  if (!m || !(await verifyPassword(parola, m.parola))) {
    return c.json({ ok: false, msg: 'Credențiale incorecte.' }, 401);
  }
  await loginMaster(c, { master: true, email: m.email });
  return c.json({ ok: true });
});

master.post('/logout', (c) => {
  logoutMaster(c);
  return c.json({ ok: true });
});

master.get('/me', async (c) => {
  const sess = await currentMaster(c);
  if (!sess) return c.json({ ok: false }, 401);
  return c.json({ ok: true, email: sess.email });
});

// All routes below require a master session.
master.use('/*', requireMaster);

master.get('/statistici', async (c) => {
  const db = c.env.DB;
  const num = async (sql: string) => (await one<{ n: number }>(db, sql))?.n ?? 0;
  const stats = {
    total_restaurante: await num('SELECT COUNT(*) AS n FROM restaurante WHERE activ = 1'),
    restaurante_active: await num('SELECT COUNT(*) AS n FROM restaurante WHERE activ = 1'),
    comenzi_azi: await num("SELECT COUNT(*) AS n FROM comenzi WHERE DATE(created_at) = DATE('now') AND status = 'servita'"),
    vanzari_azi: await num("SELECT COALESCE(SUM(cp.total),0) AS n FROM comenzi c JOIN comanda_produse cp ON cp.comanda_id = c.id WHERE DATE(c.created_at) = DATE('now') AND c.status = 'servita'"),
    total_comenzi_all: await num("SELECT COUNT(*) AS n FROM comenzi WHERE status = 'servita'"),
    cereri_pending: await num('SELECT COUNT(*) AS n FROM restaurante WHERE activ = 0'),
  };
  return c.json({ ok: true, stats });
});

master.get('/restaurante', async (c) => {
  const restaurante = await all(
    c.env.DB,
    `SELECT r.*,
        (SELECT COUNT(*) FROM mese WHERE restaurant_id = r.id) AS nr_mese,
        (SELECT COUNT(*) FROM comenzi WHERE restaurant_id = r.id AND DATE(created_at) = DATE('now') AND status = 'servita') AS comenzi_azi,
        (SELECT COALESCE(SUM(cp.total),0) FROM comenzi c JOIN comanda_produse cp ON cp.comanda_id = c.id WHERE c.restaurant_id = r.id AND DATE(c.created_at) = DATE('now') AND c.status = 'servita') AS vanzari_azi
     FROM restaurante r
     WHERE r.activ = 1
     ORDER BY r.id DESC`
  );
  return c.json({ ok: true, restaurante });
});

master.get('/cereri_inregistrare', async (c) => {
  const cereri = await all(
    c.env.DB,
    'SELECT id, nume, email, telefon, created_at FROM restaurante WHERE activ = 0 ORDER BY created_at DESC'
  );
  return c.json({ ok: true, cereri });
});

master.post('/adauga_restaurant', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const nume = (body.nume ?? '').trim();
  const email = (body.email ?? '').trim();
  const tel = (body.telefon ?? '').trim();
  const parola = body.parola ?? '';
  if (!nume || !email || !parola) return c.json({ ok: false, msg: 'Date incomplete' }, 400);
  const hash = await hashPassword(parola);
  const res = await run(
    c.env.DB,
    "INSERT INTO restaurante (nume, email, telefon, parola, activ, tema, limba) VALUES (?,?,?,?,1,'italian','ro')",
    nume,
    email,
    tel || null,
    hash
  );
  return c.json({ ok: true, id: res.meta.last_row_id });
});

master.post('/editeaza_restaurant', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  const nume = (body.nume ?? '').trim();
  const email = (body.email ?? '').trim();
  const tel = (body.telefon ?? '').trim();
  const parola = body.parola ?? '';
  if (!id || !nume || !email) return c.json({ ok: false, msg: 'Date incomplete' }, 400);
  if (parola) {
    const hash = await hashPassword(parola);
    await run(c.env.DB, 'UPDATE restaurante SET nume=?, email=?, telefon=?, parola=? WHERE id=?', nume, email, tel || null, hash, id);
  } else {
    await run(c.env.DB, 'UPDATE restaurante SET nume=?, email=?, telefon=? WHERE id=?', nume, email, tel || null, id);
  }
  return c.json({ ok: true });
});

master.post('/toggle_activ', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  await run(c.env.DB, 'UPDATE restaurante SET activ = 1 - activ WHERE id = ?', id);
  return c.json({ ok: true });
});

// Registration requests (master/cereri.php) — approve or reject a pending account.
master.post('/accepta', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  await run(c.env.DB, 'UPDATE restaurante SET activ = 1 WHERE id = ?', id);
  return c.json({ ok: true });
});

master.post('/respinge', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  await run(c.env.DB, 'DELETE FROM restaurante WHERE id = ? AND activ = 0', id);
  return c.json({ ok: true });
});

// ── View a single restaurant's orders (master/comenzi.php) ──
master.get('/restaurant_comenzi', async (c) => {
  const id = Number(c.req.query('id') ?? 0);
  const data = c.req.query('data') || new Date().toISOString().slice(0, 10);
  const rest = await one<{ nume: string }>(c.env.DB, 'SELECT nume FROM restaurante WHERE id = ?', id);
  if (!rest) return c.json({ ok: false, msg: 'Restaurant inexistent' }, 404);

  const comenzi = await all<Record<string, unknown>>(
    c.env.DB,
    `SELECT c.*, m.nume AS masa_nume FROM comenzi c JOIN mese m ON m.id = c.masa_id
     WHERE c.restaurant_id = ? AND DATE(c.created_at) = ? ORDER BY c.created_at DESC`,
    id,
    data
  );
  for (const cmd of comenzi) {
    const produse = await all<{ total: number }>(c.env.DB, 'SELECT * FROM comanda_produse WHERE comanda_id = ?', cmd.id);
    cmd.produse = produse;
    cmd.total = produse.reduce((s, p) => s + Number(p.total), 0);
  }
  return c.json({ ok: true, nume: rest.nume, comenzi });
});

// ── View a single restaurant's menu (master/meniu.php) ──
master.get('/restaurant_meniu', async (c) => {
  const id = Number(c.req.query('id') ?? 0);
  const rest = await one<{ nume: string }>(c.env.DB, 'SELECT nume FROM restaurante WHERE id = ?', id);
  if (!rest) return c.json({ ok: false, msg: 'Restaurant inexistent' }, 404);

  const categorii = await all<Record<string, unknown>>(
    c.env.DB,
    'SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id',
    id
  );
  for (const cat of categorii) {
    cat.produse = await all(c.env.DB, 'SELECT * FROM meniu_produse WHERE categorie_id = ? ORDER BY ordine, id', cat.id);
  }
  return c.json({ ok: true, nume: rest.nume, categorii });
});

// ── Landing page editor (master/landing.php) ──
async function loadLanding(db: D1Database): Promise<Record<string, string>> {
  const settings = { ...LANDING_DEFAULTS };
  const rows = await all<{ cheie: string; valoare: string }>(db, 'SELECT cheie, valoare FROM landing_settings');
  for (const row of rows) settings[row.cheie] = row.valoare;
  return settings;
}

master.get('/landing', async (c) => {
  return c.json({ ok: true, settings: await loadLanding(c.env.DB) });
});

master.post('/landing', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const settings: Record<string, string> = body.settings ?? {};
  const stmts = Object.entries(settings)
    .filter(([k]) => k in LANDING_DEFAULTS)
    .map(([k, v]) => ({
      sql: 'INSERT INTO landing_settings (cheie, valoare) VALUES (?, ?) ON CONFLICT(cheie) DO UPDATE SET valoare = excluded.valoare',
      params: [k, String(v ?? '').trim()],
    }));
  if (stmts.length) {
    const { batch } = await import('../db');
    await batch(c.env.DB, stmts);
  }
  return c.json({ ok: true });
});

// Landing images map to fixed filenames the landing page references.
const LANDING_IMAGES: Record<string, string> = {
  hero_bg_img: 'landing-hero-bg.jpg',
  galerie_img1: 'landing-img1.jpg',
  galerie_img2: 'landing-img2.jpg',
  galerie_img3: 'landing-img3.jpg',
};

master.post('/landing_image', async (c) => {
  const form = await c.req.formData();
  const camp = String(form.get('camp') ?? '');
  const target = LANDING_IMAGES[camp];
  if (!target) return c.json({ ok: false, msg: 'Câmp invalid' }, 400);
  if (!c.env.UPLOADS) return c.json({ ok: false, msg: 'Încărcarea de imagini este indisponibilă (R2 neconfigurat).' }, 503);
  const file: unknown = form.get('imagine');
  if (!(file instanceof File) || file.size === 0) return c.json({ ok: false, msg: 'Fișier invalid' }, 400);
  const ext = fileExt(file.name);
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext)) return c.json({ ok: false, msg: 'Format neacceptat' }, 400);
  if (file.size > 5 * 1024 * 1024) return c.json({ ok: false, msg: 'Maxim 5MB' }, 400);
  // Store at the fixed path the landing page references (uploads/<target>),
  // keeping the real content-type regardless of the .jpg name.
  await c.env.UPLOADS.put(`uploads/${target}`, await file.arrayBuffer(), {
    httpMetadata: { contentType: extToContentType(ext) ?? 'image/jpeg' },
  });
  return c.json({ ok: true, cale: `uploads/${target}` });
});

export default master;

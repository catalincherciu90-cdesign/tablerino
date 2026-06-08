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

export default master;

import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { all, one, run } from '../db';
import {
  requireRestaurant,
  requireOwner,
  loginRestaurant,
  logoutRestaurant,
  currentRestaurant,
  verifyPassword,
  hashPassword,
  generateToken,
} from '../auth';
import { THEMES } from '../themes';
import { deleteUpload } from '../r2';

// Restaurant dashboard API — port of admin/api.php + admin/login.php.
const admin = new Hono<HonoEnv>();

const PAYMENTS = ['cash', 'card'];
const STATUSES = ['noua', 'in_pregatire', 'servita', 'plata_aleasa'];

// ── Auth ──

admin.post('/login', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const email = (body.email ?? '').trim();
  const parola = body.parola ?? '';

  // Owner account first.
  const rest = await one<{ id: number; nume: string; parola: string }>(
    c.env.DB,
    'SELECT id, nume, parola FROM restaurante WHERE email = ? AND activ = 1',
    email
  );
  if (rest && (await verifyPassword(parola, rest.parola))) {
    await loginRestaurant(c, { rid: rest.id, nume: rest.nume, role: 'owner' });
    return c.json({ ok: true, role: 'owner' });
  }

  // Otherwise a waiter account (only if its restaurant is active).
  const osp = await one<{ id: number; nume: string; parola: string; restaurant_id: number }>(
    c.env.DB,
    `SELECT o.id, o.nume, o.parola, o.restaurant_id
     FROM ospatari o JOIN restaurante r ON r.id = o.restaurant_id
     WHERE o.email = ? AND o.activ = 1 AND r.activ = 1`,
    email
  );
  if (osp && (await verifyPassword(parola, osp.parola))) {
    await loginRestaurant(c, { rid: osp.restaurant_id, nume: osp.nume, role: 'ospatar', uid: osp.id });
    return c.json({ ok: true, role: 'ospatar' });
  }

  return c.json({ ok: false, msg: 'Email sau parolă incorecte.' }, 401);
});

admin.post('/logout', (c) => {
  logoutRestaurant(c);
  return c.json({ ok: true });
});

// Editable restaurant profile, for the settings page (owner only).
admin.get('/profil', requireRestaurant, requireOwner, async (c) => {
  const profil = await one(
    c.env.DB,
    `SELECT id, nume, email, telefon, tema, logo, text_bun_venit, bg_imagine,
            facebook, instagram, tiktok, whatsapp, limba,
            CASE WHEN parola_reset IS NULL THEN 0 ELSE 1 END AS are_parola_reset
     FROM restaurante WHERE id = ?`,
    c.get('restaurant')!.rid
  );
  return c.json({ ok: true, profil });
});

admin.get('/me', async (c) => {
  const sess = await currentRestaurant(c);
  if (!sess) return c.json({ ok: false }, 401);
  const r = await one<{ tema: string; limba: string }>(
    c.env.DB,
    'SELECT tema, limba FROM restaurante WHERE id = ?',
    sess.rid
  );
  // Waiter's default table (for pre-filtering their orders view).
  let masa_default_id: number | null = null;
  let masa_default_nume: string | null = null;
  if (sess.role === 'ospatar' && sess.uid) {
    const o = await one<{ masa_default_id: number | null; masa_nume: string | null }>(
      c.env.DB,
      `SELECT o.masa_default_id, m.nume AS masa_nume
       FROM ospatari o LEFT JOIN mese m ON m.id = o.masa_default_id
       WHERE o.id = ?`,
      sess.uid
    );
    masa_default_id = o?.masa_default_id ?? null;
    masa_default_nume = o?.masa_nume ?? null;
  }
  return c.json({
    ok: true,
    id: sess.rid,
    nume: sess.nume,
    role: sess.role,
    ospatar_id: sess.role === 'ospatar' ? (sess.uid ?? null) : null,
    tema: r?.tema ?? 'italian',
    limba: r?.limba ?? 'ro',
    masa_default_id,
    masa_default_nume,
  });
});

// All routes below require an authenticated restaurant.
admin.use('/*', requireRestaurant);

const rid = (c: import('../types').AppContext) => c.get('restaurant')!.rid;

// ── Dashboard / orders ──

admin.post('/salveaza_tema', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const tema = body.tema ?? '';
  if (!(tema in THEMES)) return c.json({ ok: false, msg: 'Temă invalidă' }, 400);
  await run(c.env.DB, 'UPDATE restaurante SET tema = ? WHERE id = ?', tema, rid(c));
  return c.json({ ok: true });
});

admin.get('/sumar_zi', requireOwner, async (c) => {
  const s = await one<Record<string, number>>(
    c.env.DB,
    `SELECT COUNT(DISTINCT c.id) AS total_comenzi,
            COALESCE(SUM(cp.total), 0) AS total_vanzari,
            COUNT(DISTINCT CASE WHEN c.metoda_plata = 'cash' THEN c.id END) AS comenzi_cash,
            COUNT(DISTINCT CASE WHEN c.metoda_plata = 'card' THEN c.id END) AS comenzi_card
     FROM comenzi c
     LEFT JOIN comanda_produse cp ON cp.comanda_id = c.id
     WHERE c.restaurant_id = ? AND DATE(c.created_at) = DATE('now') AND c.status = 'servita'`,
    rid(c)
  );
  return c.json({
    ok: true,
    total_comenzi: s?.total_comenzi ?? 0,
    total_vanzari: s?.total_vanzari ?? 0,
    comenzi_cash: s?.comenzi_cash ?? 0,
    comenzi_card: s?.comenzi_card ?? 0,
  });
});

admin.get('/comenzi', async (c) => {
  const comenzi = await all<Record<string, unknown>>(
    c.env.DB,
    `SELECT c.*, m.nume AS masa_nume, o.nume AS preluat_de_nume FROM comenzi c
     JOIN mese m ON m.id = c.masa_id
     LEFT JOIN ospatari o ON o.id = c.preluat_de
     WHERE c.restaurant_id = ? AND c.status NOT IN ('servita')
     ORDER BY CASE c.status WHEN 'noua' THEN 0 WHEN 'in_pregatire' THEN 1 WHEN 'plata_aleasa' THEN 2 ELSE 3 END, c.created_at DESC`,
    rid(c)
  );
  for (const cmd of comenzi) {
    cmd.produse = await all(c.env.DB, 'SELECT * FROM comanda_produse WHERE comanda_id = ?', cmd.id);
  }
  return c.json({ ok: true, comenzi });
});

admin.get('/istoric', requireOwner, async (c) => {
  const masaId = Number(c.req.query('masa_id') ?? 0);
  const data = c.req.query('data') ?? '';
  const page = Math.max(1, Number(c.req.query('page') ?? 1));
  const limit = 20;
  const offset = (page - 1) * limit;

  let where = "c.restaurant_id = ? AND c.status = 'servita'";
  const params: unknown[] = [rid(c)];
  if (masaId) { where += ' AND c.masa_id = ?'; params.push(masaId); }
  if (data) { where += ' AND DATE(c.created_at) = ?'; params.push(data); }

  const countRow = await one<{ n: number }>(
    c.env.DB,
    `SELECT COUNT(*) AS n FROM comenzi c WHERE ${where}`,
    ...params
  );
  const total = countRow?.n ?? 0;

  const comenzi = await all<Record<string, unknown>>(
    c.env.DB,
    `SELECT c.*, m.nume AS masa_nume FROM comenzi c JOIN mese m ON m.id = c.masa_id
     WHERE ${where} ORDER BY c.created_at DESC LIMIT ? OFFSET ?`,
    ...params,
    limit,
    offset
  );
  for (const cmd of comenzi) {
    const produse = await all<{ total: number }>(
      c.env.DB,
      'SELECT * FROM comanda_produse WHERE comanda_id = ?',
      cmd.id
    );
    cmd.produse = produse;
    cmd.total = produse.reduce((s, p) => s + Number(p.total), 0);
  }
  return c.json({ ok: true, comenzi, total, pagini: Math.ceil(total / limit), page });
});

admin.post('/schimba_status', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.comanda_id ?? 0);
  const status = body.status ?? '';
  if (!STATUSES.includes(status)) return c.json({ ok: false }, 400);
  await run(c.env.DB, 'UPDATE comenzi SET status = ? WHERE id = ? AND restaurant_id = ?', status, id, rid(c));
  return c.json({ ok: true });
});

admin.post('/livreaza_produs', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const prodId = Number(body.comanda_produs_id ?? 0);
  await run(c.env.DB, "UPDATE comanda_produse SET status = 'livrat' WHERE id = ?", prodId);
  const row = await one<{ comanda_id: number }>(
    c.env.DB,
    'SELECT comanda_id FROM comanda_produse WHERE id = ?',
    prodId
  );
  if (row) {
    const left = await one<{ n: number }>(
      c.env.DB,
      "SELECT COUNT(*) AS n FROM comanda_produse WHERE comanda_id = ? AND status = 'nou'",
      row.comanda_id
    );
    if ((left?.n ?? 0) === 0) {
      await run(
        c.env.DB,
        "UPDATE comenzi SET status = 'in_pregatire' WHERE id = ? AND status = 'noua'",
        row.comanda_id
      );
    }
  }
  return c.json({ ok: true });
});

admin.post('/livreaza_toate', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const comandaId = Number(body.comanda_id ?? 0);
  await run(c.env.DB, "UPDATE comanda_produse SET status = 'livrat' WHERE comanda_id = ? AND status = 'nou'", comandaId);
  await run(
    c.env.DB,
    "UPDATE comenzi SET status = 'in_pregatire' WHERE id = ? AND restaurant_id = ? AND status = 'noua'",
    comandaId,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/inchide_comanda', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.comanda_id ?? 0);
  const metoda = body.metoda_plata ?? '';
  if (!PAYMENTS.includes(metoda)) return c.json({ ok: false, msg: 'Metodă de plată invalidă' }, 400);
  await run(
    c.env.DB,
    "UPDATE comenzi SET status = 'servita', metoda_plata = ? WHERE id = ? AND restaurant_id = ?",
    metoda,
    id,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/elibereaza_masa', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const masaId = Number(body.masa_id ?? 0);
  await run(
    c.env.DB,
    "UPDATE comenzi SET status = 'servita' WHERE masa_id = ? AND restaurant_id = ? AND status != 'servita'",
    masaId,
    rid(c)
  );
  return c.json({ ok: true });
});

// Waiter claims an unclaimed order (any table). Only succeeds if not already taken.
admin.post('/preia_comanda', async (c) => {
  const sess = c.get('restaurant')!;
  if (sess.role !== 'ospatar' || !sess.uid) {
    return c.json({ ok: false, msg: 'Doar un ospătar poate prelua comenzi.' }, 400);
  }
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.comanda_id ?? 0);
  const res = await run(
    c.env.DB,
    'UPDATE comenzi SET preluat_de = ? WHERE id = ? AND restaurant_id = ? AND preluat_de IS NULL',
    sess.uid,
    id,
    sess.rid
  );
  if (res.meta.changes === 0) {
    return c.json({ ok: false, msg: 'Comanda a fost deja preluată.' }, 409);
  }
  return c.json({ ok: true });
});

// Release a claim. A waiter can release only their own; the owner can release any.
admin.post('/renunta_comanda', async (c) => {
  const sess = c.get('restaurant')!;
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.comanda_id ?? 0);
  if (sess.role === 'ospatar') {
    await run(
      c.env.DB,
      'UPDATE comenzi SET preluat_de = NULL WHERE id = ? AND restaurant_id = ? AND preluat_de = ?',
      id,
      sess.rid,
      sess.uid ?? 0
    );
  } else {
    await run(c.env.DB, 'UPDATE comenzi SET preluat_de = NULL WHERE id = ? AND restaurant_id = ?', id, sess.rid);
  }
  return c.json({ ok: true });
});

// ── Menu: categories & products ──

admin.get('/categorii', async (c) => {
  const categorii = await all(c.env.DB, 'SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id', rid(c));
  return c.json({ ok: true, categorii });
});

admin.get('/produse', async (c) => {
  const catId = Number(c.req.query('categorie_id') ?? 0);
  const produse = await all(
    c.env.DB,
    'SELECT * FROM meniu_produse WHERE restaurant_id = ? AND categorie_id = ? ORDER BY ordine, id',
    rid(c),
    catId
  );
  return c.json({ ok: true, produse });
});

admin.post('/adauga_categorie', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const nume = (body.nume ?? '').trim();
  if (!nume) return c.json({ ok: false, msg: 'Numele este obligatoriu' }, 400);
  const res = await run(c.env.DB, 'INSERT INTO meniu_categorii (restaurant_id, nume) VALUES (?, ?)', rid(c), nume);
  return c.json({ ok: true, id: res.meta.last_row_id });
});

admin.post('/sterge_categorie', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const catId = Number(body.categorie_id ?? 0);
  await run(c.env.DB, 'DELETE FROM meniu_categorii WHERE id = ? AND restaurant_id = ?', catId, rid(c));
  return c.json({ ok: true });
});

admin.post('/adauga_produs', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const catId = Number(body.categorie_id ?? 0);
  const nume = (body.nume ?? '').trim();
  const pret = Number(body.pret ?? 0);
  const desc = (body.descriere ?? '').trim();
  if (!catId || !nume || pret <= 0) return c.json({ ok: false, msg: 'Date incomplete' }, 400);
  const res = await run(
    c.env.DB,
    'INSERT INTO meniu_produse (categorie_id, restaurant_id, nume, descriere, pret) VALUES (?,?,?,?,?)',
    catId,
    rid(c),
    nume,
    desc,
    pret
  );
  return c.json({ ok: true, id: res.meta.last_row_id });
});

admin.post('/editeaza_produs', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const pid = Number(body.produs_id ?? 0);
  const nume = (body.nume ?? '').trim();
  const desc = (body.descriere ?? '').trim();
  const pret = Number(body.pret ?? 0);
  const ingrediente = (body.ingrediente ?? '').trim();
  const alergeni = (body.alergeni ?? '').trim();
  const calorii = Number(body.calorii ?? 0);
  const proteine = Number(body.proteine ?? 0);
  const carbohidrati = Number(body.carbohidrati ?? 0);
  const grasimi = Number(body.grasimi ?? 0);
  if (!pid || !nume || pret <= 0) return c.json({ ok: false, msg: 'Date incomplete' }, 400);
  await run(
    c.env.DB,
    'UPDATE meniu_produse SET nume=?, descriere=?, pret=?, ingrediente=?, alergeni=?, calorii=?, proteine=?, carbohidrati=?, grasimi=? WHERE id=? AND restaurant_id=?',
    nume,
    desc || null,
    pret,
    ingrediente || null,
    alergeni || null,
    calorii || null,
    proteine || null,
    carbohidrati || null,
    grasimi || null,
    pid,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/sterge_produs', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const pid = Number(body.produs_id ?? 0);
  await run(c.env.DB, 'DELETE FROM meniu_produse WHERE id = ? AND restaurant_id = ?', pid, rid(c));
  return c.json({ ok: true });
});

admin.post('/toggle_disponibil', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const pid = Number(body.produs_id ?? 0);
  await run(c.env.DB, 'UPDATE meniu_produse SET disponibil = 1 - disponibil WHERE id = ? AND restaurant_id = ?', pid, rid(c));
  return c.json({ ok: true });
});

admin.post('/reordoneaza_produse', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const ordine: number[] = body.ordine ?? [];
  await Promise.all(
    ordine.map((produsId, index) =>
      run(c.env.DB, 'UPDATE meniu_produse SET ordine = ? WHERE id = ? AND restaurant_id = ?', index, Number(produsId), rid(c))
    )
  );
  return c.json({ ok: true });
});

admin.post('/reordoneaza_categorii', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const ordine: number[] = body.ordine ?? [];
  await Promise.all(
    ordine.map((catId, index) =>
      run(c.env.DB, 'UPDATE meniu_categorii SET ordine = ? WHERE id = ? AND restaurant_id = ?', index, Number(catId), rid(c))
    )
  );
  return c.json({ ok: true });
});

// ── Tables ──

admin.get('/mese', async (c) => {
  const mese = await all(c.env.DB, 'SELECT * FROM mese WHERE restaurant_id = ? ORDER BY id', rid(c));
  return c.json({ ok: true, mese });
});

admin.post('/adauga_masa', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const nume = (body.nume ?? '').trim();
  if (!nume) return c.json({ ok: false }, 400);
  const token = generateToken();
  const res = await run(c.env.DB, 'INSERT INTO mese (restaurant_id, nume, token) VALUES (?, ?, ?)', rid(c), nume, token);
  return c.json({ ok: true, id: res.meta.last_row_id, token });
});

admin.post('/sterge_masa', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const mid = Number(body.masa_id ?? 0);
  await run(c.env.DB, 'DELETE FROM mese WHERE id = ? AND restaurant_id = ?', mid, rid(c));
  return c.json({ ok: true });
});

// ── Visual profile / social ──

admin.post('/salveaza_profil_vizual', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const text = (body.text_bun_venit ?? '').trim();
  await run(c.env.DB, 'UPDATE restaurante SET text_bun_venit = ? WHERE id = ?', text || null, rid(c));
  return c.json({ ok: true });
});

admin.post('/salveaza_social', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const facebook = (body.facebook ?? '').trim();
  const instagram = (body.instagram ?? '').trim();
  const tiktok = (body.tiktok ?? '').trim();
  const whatsapp = (body.whatsapp ?? '').trim();
  await run(
    c.env.DB,
    'UPDATE restaurante SET facebook = ?, instagram = ?, tiktok = ?, whatsapp = ? WHERE id = ?',
    facebook || null,
    instagram || null,
    tiktok || null,
    whatsapp || null,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/sterge_imagine', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const tip = body.tip ?? '';
  if (tip !== 'logo' && tip !== 'bg_imagine') return c.json({ ok: false }, 400);
  const r = await one<Record<string, string | null>>(c.env.DB, `SELECT ${tip} AS val FROM restaurante WHERE id = ?`, rid(c));
  if (r?.val && c.env.UPLOADS) await deleteUpload(c.env.UPLOADS, r.val);
  await run(c.env.DB, `UPDATE restaurante SET ${tip} = NULL WHERE id = ?`, rid(c));
  return c.json({ ok: true });
});

// ── Ads (reclame) ──

admin.get('/reclame', requireOwner, async (c) => {
  const reclame = await all(c.env.DB, 'SELECT * FROM reclame WHERE restaurant_id = ? ORDER BY ordine, id', rid(c));
  return c.json({ ok: true, reclame });
});

admin.post('/adauga_reclama', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const titlu = (body.titlu ?? '').trim();
  const text = (body.text ?? '').trim();
  const durata = Number(body.durata ?? 5);
  const res = await run(
    c.env.DB,
    'INSERT INTO reclame (restaurant_id, titlu, text, durata) VALUES (?, ?, ?, ?)',
    rid(c),
    titlu || null,
    text || null,
    durata
  );
  return c.json({ ok: true, id: res.meta.last_row_id });
});

admin.post('/editeaza_reclama', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  const titlu = (body.titlu ?? '').trim();
  const text = (body.text ?? '').trim();
  const durata = Number(body.durata ?? 5);
  await run(
    c.env.DB,
    'UPDATE reclame SET titlu = ?, text = ?, durata = ? WHERE id = ? AND restaurant_id = ?',
    titlu || null,
    text || null,
    durata,
    id,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/toggle_reclama', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.reclama_id ?? body.id ?? 0);
  await run(c.env.DB, 'UPDATE reclame SET activa = 1 - activa WHERE id = ? AND restaurant_id = ?', id, rid(c));
  return c.json({ ok: true });
});

admin.post('/sterge_reclama', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.reclama_id ?? body.id ?? 0);
  const r = await one<{ imagine: string | null }>(c.env.DB, 'SELECT imagine FROM reclame WHERE id = ? AND restaurant_id = ?', id, rid(c));
  if (r?.imagine && c.env.UPLOADS) await deleteUpload(c.env.UPLOADS, r.imagine);
  await run(c.env.DB, 'DELETE FROM reclame WHERE id = ? AND restaurant_id = ?', id, rid(c));
  return c.json({ ok: true });
});

// ── Audit (password-protected manual closures) ──

async function checkAuditPassword(c: import('../types').AppContext, parola: string): Promise<boolean> {
  const pr = await one<{ parola_reset: string | null }>(
    c.env.DB,
    'SELECT parola_reset FROM restaurante WHERE id = ?',
    rid(c)
  );
  return !!pr?.parola_reset && verifyPassword(parola, pr.parola_reset);
}

admin.post('/audit_seteaza_plata', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const comandaId = Number(body.comanda_id ?? 0);
  const metoda = body.metoda_plata ?? '';
  const parola = (body.parola ?? '').trim();
  if (!PAYMENTS.includes(metoda)) return c.json({ ok: false, msg: 'Metodă invalidă' }, 400);
  if (!(await checkAuditPassword(c, parola))) return c.json({ ok: false, msg: 'Parolă greșită' }, 403);
  await run(
    c.env.DB,
    "UPDATE comenzi SET metoda_plata = ?, status = 'servita' WHERE id = ? AND restaurant_id = ?",
    metoda,
    comandaId,
    rid(c)
  );
  return c.json({ ok: true });
});

admin.post('/audit_inchide', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const comandaId = Number(body.comanda_id ?? 0);
  const parola = (body.parola ?? '').trim();
  if (!(await checkAuditPassword(c, parola))) return c.json({ ok: false, msg: 'Parolă greșită' }, 403);
  await run(c.env.DB, "UPDATE comenzi SET status = 'servita' WHERE id = ? AND restaurant_id = ?", comandaId, rid(c));
  return c.json({ ok: true });
});

admin.post('/audit_inchide_tot', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const parola = (body.parola ?? '').trim();
  if (!(await checkAuditPassword(c, parola))) return c.json({ ok: false, msg: 'Parolă greșită' }, 403);
  await run(c.env.DB, "UPDATE comenzi SET status = 'servita' WHERE restaurant_id = ? AND status NOT IN ('servita')", rid(c));
  return c.json({ ok: true });
});

admin.post('/salveaza_parola_reset', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const parola = (body.parola ?? '').trim();
  if (parola.length < 4) return c.json({ ok: false, msg: 'Parola este prea scurtă' }, 400);
  const hash = await hashPassword(parola);
  await run(c.env.DB, 'UPDATE restaurante SET parola_reset = ? WHERE id = ?', hash, rid(c));
  return c.json({ ok: true });
});

// ── Daily report (admin/raport.php) ──
admin.get('/raport', requireOwner, async (c) => {
  const data = c.req.query('data') || new Date().toISOString().slice(0, 10);
  const db = c.env.DB;
  const r = rid(c);

  // Per-order totals as a reusable subquery join.
  const ordTotals = `JOIN (SELECT comanda_id, SUM(total) AS total_comanda FROM comanda_produse GROUP BY comanda_id) cp ON cp.comanda_id = c.id`;
  const filter = `c.restaurant_id = ? AND DATE(c.created_at) = ? AND c.status = 'servita'`;

  const sumar = await one<Record<string, number>>(
    db,
    `SELECT COUNT(*) AS total_comenzi,
            COALESCE(SUM(cp.total_comanda),0) AS total_vanzari,
            COALESCE(SUM(CASE WHEN c.metoda_plata='cash' THEN cp.total_comanda ELSE 0 END),0) AS total_cash,
            COALESCE(SUM(CASE WHEN c.metoda_plata='card' THEN cp.total_comanda ELSE 0 END),0) AS total_card,
            COUNT(CASE WHEN c.metoda_plata='cash' THEN 1 END) AS nr_cash,
            COUNT(CASE WHEN c.metoda_plata='card' THEN 1 END) AS nr_card,
            AVG(cp.total_comanda) AS medie_comanda
     FROM comenzi c ${ordTotals} WHERE ${filter}`,
    r, data
  );

  const top_produse = await all(
    db,
    `SELECT cp.nume_produs, SUM(cp.cantitate) AS cantitate_totala, SUM(cp.total) AS total_produs,
            COUNT(DISTINCT cp.comanda_id) AS nr_comenzi, cp.pret_unitar
     FROM comanda_produse cp JOIN comenzi c ON c.id = cp.comanda_id
     WHERE ${filter}
     GROUP BY cp.nume_produs, cp.pret_unitar ORDER BY cantitate_totala DESC LIMIT 20`,
    r, data
  );

  const per_masa = await all(
    db,
    `SELECT m.nume AS masa_nume, COUNT(c.id) AS nr_comenzi, SUM(cp.total_comanda) AS total_masa,
            c.metoda_plata, MIN(c.created_at) AS prima_comanda, MAX(c.created_at) AS ultima_comanda
     FROM comenzi c JOIN mese m ON m.id = c.masa_id ${ordTotals}
     WHERE ${filter}
     GROUP BY m.id, m.nume, c.metoda_plata ORDER BY total_masa DESC`,
    r, data
  );

  const per_ora = await all(
    db,
    `SELECT CAST(strftime('%H', c.created_at) AS INTEGER) AS ora, COUNT(*) AS nr_comenzi,
            SUM(cp.total_comanda) AS total_ora
     FROM comenzi c ${ordTotals}
     WHERE ${filter}
     GROUP BY ora ORDER BY ora`,
    r, data
  );

  const maseRow = await one<{ n: number }>(
    db,
    "SELECT COUNT(DISTINCT masa_id) AS n FROM comenzi WHERE restaurant_id = ? AND DATE(created_at) = ? AND status = 'servita'",
    r, data
  );

  const rest = await one<{ nume: string }>(db, 'SELECT nume FROM restaurante WHERE id = ?', r);

  return c.json({
    ok: true,
    data,
    nume: rest?.nume ?? '',
    sumar,
    top_produse,
    per_masa,
    per_ora,
    mese_active: maseRow?.n ?? 0,
  });
});

// ── Waiter (ospatar) accounts — owner only ──

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

admin.get('/ospatari', requireOwner, async (c) => {
  const ospatari = await all(
    c.env.DB,
    `SELECT o.id, o.nume, o.email, o.activ, o.created_at, o.masa_default_id,
            m.nume AS masa_default_nume
     FROM ospatari o LEFT JOIN mese m ON m.id = o.masa_default_id
     WHERE o.restaurant_id = ? ORDER BY o.id`,
    rid(c)
  );
  return c.json({ ok: true, ospatari });
});

// Validate a table id belongs to this restaurant; returns the id or null.
async function validMasaId(c: import('../types').AppContext, masaId: unknown): Promise<number | null> {
  const id = Number(masaId ?? 0);
  if (!id) return null;
  const m = await one<{ id: number }>(c.env.DB, 'SELECT id FROM mese WHERE id = ? AND restaurant_id = ?', id, rid(c));
  return m ? id : null;
}

// Set/clear a waiter's default table.
admin.post('/set_masa_ospatar', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  const masaId = await validMasaId(c, body.masa_default_id);
  await run(c.env.DB, 'UPDATE ospatari SET masa_default_id = ? WHERE id = ? AND restaurant_id = ?', masaId, id, rid(c));
  return c.json({ ok: true, masa_default_id: masaId });
});

admin.post('/adauga_ospatar', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const nume = (body.nume ?? '').trim();
  const email = (body.email ?? '').trim().toLowerCase();
  const parola = body.parola ?? '';
  if (!nume || !email || !parola) return c.json({ ok: false, msg: 'Date incomplete' }, 400);
  if (!EMAIL_RE.test(email)) return c.json({ ok: false, msg: 'Email invalid' }, 400);
  if (parola.length < 6) return c.json({ ok: false, msg: 'Parola trebuie să aibă minim 6 caractere' }, 400);

  // Email must not collide with a restaurant or another waiter (login is by email).
  const dupR = await one(c.env.DB, 'SELECT id FROM restaurante WHERE email = ?', email);
  const dupO = await one(c.env.DB, 'SELECT id FROM ospatari WHERE email = ?', email);
  if (dupR || dupO) return c.json({ ok: false, msg: 'Există deja un cont cu acest email' }, 400);

  const masaId = await validMasaId(c, body.masa_default_id);
  const hash = await hashPassword(parola);
  const res = await run(
    c.env.DB,
    'INSERT INTO ospatari (restaurant_id, nume, email, parola, masa_default_id) VALUES (?, ?, ?, ?, ?)',
    rid(c),
    nume,
    email,
    hash,
    masaId
  );
  return c.json({ ok: true, id: res.meta.last_row_id });
});

admin.post('/toggle_ospatar', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  await run(c.env.DB, 'UPDATE ospatari SET activ = 1 - activ WHERE id = ? AND restaurant_id = ?', id, rid(c));
  return c.json({ ok: true });
});

admin.post('/reset_parola_ospatar', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  const parola = body.parola ?? '';
  if (parola.length < 6) return c.json({ ok: false, msg: 'Parola trebuie să aibă minim 6 caractere' }, 400);
  const hash = await hashPassword(parola);
  await run(c.env.DB, 'UPDATE ospatari SET parola = ? WHERE id = ? AND restaurant_id = ?', hash, id, rid(c));
  return c.json({ ok: true });
});

admin.post('/sterge_ospatar', requireOwner, async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const id = Number(body.id ?? 0);
  await run(c.env.DB, 'DELETE FROM ospatari WHERE id = ? AND restaurant_id = ?', id, rid(c));
  return c.json({ ok: true });
});

export default admin;

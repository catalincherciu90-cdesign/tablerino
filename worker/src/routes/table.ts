import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { all, one, run } from '../db';
import { getMasa } from '../auth';
import { getTheme } from '../themes';

// Table / tablet API — port of masa/api.php. Token-based, no session.
const table = new Hono<HonoEnv>();

interface Restaurant {
  tema: string; logo: string | null; text_bun_venit: string | null;
  bg_imagine: string | null; facebook: string | null; instagram: string | null;
  tiktok: string | null; whatsapp: string | null; limba: string | null;
}

// GET /api/table/meniu?token=  — full menu + branding for a table
table.get('/meniu', async (c) => {
  const token = c.req.query('token') ?? '';
  const masa = await getMasa(c.env.DB, token);
  if (!masa) return c.json({ ok: false, msg: 'Masă invalidă' }, 404);
  const rid = masa.restaurant_id;

  const restaurant = await one<Restaurant>(
    c.env.DB,
    'SELECT tema, logo, text_bun_venit, bg_imagine, facebook, instagram, tiktok, whatsapp, limba FROM restaurante WHERE id = ?',
    rid
  );
  if (!restaurant) return c.json({ ok: false, msg: 'Restaurant inexistent' }, 404);

  const temaSlug = restaurant.tema ?? 'italian';
  const tema = getTheme(temaSlug);

  const categorii = await all<Record<string, unknown>>(
    c.env.DB,
    'SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id',
    rid
  );
  for (const cat of categorii) {
    cat.produse = await all(
      c.env.DB,
      'SELECT * FROM meniu_produse WHERE categorie_id = ? AND restaurant_id = ? ORDER BY ordine, id',
      cat.id,
      rid
    );
  }

  const social: Record<string, string> = {};
  if (restaurant.facebook) social.facebook = restaurant.facebook;
  if (restaurant.instagram) social.instagram = restaurant.instagram;
  if (restaurant.tiktok) social.tiktok = restaurant.tiktok;
  if (restaurant.whatsapp) social.whatsapp = restaurant.whatsapp;

  return c.json({
    ok: true,
    masa,
    categorii,
    tema,
    tema_slug: temaSlug,
    logo: restaurant.logo,
    text_bun_venit: restaurant.text_bun_venit,
    bg_imagine: restaurant.bg_imagine,
    social,
    limba: restaurant.limba ?? 'ro',
  });
});

// POST /api/table/trimite_comanda  — place/append an order
table.post('/trimite_comanda', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const token: string = body.token ?? '';
  const produse: { produs_id?: number; cantitate?: number }[] = body.produse ?? [];
  const observatii: string = (body.observatii ?? '').trim();

  if (!token || produse.length === 0) return c.json({ ok: false, msg: 'Date incomplete' }, 400);

  const masa = await getMasa(c.env.DB, token);
  if (!masa) return c.json({ ok: false, msg: 'Masă invalidă' }, 404);
  const rid = masa.restaurant_id;

  // Reuse an active order on the table (not yet served / payment chosen).
  const existing = await one<{ id: number }>(
    c.env.DB,
    "SELECT id FROM comenzi WHERE masa_id = ? AND status NOT IN ('servita','plata_aleasa') ORDER BY id DESC LIMIT 1",
    masa.id
  );

  let comandaId: number;
  if (existing) {
    comandaId = existing.id;
  } else {
    const nrRow = await one<{ nr: number }>(
      c.env.DB,
      "SELECT COALESCE(MAX(nr_ordine_zi), 0) + 1 AS nr FROM comenzi WHERE restaurant_id = ? AND DATE(created_at) = DATE('now')",
      rid
    );
    const nrOrdine = nrRow?.nr ?? 1;
    const ins = await run(
      c.env.DB,
      'INSERT INTO comenzi (masa_id, restaurant_id, observatii, nr_ordine_zi) VALUES (?, ?, ?, ?)',
      masa.id,
      rid,
      observatii,
      nrOrdine
    );
    comandaId = ins.meta.last_row_id;
  }

  for (const p of produse) {
    const pid = Number(p.produs_id ?? 0);
    const qty = Number(p.cantitate ?? 1);
    const produs = await one<{ nume: string; pret: number }>(
      c.env.DB,
      'SELECT nume, pret FROM meniu_produse WHERE id = ? AND restaurant_id = ? AND disponibil = 1',
      pid,
      rid
    );
    if (!produs) continue;
    const total = produs.pret * qty;
    await run(
      c.env.DB,
      "INSERT INTO comanda_produse (comanda_id, produs_id, nume_produs, pret_unitar, cantitate, total, status) VALUES (?,?,?,?,?,?,'nou')",
      comandaId,
      pid,
      produs.nume,
      produs.pret,
      qty,
      total
    );
  }

  // Re-activate an appended order (updated_at handled by trigger).
  if (existing) {
    await run(c.env.DB, "UPDATE comenzi SET status = 'noua' WHERE id = ?", comandaId);
  }

  return c.json({ ok: true, comanda_id: comandaId });
});

// GET /api/table/sumar_comanda?token=  — current order summary for the table
table.get('/sumar_comanda', async (c) => {
  const token = c.req.query('token') ?? '';
  const masa = await getMasa(c.env.DB, token);
  if (!masa) return c.json({ ok: false, msg: 'Masă invalidă' }, 404);

  const comanda = await one<{ id: number; status: string; metoda_plata: string | null }>(
    c.env.DB,
    "SELECT id, status, metoda_plata FROM comenzi WHERE masa_id = ? AND status != 'servita' ORDER BY id DESC LIMIT 1",
    masa.id
  );
  if (!comanda) {
    return c.json({ ok: true, produse: [], total: 0, status: null });
  }
  const produse = await all<{ total: number }>(
    c.env.DB,
    'SELECT * FROM comanda_produse WHERE comanda_id = ?',
    comanda.id
  );
  const total = produse.reduce((s, p) => s + Number(p.total), 0);
  return c.json({
    ok: true,
    produse,
    total,
    status: comanda.status,
    metoda_plata: comanda.metoda_plata,
  });
});

// POST /api/table/alege_plata  — choose payment method
table.post('/alege_plata', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const token: string = body.token ?? '';
  const metoda: string = body.metoda ?? '';
  if (metoda !== 'cash' && metoda !== 'card') return c.json({ ok: false, msg: 'Metodă invalidă' }, 400);

  const masa = await getMasa(c.env.DB, token);
  if (!masa) return c.json({ ok: false, msg: 'Masă invalidă' }, 404);

  await run(
    c.env.DB,
    "UPDATE comenzi SET metoda_plata = ?, status = 'plata_aleasa' WHERE masa_id = ? AND status NOT IN ('servita','plata_aleasa')",
    metoda,
    masa.id
  );
  return c.json({ ok: true });
});

// GET /api/table/reclame?token=  — active ads for the carousel
table.get('/reclame', async (c) => {
  const token = c.req.query('token') ?? '';
  const masa = await getMasa(c.env.DB, token);
  if (!masa) return c.json({ ok: false, msg: 'Masă invalidă' }, 404);
  const reclame = await all(
    c.env.DB,
    'SELECT * FROM reclame WHERE restaurant_id = ? AND activa = 1 ORDER BY ordine, id',
    masa.restaurant_id
  );
  return c.json({ ok: true, reclame });
});

export default table;

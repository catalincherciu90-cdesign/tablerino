import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { one, run } from '../db';
import { getTheme } from '../themes';

// Public reservation API (no auth) — a customer reserves a table at a restaurant
// via its public reservation link (?token=<rezervare_token>).
const rezervare = new Hono<HonoEnv>();

const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
const TIME_RE = /^\d{2}:\d{2}$/;

interface Rest {
  id: number; nume: string; logo: string | null; tema: string;
  bg_imagine: string | null; text_bun_venit: string | null; limba: string | null;
}

async function restByToken(db: D1Database, token: string): Promise<Rest | null> {
  if (!token) return null;
  return one<Rest>(
    db,
    `SELECT id, nume, logo, tema, bg_imagine, text_bun_venit, limba
     FROM restaurante WHERE rezervare_token = ? AND activ = 1`,
    token
  );
}

// GET /api/rezervare/info?token=  — branding for the public reservation form
rezervare.get('/info', async (c) => {
  const r = await restByToken(c.env.DB, c.req.query('token') ?? '');
  if (!r) return c.json({ ok: false, msg: 'Link invalid' }, 404);
  // Largest table capacity (so the form can cap the party size); null = no limit set.
  const capRow = await one<{ max_persoane: number | null }>(
    c.env.DB,
    'SELECT MAX(capacitate) AS max_persoane FROM mese WHERE restaurant_id = ? AND activa = 1',
    r.id
  );
  return c.json({
    ok: true,
    max_persoane: capRow?.max_persoane ?? null,
    restaurant: {
      nume: r.nume,
      logo: r.logo,
      tema: r.tema,
      tema_cfg: getTheme(r.tema),
      bg_imagine: r.bg_imagine,
      text_bun_venit: r.text_bun_venit,
      limba: r.limba ?? 'ro',
    },
  });
});

// POST /api/rezervare  — create a reservation
rezervare.post('/', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const r = await restByToken(c.env.DB, body.token ?? '');
  if (!r) return c.json({ ok: false, msg: 'Link invalid' }, 404);

  const nume = (body.nume ?? '').trim();
  const telefon = (body.telefon ?? '').trim();
  const email = (body.email ?? '').trim();
  const data = (body.data ?? '').trim();
  const ora = (body.ora ?? '').trim();
  const nrPersoane = Number(body.nr_persoane ?? 0);
  const observatii = (body.observatii ?? '').trim();

  if (!nume || !telefon || !data || !ora) return c.json({ ok: false, msg: 'Completează toate câmpurile obligatorii.' }, 400);
  if (!DATE_RE.test(data) || !TIME_RE.test(ora)) return c.json({ ok: false, msg: 'Dată sau oră invalidă.' }, 400);
  if (data < new Date().toISOString().slice(0, 10)) return c.json({ ok: false, msg: 'Alege o dată din viitor.' }, 400);
  if (!(nrPersoane >= 1 && nrPersoane <= 50)) return c.json({ ok: false, msg: 'Număr de persoane invalid.' }, 400);

  const res = await run(
    c.env.DB,
    `INSERT INTO rezervari (restaurant_id, nume_client, telefon, email, data, ora, nr_persoane, observatii)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    r.id,
    nume,
    telefon,
    email || null,
    data,
    ora,
    nrPersoane,
    observatii || null
  );
  return c.json({ ok: true, id: res.meta.last_row_id });
});

export default rezervare;

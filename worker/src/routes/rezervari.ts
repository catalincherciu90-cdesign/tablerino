import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { all, one, run } from '../db';
import { getTheme } from '../themes';

// Public reservation API (no auth) — a customer reserves a table at a restaurant
// via its public reservation link (?token=<rezervare_token>).
const rezervare = new Hono<HonoEnv>();

const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
const TIME_RE = /^\d{2}:\d{2}$/;

// Reservation slots & sizing (fixed for V1).
const SLOT_START = 12 * 60; // 12:00
const SLOT_END = 23 * 60; // 23:00 (last slot)
const SLOT_STEP = 30; // minutes between slots
const WINDOW = 120; // a reservation occupies the table for ~2h

interface Rest {
  id: number; nume: string; logo: string | null; tema: string;
  bg_imagine: string | null; text_bun_venit: string | null; limba: string | null;
  rez_capacitate: number | null;
}

async function restByToken(db: D1Database, token: string): Promise<Rest | null> {
  if (!token) return null;
  return one<Rest>(
    db,
    `SELECT id, nume, logo, tema, bg_imagine, text_bun_venit, limba, rez_capacitate
     FROM restaurante WHERE rezervare_token = ? AND activ = 1`,
    token
  );
}

function minutesOf(hhmm: string): number {
  const [h, m] = hhmm.split(':').map(Number);
  return h * 60 + m;
}
function slotLabel(min: number): string {
  return `${String(Math.floor(min / 60)).padStart(2, '0')}:${String(min % 60).padStart(2, '0')}`;
}
function genSlots(): string[] {
  const out: string[] = [];
  for (let m = SLOT_START; m <= SLOT_END; m += SLOT_STEP) out.push(slotLabel(m));
  return out;
}

/** Total seats available for reservations: explicit override, else sum of active
 *  table capacities, else null (= no limit configured → always available). */
async function totalCapacitate(db: D1Database, r: Rest): Promise<number | null> {
  if (r.rez_capacitate != null) return r.rez_capacitate;
  const row = await one<{ s: number; n: number }>(
    db,
    'SELECT COALESCE(SUM(capacitate),0) AS s, COUNT(capacitate) AS n FROM mese WHERE restaurant_id = ? AND activa = 1',
    r.id
  );
  return row && row.n > 0 ? row.s : null;
}

/** Seats occupied around a target time (overlapping the ~2h window). */
function occupiedAt(rezRows: { ora: string; nr_persoane: number }[], targetMin: number): number {
  let sum = 0;
  for (const rz of rezRows) {
    if (Math.abs(minutesOf(rz.ora) - targetMin) < WINDOW) sum += Number(rz.nr_persoane);
  }
  return sum;
}

// GET /api/rezervare/restaurante  — public directory of restaurants that opted in
// to the Tablerino public reservations page.
rezervare.get('/restaurante', async (c) => {
  const rows = await all<{ nume: string; logo: string | null; tema: string; rezervare_token: string }>(
    c.env.DB,
    `SELECT nume, logo, tema, rezervare_token
     FROM restaurante
     WHERE rezervari_publice = 1 AND activ = 1 AND rezervare_token IS NOT NULL
     ORDER BY nume COLLATE NOCASE`
  );
  const restaurante = rows.map((r) => ({
    nume: r.nume,
    logo: r.logo,
    tema: r.tema,
    tema_cfg: getTheme(r.tema),
    token: r.rezervare_token,
  }));
  return c.json({ ok: true, restaurante });
});

// GET /api/rezervare/info?token=  — branding for the public reservation form
rezervare.get('/info', async (c) => {
  const r = await restByToken(c.env.DB, c.req.query('token') ?? '');
  if (!r) return c.json({ ok: false, msg: 'Link invalid' }, 404);
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

// GET /api/rezervare/disponibilitate?token=&data=&nr=  — free slots for a date/party
rezervare.get('/disponibilitate', async (c) => {
  const r = await restByToken(c.env.DB, c.req.query('token') ?? '');
  if (!r) return c.json({ ok: false, msg: 'Link invalid' }, 404);
  const data = (c.req.query('data') ?? '').trim();
  const nr = Math.max(1, Number(c.req.query('nr') ?? 1));
  if (!DATE_RE.test(data)) return c.json({ ok: false, msg: 'Dată invalidă' }, 400);

  const total = await totalCapacitate(c.env.DB, r);
  const rezRows = await all<{ ora: string; nr_persoane: number }>(
    c.env.DB,
    "SELECT ora, nr_persoane FROM rezervari WHERE restaurant_id = ? AND data = ? AND status != 'anulata'",
    r.id,
    data
  );
  const sloturi = genSlots().map((ora) => {
    const occ = occupiedAt(rezRows, minutesOf(ora));
    const ramase = total == null ? null : Math.max(0, total - occ);
    const liber = total == null ? true : total - occ >= nr;
    return { ora, liber, ramase };
  });
  return c.json({ ok: true, total, nr, sloturi });
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

  // Slot availability: don't let a slot be overbooked beyond total capacity.
  const total = await totalCapacitate(c.env.DB, r);
  if (total != null) {
    const rezRows = await all<{ ora: string; nr_persoane: number }>(
      c.env.DB,
      "SELECT ora, nr_persoane FROM rezervari WHERE restaurant_id = ? AND data = ? AND status != 'anulata'",
      r.id,
      data
    );
    if (total - occupiedAt(rezRows, minutesOf(ora)) < nrPersoane) {
      return c.json({ ok: false, msg: 'Intervalul ales nu mai are locuri pentru acest număr de persoane. Alege altă oră.' }, 409);
    }
  }

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

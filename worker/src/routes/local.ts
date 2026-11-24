import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { all, one } from '../db';
import { getTheme } from '../themes';

// Public restaurant profile (no auth) — shows branding, menu, reviews and a
// "reserve" CTA. Addressed by the restaurant's public rezervare_token.
const local = new Hono<HonoEnv>();

interface Rest {
  id: number; nume: string; logo: string | null; tema: string;
  bg_imagine: string | null; text_bun_venit: string | null;
  facebook: string | null; instagram: string | null; tiktok: string | null;
  whatsapp: string | null; limba: string | null; rezervare_token: string | null;
}

// GET /api/local?token=  — full public profile
local.get('/', async (c) => {
  const token = c.req.query('token') ?? '';
  if (!token) return c.json({ ok: false, msg: 'Link invalid' }, 404);
  const r = await one<Rest>(
    c.env.DB,
    `SELECT id, nume, logo, tema, bg_imagine, text_bun_venit, facebook, instagram,
            tiktok, whatsapp, limba, rezervare_token
     FROM restaurante WHERE rezervare_token = ? AND activ = 1`,
    token
  );
  if (!r) return c.json({ ok: false, msg: 'Restaurant indisponibil' }, 404);

  const categorii = await all<Record<string, unknown>>(
    c.env.DB,
    'SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id',
    r.id
  );
  for (const cat of categorii) {
    cat.produse = await all(
      c.env.DB,
      'SELECT * FROM meniu_produse WHERE categorie_id = ? AND restaurant_id = ? AND disponibil = 1 ORDER BY ordine, id',
      cat.id,
      r.id
    );
  }

  const social: Record<string, string> = {};
  if (r.facebook) social.facebook = r.facebook;
  if (r.instagram) social.instagram = r.instagram;
  if (r.tiktok) social.tiktok = r.tiktok;
  if (r.whatsapp) social.whatsapp = r.whatsapp;

  const agg = await one<{ medie: number | null; total: number }>(
    c.env.DB,
    'SELECT AVG(rating) AS medie, COUNT(*) AS total FROM recenzii WHERE restaurant_id = ?',
    r.id
  );
  const recente = await all(
    c.env.DB,
    'SELECT rating, comentariu, nume, created_at FROM recenzii WHERE restaurant_id = ? AND comentariu IS NOT NULL ORDER BY created_at DESC LIMIT 5',
    r.id
  );

  return c.json({
    ok: true,
    rezervare_token: r.rezervare_token,
    restaurant: {
      nume: r.nume,
      logo: r.logo,
      tema: r.tema,
      tema_cfg: getTheme(r.tema),
      bg_imagine: r.bg_imagine,
      text_bun_venit: r.text_bun_venit,
      social,
      limba: r.limba ?? 'ro',
    },
    categorii,
    recenzii: { medie: agg?.medie ?? null, total: agg?.total ?? 0, recente },
  });
});

export default local;

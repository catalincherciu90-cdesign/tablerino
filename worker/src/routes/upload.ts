import { Hono } from 'hono';
import type { AppContext, HonoEnv } from '../types';
import { one, run } from '../db';
import { requireRestaurant } from '../auth';
import { deleteUpload, extToContentType, fileExt, putUpload } from '../r2';

// Image uploads to R2 — port of admin/upload_imagine.php, upload_poza.php,
// upload_reclama.php. All require an authenticated restaurant.
const upload = new Hono<HonoEnv>();
upload.use('/*', requireRestaurant);

const rid = (c: AppContext) => c.get('restaurant')!.rid;
const now = () => Math.floor(Date.now() / 1000);

// POST /api/upload/imagine  — logo or background (field `imagine`, `tip`=logo|bg)
upload.post('/imagine', async (c) => {
  const form = await c.req.formData();
  const tip = form.get('tip');
  if (tip !== 'logo' && tip !== 'bg') return c.json({ ok: false, msg: 'Tip invalid' }, 400);

  const file: unknown = form.get('imagine');
  if (!(file instanceof File) || file.size === 0) return c.json({ ok: false, msg: 'Fișier invalid' }, 400);
  const ext = fileExt(file.name);
  if (!['jpg', 'jpeg', 'png', 'webp', 'svg'].includes(ext)) {
    return c.json({ ok: false, msg: 'Format neacceptat. Folosește JPG, PNG, WEBP sau SVG.' }, 400);
  }
  if (file.size > 3 * 1024 * 1024) return c.json({ ok: false, msg: 'Fișierul e prea mare. Maximum 3MB.' }, 400);

  const camp = tip === 'logo' ? 'logo' : 'bg_imagine';
  const sub = tip === 'logo' ? 'logo' : 'bg';

  const r = await one<Record<string, string | null>>(c.env.DB, `SELECT ${camp} AS val FROM restaurante WHERE id = ?`, rid(c));
  if (r?.val) await deleteUpload(c.env.UPLOADS, r.val);

  const name = `${tip}_${rid(c)}_${now()}.${ext}`;
  const cale = await putUpload(c.env.UPLOADS, sub, name, await file.arrayBuffer(), extToContentType(ext) ?? 'image/jpeg');
  await run(c.env.DB, `UPDATE restaurante SET ${camp} = ? WHERE id = ?`, cale, rid(c));
  return c.json({ ok: true, cale });
});

// POST /api/upload/poza  — product photo (field `poza`, `produs_id`)
upload.post('/poza', async (c) => {
  const form = await c.req.formData();
  const pid = Number(form.get('produs_id') ?? 0);
  if (!pid) return c.json({ ok: false, msg: 'ID produs lipsă' }, 400);

  const produs = await one<{ id: number; poza: string | null }>(
    c.env.DB,
    'SELECT id, poza FROM meniu_produse WHERE id = ? AND restaurant_id = ?',
    pid,
    rid(c)
  );
  if (!produs) return c.json({ ok: false, msg: 'Produs negăsit' }, 404);

  const file: unknown = form.get('poza');
  if (!(file instanceof File) || file.size === 0) return c.json({ ok: false, msg: 'Fișier invalid' }, 400);
  const ext = fileExt(file.name);
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
    return c.json({ ok: false, msg: 'Format neacceptat. Folosește JPG, PNG sau WEBP.' }, 400);
  }
  if (file.size > 2 * 1024 * 1024) return c.json({ ok: false, msg: 'Poza e prea mare. Maximum 2MB.' }, 400);

  if (produs.poza) await deleteUpload(c.env.UPLOADS, produs.poza);

  const name = `prod_${rid(c)}_${pid}_${now()}.${ext}`;
  const poza = await putUpload(c.env.UPLOADS, 'produse', name, await file.arrayBuffer(), extToContentType(ext) ?? 'image/jpeg');
  await run(c.env.DB, 'UPDATE meniu_produse SET poza = ? WHERE id = ? AND restaurant_id = ?', poza, pid, rid(c));
  return c.json({ ok: true, poza });
});

// POST /api/upload/reclama  — ad image (field `imagine`, `reclama_id`)
upload.post('/reclama', async (c) => {
  const form = await c.req.formData();
  const reclamaId = Number(form.get('reclama_id') ?? 0);
  if (!reclamaId) return c.json({ ok: false, msg: 'ID lipsă' }, 400);

  const reclama = await one<{ id: number; imagine: string | null }>(
    c.env.DB,
    'SELECT id, imagine FROM reclame WHERE id = ? AND restaurant_id = ?',
    reclamaId,
    rid(c)
  );
  if (!reclama) return c.json({ ok: false, msg: 'Reclamă negăsită' }, 404);

  const file: unknown = form.get('imagine');
  if (!(file instanceof File) || file.size === 0) return c.json({ ok: false, msg: 'Fișier invalid' }, 400);
  const ext = fileExt(file.name);
  if (!['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) return c.json({ ok: false, msg: 'Format neacceptat' }, 400);
  if (file.size > 2 * 1024 * 1024) return c.json({ ok: false, msg: 'Maxim 2MB' }, 400);

  if (reclama.imagine) await deleteUpload(c.env.UPLOADS, reclama.imagine);

  const name = `reclama_${rid(c)}_${reclamaId}_${now()}.${ext}`;
  const cale = await putUpload(c.env.UPLOADS, 'reclame', name, await file.arrayBuffer(), extToContentType(ext) ?? 'image/jpeg');
  await run(c.env.DB, 'UPDATE reclame SET imagine = ? WHERE id = ?', cale, reclamaId);
  return c.json({ ok: true, cale });
});

export default upload;

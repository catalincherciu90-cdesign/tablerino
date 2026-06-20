import { Hono } from 'hono';
import type { AppContext, HonoEnv } from '../types';
import { one, run } from '../db';
import { requireRestaurant, requireOwner } from '../auth';
import { fileExt } from '../r2';

// AI menu extraction via Groq — port of admin/ai_meniu.php. Owner only
// (it adds/edits the menu).
const ai = new Hono<HonoEnv>();
ai.use('/*', requireRestaurant);
ai.use('/*', requireOwner);

const rid = (c: AppContext) => c.get('restaurant')!.rid;

const PROMPT = `Analyze this restaurant menu image and extract all products. Reply ONLY with valid JSON, no extra text, no markdown, no explanations.

The JSON format must be exactly:
{"categorii":[{"nume":"Category name","produse":[{"nume":"Product name","descriere":"Short description","pret":0.00,"ingrediente":"ingredient1, ingredient2","calorii":0,"proteine":0.00,"carbohidrati":0.00,"grasimi":0.00,"alergeni":"gluten, lactoza"}]}]}

Rules:
- Group products into logical categories (Appetizers, Main course, Desserts, Drinks etc.)
- Price must be decimal number. If not visible use 0.00
- Description max 80 characters
- ingrediente: comma separated list of ingredients if visible, empty string if not
- calorii: integer, 0 if not visible
- proteine/carbohidrati/grasimi: decimal numbers in grams, 0.00 if not visible
- alergeni: comma separated list of allergens if visible, empty string if not
- Reply ONLY with JSON, nothing else`;

function base64(bytes: Uint8Array): string {
  let s = '';
  const chunk = 0x8000;
  for (let i = 0; i < bytes.length; i += chunk) {
    s += String.fromCharCode(...bytes.subarray(i, i + chunk));
  }
  return btoa(s);
}

interface MenuProduct {
  nume?: string; descriere?: string; pret?: number; ingrediente?: string;
  calorii?: number; proteine?: number; carbohidrati?: number; grasimi?: number; alergeni?: string;
}
interface MenuCategory { nume?: string; produse?: MenuProduct[]; }

// POST /api/ai/extrage  — upload a menu photo (field `poza`), get extracted menu JSON
ai.post('/extrage', async (c) => {
  const form = await c.req.formData();
  const file: unknown = form.get('poza');
  if (!(file instanceof File) || file.size === 0) {
    return c.json({ ok: false, msg: 'Fișier invalid' });
  }
  const ext = fileExt(file.name);
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
    return c.json({ ok: false, msg: 'Format invalid. Acceptat: JPG, PNG, WEBP' });
  }
  if (file.size > 10 * 1024 * 1024) {
    return c.json({ ok: false, msg: 'Poza este prea mare (max 10MB)' });
  }

  const mediaType = ext === 'png' ? 'image/png' : ext === 'webp' ? 'image/webp' : 'image/jpeg';
  const dataUrl = `data:${mediaType};base64,${base64(new Uint8Array(await file.arrayBuffer()))}`;

  let resp: Response;
  try {
    resp = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${c.env.GROQ_KEY}` },
      body: JSON.stringify({
        model: c.env.GROQ_MODEL,
        messages: [
          {
            role: 'user',
            content: [
              { type: 'image_url', image_url: { url: dataUrl } },
              { type: 'text', text: PROMPT },
            ],
          },
        ],
        max_tokens: 2000,
        temperature: 0.1,
      }),
    });
  } catch {
    return c.json({ ok: false, msg: 'Eroare la conectarea cu AI.' });
  }

  if (!resp.ok) {
    const err = (await resp.json().catch(() => null)) as { error?: { message?: string } } | null;
    return c.json({ ok: false, msg: err?.error?.message ?? 'Eroare la conectarea cu AI.' });
  }

  const result = (await resp.json()) as { choices?: { message?: { content?: string } }[] };
  let text = (result.choices?.[0]?.message?.content ?? '').trim();
  text = text.replace(/^```json\s*/i, '').replace(/\s*```$/i, '').trim();

  let meniu: { categorii?: MenuCategory[] } | null = null;
  try {
    meniu = JSON.parse(text);
  } catch {
    meniu = null;
  }
  if (!meniu || !meniu.categorii) {
    return c.json({
      ok: false,
      msg: 'Nu am putut extrage produse din această imagine. Încearcă cu o poză mai clară a meniului.',
    });
  }
  return c.json({ ok: true, meniu });
});

// POST /api/ai/salveaza  — persist the (user-confirmed) extracted menu
ai.post('/salveaza', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const categorii: MenuCategory[] = body.categorii ?? [];
  let salvate = 0;

  for (const cat of categorii) {
    const numeCat = (cat.nume ?? '').trim();
    if (!numeCat) continue;

    const catRow = await one<{ id: number }>(
      c.env.DB,
      'SELECT id FROM meniu_categorii WHERE restaurant_id = ? AND nume = ?',
      rid(c),
      numeCat
    );
    let catId: number;
    if (catRow) {
      catId = catRow.id;
    } else {
      const ins = await run(c.env.DB, 'INSERT INTO meniu_categorii (restaurant_id, nume) VALUES (?, ?)', rid(c), numeCat);
      catId = ins.meta.last_row_id;
    }

    for (const produs of cat.produse ?? []) {
      const numeProd = (produs.nume ?? '').trim();
      if (!numeProd) continue;
      const desc = (produs.descriere ?? '').trim();
      const pret = Number(produs.pret ?? 0);
      const ingrediente = (produs.ingrediente ?? '').trim();
      const calorii = Number(produs.calorii ?? 0);
      const proteine = Number(produs.proteine ?? 0);
      const carbohidrati = Number(produs.carbohidrati ?? 0);
      const grasimi = Number(produs.grasimi ?? 0);
      const alergeni = (produs.alergeni ?? '').trim();

      await run(
        c.env.DB,
        'INSERT INTO meniu_produse (categorie_id, restaurant_id, nume, descriere, pret, ingrediente, calorii, proteine, carbohidrati, grasimi, alergeni) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        catId,
        rid(c),
        numeProd,
        desc || null,
        pret,
        ingrediente || null,
        calorii || null,
        proteine || null,
        carbohidrati || null,
        grasimi || null,
        alergeni || null
      );
      salvate++;
    }
  }

  return c.json({ ok: true, salvate });
});

export default ai;

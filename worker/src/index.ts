import { Hono } from 'hono';
import type { HonoEnv } from './types';
import { serveUpload } from './r2';
import table from './routes/table';
import admin from './routes/admin';
import master from './routes/master';
import register from './routes/register';
import rezervare from './routes/rezervari';
import local from './routes/local';
import upload from './routes/upload';
import ai from './routes/ai';

const app = new Hono<HonoEnv>();

// ── Health check ──
app.get('/api/health', (c) => c.json({ ok: true, service: 'tablerino', ts: Date.now() }));

// ── Is the public portal / reservations directory live? (global "production" switch) ──
async function portalActiv(env: HonoEnv['Bindings']): Promise<boolean> {
  const { one } = await import('./db');
  const row = await one<{ valoare: string }>(env.DB, "SELECT valoare FROM app_config WHERE cheie = 'portal_activ'");
  return row?.valoare === '1';
}

// ── Public landing settings (defaults merged with master overrides) ──
app.get('/api/landing', async (c) => {
  const { all } = await import('./db');
  const { LANDING_DEFAULTS } = await import('./landingDefaults');
  const settings: Record<string, string> = { ...LANDING_DEFAULTS };
  const rows = await all<{ cheie: string; valoare: string }>(c.env.DB, 'SELECT cheie, valoare FROM landing_settings');
  for (const row of rows) settings[row.cheie] = row.valoare;
  return c.json({ ok: true, settings, portal_activ: (await portalActiv(c.env)) ? 1 : 0 });
});

// ── Uploaded images: R2 first (user uploads) when configured, then static
//    assets (app icons, landing images shipped in public/uploads/) ──
app.get('/uploads/*', async (c) => {
  if (c.env.UPLOADS) {
    const res = await serveUpload(c.env.UPLOADS, new URL(c.req.url).pathname);
    if (res) return res;
  }
  return c.env.ASSETS.fetch(c.req.raw);
});

// ── API routes ──
app.route('/api/table', table); // masa/api.php
app.route('/api/admin', admin); // admin/api.php + admin/login.php
app.route('/api/master', master); // master/api.php + master/login.php
app.route('/api/register', register); // api/inregistrare.php
app.route('/api/rezervare', rezervare); // public reservations
app.route('/api/local', local); // public restaurant profile
app.route('/api/upload', upload); // admin/upload_*.php
app.route('/api/ai', ai); // admin/ai_meniu.php

// ── Root: serve the diner portal when enabled, else the B2B landing ──
app.get('/', async (c) => {
  const file = (await portalActiv(c.env)) ? '/portal.html' : '/index.html';
  const url = new URL(c.req.url);
  url.pathname = file;
  return c.env.ASSETS.fetch(new Request(url.toString(), { headers: c.req.raw.headers }));
});

// ── Public reservations directory: only live when the portal is ON (production). ──
//    When OFF, bounce to the landing so the feature is never reachable early.
app.get('/rezerva', async (c) => c.redirect((await portalActiv(c.env)) ? '/rezerva/' : '/', 302));
app.get('/rezerva/', async (c) => {
  if (!(await portalActiv(c.env))) return c.redirect('/', 302);
  const url = new URL(c.req.url);
  url.pathname = '/rezerva/index.html';
  return c.env.ASSETS.fetch(new Request(url.toString(), { headers: c.req.raw.headers }));
});

// ── Everything else falls through to the static frontend in ./public ──
app.all('*', (c) => c.env.ASSETS.fetch(c.req.raw));

export default {
  fetch: app.fetch,
  // Cron: every minute, warn waiters about orders about to escalate.
  async scheduled(_event: ScheduledEvent, env: HonoEnv['Bindings'], ctx: ExecutionContext) {
    const { notifyEscalations } = await import('./webpush');
    ctx.waitUntil(notifyEscalations(env.DB));
  },
};

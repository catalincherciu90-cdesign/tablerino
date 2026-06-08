import { Hono } from 'hono';
import type { HonoEnv } from './types';
import { serveUpload } from './r2';
import table from './routes/table';
import admin from './routes/admin';
import master from './routes/master';
import register from './routes/register';
import upload from './routes/upload';
import ai from './routes/ai';

const app = new Hono<HonoEnv>();

// ── Health check ──
app.get('/api/health', (c) => c.json({ ok: true, service: 'tablerino', ts: Date.now() }));

// ── Uploaded images, served from R2 (replaces the PHP uploads/ filesystem) ──
app.get('/uploads/*', async (c) => {
  const res = await serveUpload(c.env.UPLOADS, new URL(c.req.url).pathname);
  return res ?? c.notFound();
});

// ── API routes ──
app.route('/api/table', table); // masa/api.php
app.route('/api/admin', admin); // admin/api.php + admin/login.php
app.route('/api/master', master); // master/api.php + master/login.php
app.route('/api/register', register); // api/inregistrare.php
app.route('/api/upload', upload); // admin/upload_*.php
app.route('/api/ai', ai); // admin/ai_meniu.php

// ── Everything else falls through to the static frontend in ./public ──
app.all('*', (c) => c.env.ASSETS.fetch(c.req.raw));

export default app;

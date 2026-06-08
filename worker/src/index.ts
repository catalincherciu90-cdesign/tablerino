import { Hono } from 'hono';
import type { HonoEnv } from './types';
import { serveUpload } from './r2';
import table from './routes/table';

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

// ── API routes (mounted in later phases) ──
// app.route('/api/admin', adminRoutes);     // phase 3 — admin/api.php
// app.route('/api/master', masterRoutes);   // phase 4 — master/api.php
// app.route('/api/register', registerRoutes); // phase 4 — api/inregistrare.php
// app.route('/api/upload', uploadRoutes);   // phase 5 — admin/upload_*.php
// app.route('/api/ai', aiRoutes);           // phase 5 — admin/ai_meniu.php

// ── Everything else falls through to the static frontend in ./public ──
app.all('*', (c) => c.env.ASSETS.fetch(c.req.raw));

export default app;

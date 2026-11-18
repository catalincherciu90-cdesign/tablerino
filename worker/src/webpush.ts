// Minimal Web Push (VAPID + RFC 8291 "aes128gcm") sender for Cloudflare Workers,
// built on WebCrypto. No external dependencies.

import { all, one } from './db';

export interface PushSubscription {
  endpoint: string;
  p256dh: string; // client public key (base64url, 65 bytes raw)
  auth: string;   // client auth secret (base64url, 16 bytes)
}

export interface VapidConfig {
  publicKey: string;   // base64url raw (65 bytes) — the applicationServerKey
  privateKey: string;  // base64url PKCS8 DER
  subject: string;     // e.g. "mailto:contact@example.com"
}

// ── base64url helpers ──
function b64urlToBytes(s: string): Uint8Array {
  s = s.replace(/-/g, '+').replace(/_/g, '/');
  while (s.length % 4) s += '=';
  const bin = atob(s);
  const out = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
  return out;
}
function bytesToB64url(bytes: ArrayBuffer | Uint8Array): string {
  const b = bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes);
  let bin = '';
  for (let i = 0; i < b.length; i++) bin += String.fromCharCode(b[i]);
  return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function concat(...arrs: Uint8Array[]): Uint8Array {
  const len = arrs.reduce((n, a) => n + a.length, 0);
  const out = new Uint8Array(len);
  let o = 0;
  for (const a of arrs) { out.set(a, o); o += a.length; }
  return out;
}

async function hkdf(salt: Uint8Array, ikm: Uint8Array, info: Uint8Array, len: number): Promise<Uint8Array> {
  const key = await crypto.subtle.importKey('raw', ikm, 'HKDF', false, ['deriveBits']);
  const bits = await crypto.subtle.deriveBits({ name: 'HKDF', hash: 'SHA-256', salt, info }, key, len * 8);
  return new Uint8Array(bits);
}

// ── VAPID JWT (ES256) ──
async function vapidAuthHeader(endpoint: string, cfg: VapidConfig): Promise<string> {
  const aud = new URL(endpoint).origin;
  const enc = new TextEncoder();
  const header = bytesToB64url(enc.encode(JSON.stringify({ typ: 'JWT', alg: 'ES256' })));
  const payload = bytesToB64url(enc.encode(JSON.stringify({
    aud,
    exp: Math.floor(Date.now() / 1000) + 12 * 3600,
    sub: cfg.subject,
  })));
  const signingInput = `${header}.${payload}`;
  const key = await crypto.subtle.importKey(
    'pkcs8', b64urlToBytes(cfg.privateKey),
    { name: 'ECDSA', namedCurve: 'P-256' }, false, ['sign']
  );
  const sig = await crypto.subtle.sign({ name: 'ECDSA', hash: 'SHA-256' }, key, enc.encode(signingInput));
  const jwt = `${signingInput}.${bytesToB64url(sig)}`;
  return `vapid t=${jwt}, k=${cfg.publicKey}`;
}

// ── Payload encryption (RFC 8291 / aes128gcm) ──
async function encryptPayload(plaintext: Uint8Array, sub: PushSubscription): Promise<Uint8Array> {
  const uaPublic = b64urlToBytes(sub.p256dh);
  const authSecret = b64urlToBytes(sub.auth);

  const serverKeys = (await crypto.subtle.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits'])) as CryptoKeyPair;
  const asPublic = new Uint8Array((await crypto.subtle.exportKey('raw', serverKeys.publicKey)) as ArrayBuffer);
  const uaPubKey = await crypto.subtle.importKey('raw', uaPublic, { name: 'ECDH', namedCurve: 'P-256' }, false, []);
  const ecdhSecret = new Uint8Array(
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    await crypto.subtle.deriveBits({ name: 'ECDH', public: uaPubKey } as any, serverKeys.privateKey, 256)
  );

  const enc = new TextEncoder();
  const keyInfo = concat(enc.encode('WebPush: info\0'), uaPublic, asPublic);
  const ikm = await hkdf(authSecret, ecdhSecret, keyInfo, 32);

  const salt = crypto.getRandomValues(new Uint8Array(16));
  const cek = await hkdf(salt, ikm, enc.encode('Content-Encoding: aes128gcm\0'), 16);
  const nonce = await hkdf(salt, ikm, enc.encode('Content-Encoding: nonce\0'), 12);

  // Single record: append 0x02 padding delimiter, then AES-128-GCM.
  const record = concat(plaintext, new Uint8Array([0x02]));
  const aesKey = await crypto.subtle.importKey('raw', cek, { name: 'AES-GCM' }, false, ['encrypt']);
  const ciphertext = new Uint8Array(
    await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce }, aesKey, record)
  );

  // aes128gcm header: salt(16) | rs(4) | idlen(1) | keyid(as_public, 65)
  const rs = new Uint8Array([0x00, 0x00, 0x10, 0x00]); // 4096
  const idlen = new Uint8Array([asPublic.length]);
  return concat(salt, rs, idlen, asPublic, ciphertext);
}

/** Send one push. Returns the HTTP status (201 = ok; 404/410 = gone → prune). */
export async function sendPush(sub: PushSubscription, payload: unknown, cfg: VapidConfig): Promise<number> {
  const body = await encryptPayload(new TextEncoder().encode(JSON.stringify(payload)), sub);
  const auth = await vapidAuthHeader(sub.endpoint, cfg);
  const res = await fetch(sub.endpoint, {
    method: 'POST',
    headers: {
      'Authorization': auth,
      'Content-Encoding': 'aes128gcm',
      'Content-Type': 'application/octet-stream',
      'TTL': '600',
      'Urgency': 'high',
    },
    body,
  });
  return res.status;
}

// ── DB helpers ──
export async function getVapidConfig(db: D1Database): Promise<VapidConfig | null> {
  const rows = await all<{ cheie: string; valoare: string }>(
    db, "SELECT cheie, valoare FROM app_config WHERE cheie IN ('vapid_public','vapid_private','vapid_subject')"
  );
  const m: Record<string, string> = {};
  for (const r of rows) m[r.cheie] = r.valoare;
  if (!m.vapid_public || !m.vapid_private) return null;
  return { publicKey: m.vapid_public, privateKey: m.vapid_private, subject: m.vapid_subject || 'mailto:contact@tablerino.ro' };
}

interface SubRow { id: number; endpoint: string; p256dh: string; auth: string; }

/** Push a message to every subscription of the given waiters; prunes dead ones. */
export async function pushToOspatari(
  db: D1Database, cfg: VapidConfig, ospatarIds: number[], payload: unknown
): Promise<void> {
  if (!ospatarIds.length) return;
  const placeholders = ospatarIds.map(() => '?').join(',');
  const subs = await all<SubRow>(
    db, `SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE ospatar_id IN (${placeholders})`,
    ...ospatarIds
  );
  for (const s of subs) {
    try {
      const status = await sendPush({ endpoint: s.endpoint, p256dh: s.p256dh, auth: s.auth }, payload, cfg);
      if (status === 404 || status === 410) {
        await db.prepare('DELETE FROM push_subscriptions WHERE id = ?').bind(s.id).run();
      }
    } catch (e) { /* ignore a single failed endpoint */ }
  }
}

/** Cron job: warn each responsible waiter when their unclaimed order is ~30-45s
 *  from escalating to other waiters. Runs every minute. */
export async function notifyEscalations(db: D1Database): Promise<void> {
  const cfg = await getVapidConfig(db);
  if (!cfg) return;
  const rows = await all<{ id: number; masa_nume: string; ospatar_id: number }>(
    db,
    `SELECT c.id, m.nume AS masa_nume, m.ospatar_id
     FROM comenzi c
     JOIN mese m ON m.id = c.masa_id
     JOIN restaurante r ON r.id = c.restaurant_id
     WHERE c.preluat_de IS NULL
       AND m.ospatar_id IS NOT NULL
       AND c.push_escalare_trimis = 0
       AND c.status NOT IN ('servita','plata_aleasa')
       AND (strftime('%s','now') - strftime('%s', c.created_at)) >= COALESCE(r.escalare_sec, 120) - 45
       AND (strftime('%s','now') - strftime('%s', c.created_at)) <  COALESCE(r.escalare_sec, 120)`
  );
  for (const row of rows) {
    await pushToOspatari(db, cfg, [row.ospatar_id], {
      type: 'escalare',
      title: '⏰ Îți scapă o comandă',
      body: `${row.masa_nume} — preia acum, altfel trece la alt ospătar`,
      url: '/ospatar/',
      tag: `escalare-${row.id}`,
    });
    await db.prepare('UPDATE comenzi SET push_escalare_trimis = 1 WHERE id = ?').bind(row.id).run();
  }
}

export { one };

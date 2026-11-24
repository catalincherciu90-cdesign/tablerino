// Firebase Cloud Messaging (HTTP v1) sender for Cloudflare Workers. Used to push
// to the native Android (.apk) waiter app, which registers FCM device tokens.
// The service-account credentials live in app_config (key 'fcm_service_account'),
// not in source.

import { all } from './db';

interface ServiceAccount {
  project_id: string;
  client_email: string;
  private_key: string; // PEM PKCS8
}

function b64urlFromBytes(bytes: ArrayBuffer | Uint8Array): string {
  const b = bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes);
  let bin = '';
  for (let i = 0; i < b.length; i++) bin += String.fromCharCode(b[i]);
  return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function b64urlFromString(s: string): string {
  return b64urlFromBytes(new TextEncoder().encode(s));
}
function pemToDer(pem: string): Uint8Array {
  const body = pem.replace(/-----BEGIN [^-]+-----/, '').replace(/-----END [^-]+-----/, '').replace(/\s+/g, '');
  const bin = atob(body);
  const out = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
  return out;
}

async function getServiceAccount(db: D1Database): Promise<ServiceAccount | null> {
  const rows = await all<{ cheie: string; valoare: string }>(
    db, "SELECT cheie, valoare FROM app_config WHERE cheie = 'fcm_service_account'"
  );
  if (!rows.length) return null;
  try {
    const sa = JSON.parse(rows[0].valoare) as ServiceAccount;
    if (!sa.project_id || !sa.client_email || !sa.private_key) return null;
    return sa;
  } catch { return null; }
}

/** Mint an OAuth2 access token for the FCM scope using the service account (RS256 JWT). */
async function getAccessToken(sa: ServiceAccount): Promise<string | null> {
  const now = Math.floor(Date.now() / 1000);
  const header = b64urlFromString(JSON.stringify({ alg: 'RS256', typ: 'JWT' }));
  const claims = b64urlFromString(JSON.stringify({
    iss: sa.client_email,
    scope: 'https://www.googleapis.com/auth/firebase.messaging',
    aud: 'https://oauth2.googleapis.com/token',
    iat: now,
    exp: now + 3600,
  }));
  const signingInput = `${header}.${claims}`;
  const key = await crypto.subtle.importKey(
    'pkcs8', pemToDer(sa.private_key),
    { name: 'RSASSA-PKCS1-v1_5', hash: 'SHA-256' }, false, ['sign']
  );
  const sig = await crypto.subtle.sign('RSASSA-PKCS1-v1_5', key, new TextEncoder().encode(signingInput));
  const jwt = `${signingInput}.${b64urlFromBytes(sig)}`;

  const res = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=${jwt}`,
  });
  if (!res.ok) return null;
  const data = (await res.json().catch(() => ({}))) as { access_token?: string };
  return data.access_token ?? null;
}

interface FcmPayload { title: string; body: string; url?: string; tag?: string; type?: string; }

/** Send a push to every FCM token of the given waiters; prunes dead tokens. */
export async function pushFcmToOspatari(db: D1Database, ospatarIds: number[], payload: FcmPayload): Promise<void> {
  if (!ospatarIds.length) return;
  const sa = await getServiceAccount(db);
  if (!sa) return; // FCM not configured yet
  const token = await getAccessToken(sa);
  if (!token) return;

  const placeholders = ospatarIds.map(() => '?').join(',');
  const rows = await all<{ id: number; token: string }>(
    db, `SELECT id, token FROM fcm_tokens WHERE ospatar_id IN (${placeholders})`, ...ospatarIds
  );
  const url = `https://fcm.googleapis.com/v1/projects/${sa.project_id}/messages:send`;
  for (const r of rows) {
    const message = {
      message: {
        token: r.token,
        notification: { title: payload.title, body: payload.body },
        data: {
          url: payload.url ?? '/ospatar/',
          type: payload.type ?? '',
          tag: payload.tag ?? '',
        },
        android: {
          priority: 'HIGH',
          notification: { sound: 'default', default_vibrate_timings: true, tag: payload.tag ?? undefined },
        },
      },
    };
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        body: JSON.stringify(message),
      });
      // 404 / 400 UNREGISTERED → token no longer valid, prune it.
      if (res.status === 404 || res.status === 400) {
        const txt = await res.text().catch(() => '');
        if (res.status === 404 || /UNREGISTERED|INVALID_ARGUMENT/.test(txt)) {
          await db.prepare('DELETE FROM fcm_tokens WHERE id = ?').bind(r.id).run();
        }
      }
    } catch (e) { /* ignore a single failed token */ }
  }
}

/** Is FCM configured (service account present)? */
export async function fcmConfigured(db: D1Database): Promise<boolean> {
  return (await getServiceAccount(db)) != null;
}

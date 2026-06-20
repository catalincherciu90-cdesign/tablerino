import { getCookie, setCookie, deleteCookie } from 'hono/cookie';
import bcrypt from 'bcryptjs';
import type { MiddlewareHandler } from 'hono';
import type { AppContext, HonoEnv, MasterSession, RestaurantSession } from './types';
import { one } from './db';

const RESTAURANT_COOKIE = 'tbr_sess';
const MASTER_COOKIE = 'tbr_master';
const SESSION_TTL = 60 * 60 * 24 * 30; // 30 days

// ── Password hashing (bcrypt, compatible with the PHP password_hash data) ──

export function hashPassword(plain: string): Promise<string> {
  return bcrypt.hash(plain, 10);
}

export function verifyPassword(plain: string, hash: string): Promise<boolean> {
  return bcrypt.compare(plain, hash);
}

// ── Signed token (JWT-like): base64url(payload).base64url(HMAC-SHA256) ──

const enc = new TextEncoder();
const dec = new TextDecoder();

function b64urlEncode(bytes: Uint8Array): string {
  let s = '';
  for (const b of bytes) s += String.fromCharCode(b);
  return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function b64urlDecode(str: string): Uint8Array {
  const s = str.replace(/-/g, '+').replace(/_/g, '/');
  const pad = s.length % 4 ? '='.repeat(4 - (s.length % 4)) : '';
  const bin = atob(s + pad);
  const out = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
  return out;
}

async function hmacKey(secret: string): Promise<CryptoKey> {
  return crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign', 'verify']
  );
}

async function sign(payload: object, secret: string, ttl = SESSION_TTL): Promise<string> {
  const body = { ...payload, exp: Math.floor(Date.now() / 1000) + ttl };
  const data = b64urlEncode(enc.encode(JSON.stringify(body)));
  const key = await hmacKey(secret);
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(data));
  return `${data}.${b64urlEncode(new Uint8Array(sig))}`;
}

async function verify<T>(token: string, secret: string): Promise<T | null> {
  const parts = token.split('.');
  if (parts.length !== 2) return null;
  const [data, sig] = parts;
  const key = await hmacKey(secret);
  const ok = await crypto.subtle.verify('HMAC', key, b64urlDecode(sig), enc.encode(data));
  if (!ok) return null;
  try {
    const payload = JSON.parse(dec.decode(b64urlDecode(data))) as T & { exp: number };
    if (payload.exp < Math.floor(Date.now() / 1000)) return null;
    return payload;
  } catch {
    return null;
  }
}

// ── Session cookies ──

function cookieOpts() {
  return {
    httpOnly: true,
    secure: true,
    sameSite: 'Lax' as const,
    path: '/',
    maxAge: SESSION_TTL,
  };
}

export async function loginRestaurant(c: AppContext, sess: RestaurantSession): Promise<void> {
  const token = await sign(sess, c.env.SESSION_SECRET);
  setCookie(c, RESTAURANT_COOKIE, token, cookieOpts());
}

export async function loginMaster(c: AppContext, sess: MasterSession): Promise<void> {
  const token = await sign(sess, c.env.SESSION_SECRET);
  setCookie(c, MASTER_COOKIE, token, cookieOpts());
}

export function logoutRestaurant(c: AppContext): void {
  deleteCookie(c, RESTAURANT_COOKIE, { path: '/' });
}

export function logoutMaster(c: AppContext): void {
  deleteCookie(c, MASTER_COOKIE, { path: '/' });
}

export async function currentRestaurant(c: AppContext): Promise<RestaurantSession | null> {
  const token = getCookie(c, RESTAURANT_COOKIE);
  if (!token) return null;
  return verify<RestaurantSession>(token, c.env.SESSION_SECRET);
}

export async function currentMaster(c: AppContext): Promise<MasterSession | null> {
  const token = getCookie(c, MASTER_COOKIE);
  if (!token) return null;
  return verify<MasterSession>(token, c.env.SESSION_SECRET);
}

// ── Middlewares ──

/** Requires a valid restaurant session; attaches it to c.var.restaurant. */
export const requireRestaurant: MiddlewareHandler<HonoEnv> = async (c, next) => {
  const sess = await currentRestaurant(c as AppContext);
  if (!sess) return c.json({ ok: false, msg: 'Neautentificat' }, 401);
  c.set('restaurant', sess);
  await next();
};

/** Requires the restaurant OWNER (not a waiter). Run after requireRestaurant. */
export const requireOwner: MiddlewareHandler<HonoEnv> = async (c, next) => {
  const sess = c.get('restaurant');
  if (!sess || sess.role !== 'owner') {
    return c.json({ ok: false, msg: 'Doar contul principal al restaurantului poate face asta.' }, 403);
  }
  await next();
};

/** Requires a valid master session; attaches it to c.var.master. */
export const requireMaster: MiddlewareHandler<HonoEnv> = async (c, next) => {
  const sess = await currentMaster(c as AppContext);
  if (!sess) return c.json({ ok: false, msg: 'Neautentificat' }, 401);
  c.set('master', sess);
  await next();
};

// ── Table (masa) token auth — stateless, no cookie ──

export interface MasaRow {
  id: number;
  restaurant_id: number;
  nume: string;
  token: string;
  activa: number;
}

/** Validate a table token; returns the active table row or null. */
export function getMasa(db: D1Database, token: string): Promise<MasaRow | null> {
  if (!token) return Promise.resolve(null);
  return one<MasaRow>(
    db,
    'SELECT id, restaurant_id, nume, token, activa FROM mese WHERE token = ? AND activa = 1',
    token
  );
}

/** Generate a unique table token (hex 16 bytes), matching bin2hex(random_bytes(16)). */
export function generateToken(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(16));
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}

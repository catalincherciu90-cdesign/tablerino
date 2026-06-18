import type { Context } from 'hono';

/** Cloudflare bindings + configuration available to the Worker. */
export interface Env {
  /** D1 database (binding `DB`). */
  DB: D1Database;
  /** R2 bucket for uploaded images (binding `UPLOADS`). Optional — when the
   *  bucket isn't configured, image upload/serve features are disabled. */
  UPLOADS?: R2Bucket;
  /** Static assets (binding `ASSETS`) — the frontend in ./public. */
  ASSETS: Fetcher;

  /** Public base URL of the deployment, e.g. https://tablerino.ro */
  BASE_URL: string;
  /** Groq vision model id used by the AI menu extractor. */
  GROQ_MODEL: string;

  /** Secret: Groq API key (wrangler secret put GROQ_KEY). */
  GROQ_KEY: string;
  /** Secret: HMAC key for signing session cookies (wrangler secret put SESSION_SECRET). */
  SESSION_SECRET: string;
}

/** Authenticated restaurant-scoped session (owner or waiter). */
export interface RestaurantSession {
  rid: number;
  nume: string;
  role: 'owner' | 'ospatar';
}

/** Authenticated master session payload (replaces $_SESSION master_*). */
export interface MasterSession {
  master: true;
  email: string;
}

/** Variables attached to the Hono context after auth middleware runs. */
export interface Variables {
  restaurant?: RestaurantSession;
  master?: MasterSession;
}

export type AppContext = Context<{ Bindings: Env; Variables: Variables }>;
export type HonoEnv = { Bindings: Env; Variables: Variables };

// Subscription billing — processor-agnostic core. The actual processor
// (Netopia/PayU) is wired on top of this once merchant credentials exist.
import { one, run } from './db';

/** Add one calendar month to a YYYY-MM-DD date (UTC). */
export function addOneMonth(d: string): string {
  const [y, m, day] = d.split('-').map(Number);
  const dt = new Date(Date.UTC(y, m - 1, day));
  dt.setUTCMonth(dt.getUTCMonth() + 1);
  return dt.toISOString().slice(0, 10);
}

/** Is an online payment processor configured and active? */
export async function platiActive(db: D1Database): Promise<boolean> {
  const row = await one<{ valoare: string }>(db, "SELECT valoare FROM app_config WHERE cheie = 'plati_activ'");
  return row?.valoare === '1';
}

/**
 * Mark a subscription as paid: records the payment and extends the subscription
 * by one month from the later of today or the current expiry. Called by the
 * processor's IPN handler (or a manual confirmation). Returns the new expiry.
 */
export async function confirmaPlataAbonament(
  db: D1Database,
  restaurantId: number,
  suma: number,
  procesator: string,
  refExtern: string | null
): Promise<string> {
  const rest = await one<{ abonament_expira: string | null }>(
    db,
    'SELECT abonament_expira FROM restaurante WHERE id = ?',
    restaurantId
  );
  const today = new Date().toISOString().slice(0, 10);
  const base = rest?.abonament_expira && rest.abonament_expira >= today ? rest.abonament_expira : today;
  const nextExpira = addOneMonth(base);

  await run(
    db,
    `INSERT INTO abonament_plati (restaurant_id, suma, valuta, perioada_start, perioada_end, status, procesator, ref_extern, platit_la)
     VALUES (?, ?, 'RON', ?, ?, 'platita', ?, ?, datetime('now'))`,
    restaurantId, suma, base, nextExpira, procesator, refExtern
  );
  await run(
    db,
    "UPDATE restaurante SET abonament_status = 'activ', abonament_expira = ? WHERE id = ?",
    nextExpira, restaurantId
  );
  return nextExpira;
}

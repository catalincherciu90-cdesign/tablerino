import { Hono } from 'hono';
import type { HonoEnv } from '../types';

// Online subscription payments (processor callbacks). Processor-agnostic shell;
// the Netopia/PayU specifics are filled in once merchant credentials exist.
const plati = new Hono<HonoEnv>();

// Netopia IPN — server-to-server payment confirmation (placeholder).
// When wired: verify the signature, parse the result, and on a successful
// payment call confirmaPlataAbonament(c.env.DB, restaurantId, suma, 'netopia', ref).
plati.post('/netopia/ipn', async (c) => {
  return c.json({ ok: false, msg: 'Netopia nu este configurat încă.' }, 503);
});

// PayU IDN/IPN (placeholder, same idea).
plati.post('/payu/ipn', async (c) => {
  return c.json({ ok: false, msg: 'PayU nu este configurat încă.' }, 503);
});

export default plati;

import { Hono } from 'hono';
import type { HonoEnv } from '../types';
import { one, run } from '../db';
import { hashPassword } from '../auth';

// Public restaurant registration — port of api/inregistrare.php.
const register = new Hono<HonoEnv>();

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

register.post('/', async (c) => {
  const body = await c.req.json().catch(() => ({}));
  const nume = (body.nume ?? '').trim();
  const email = (body.email ?? '').trim();
  const tel = (body.telefon ?? '').trim();
  const parola = body.parola ?? '';

  if (!nume || !email || !parola) {
    return c.json({ ok: false, msg: 'Completează toate câmpurile obligatorii.' });
  }
  if (!EMAIL_RE.test(email)) {
    return c.json({ ok: false, msg: 'Adresa de email nu este validă.' });
  }
  if (parola.length < 6) {
    return c.json({ ok: false, msg: 'Parola trebuie să aibă minim 6 caractere.' });
  }

  const existent = await one<{ id: number; activ: number }>(
    c.env.DB,
    'SELECT id, activ FROM restaurante WHERE email = ?',
    email
  );
  if (existent) {
    return c.json({
      ok: false,
      msg: existent.activ
        ? 'Există deja un cont activ cu această adresă de email.'
        : 'Cererea ta este deja în așteptare. Te vom contacta în curând.',
    });
  }

  const hash = await hashPassword(parola);
  await run(
    c.env.DB,
    "INSERT INTO restaurante (nume, email, telefon, parola, activ, tema, limba) VALUES (?, ?, ?, ?, 0, 'italian', 'ro')",
    nume,
    email,
    tel || null,
    hash
  );
  return c.json({ ok: true });
});

export default register;

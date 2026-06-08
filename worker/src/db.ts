/** Thin helpers over D1 prepared statements. */

/** Fetch a single row (or null). */
export async function one<T = Record<string, unknown>>(
  db: D1Database,
  sql: string,
  ...params: unknown[]
): Promise<T | null> {
  const stmt = db.prepare(sql).bind(...params);
  return (await stmt.first<T>()) ?? null;
}

/** Fetch all rows. */
export async function all<T = Record<string, unknown>>(
  db: D1Database,
  sql: string,
  ...params: unknown[]
): Promise<T[]> {
  const stmt = db.prepare(sql).bind(...params);
  const res = await stmt.all<T>();
  return res.results ?? [];
}

/** Run a write statement; returns D1 meta (last_row_id, changes). */
export async function run(
  db: D1Database,
  sql: string,
  ...params: unknown[]
): Promise<D1Result> {
  return db.prepare(sql).bind(...params).run();
}

/** Run several statements atomically via D1 batch. */
export async function batch(
  db: D1Database,
  statements: { sql: string; params?: unknown[] }[]
): Promise<D1Result[]> {
  const prepared = statements.map((s) =>
    db.prepare(s.sql).bind(...(s.params ?? []))
  );
  return db.batch(prepared);
}

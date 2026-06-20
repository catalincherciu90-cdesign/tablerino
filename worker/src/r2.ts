/** R2-backed image storage, replacing the PHP uploads/ filesystem. */

const ALLOWED: Record<string, string> = {
  jpg: 'image/jpeg',
  jpeg: 'image/jpeg',
  png: 'image/png',
  webp: 'image/webp',
  gif: 'image/gif',
  svg: 'image/svg+xml',
};

export function extToContentType(ext: string): string | null {
  return ALLOWED[ext.toLowerCase()] ?? null;
}

export function fileExt(filename: string): string {
  const i = filename.lastIndexOf('.');
  return i >= 0 ? filename.slice(i + 1).toLowerCase() : '';
}

/**
 * Store an uploaded file in R2 under uploads/<sub>/<name> and return the public
 * path (e.g. "uploads/logo/logo_1_1700000000.png"), matching the relative
 * paths the original app stored in the DB and rendered in <img src>.
 */
export async function putUpload(
  bucket: R2Bucket,
  sub: string,
  name: string,
  body: ArrayBuffer | ReadableStream,
  contentType: string
): Promise<string> {
  const key = `uploads/${sub}/${name}`;
  await bucket.put(key, body, { httpMetadata: { contentType } });
  return key;
}

/** Delete an upload by its stored path (no-op if missing). */
export async function deleteUpload(bucket: R2Bucket, path: string | null | undefined): Promise<void> {
  if (!path) return;
  const key = path.replace(/^\/+/, '');
  await bucket.delete(key);
}

/** Serve an upload from R2 by request path ("uploads/..."). Returns null if absent. */
export async function serveUpload(bucket: R2Bucket, path: string): Promise<Response | null> {
  const key = path.replace(/^\/+/, '');
  const obj = await bucket.get(key);
  if (!obj) return null;
  const headers = new Headers();
  obj.writeHttpMetadata(headers);
  headers.set('etag', obj.httpEtag);
  headers.set('cache-control', 'public, max-age=31536000, immutable');
  return new Response(obj.body, { headers });
}

#!/usr/bin/env bash
# Upload the original PHP app's uploaded images (product photos, ad images,
# logos, backgrounds) into the R2 bucket, preserving their uploads/... paths.
#
# Usage (run from the repo root, where the old `uploads/` dir lives):
#   bash worker/scripts/upload-images-to-r2.sh ../uploads tablerino-uploads
#
# Args: <local-uploads-dir> <r2-bucket-name>
set -euo pipefail

SRC="${1:-../uploads}"
BUCKET="${2:-tablerino-uploads}"

if [ ! -d "$SRC" ]; then
  echo "Source dir '$SRC' not found." >&2
  exit 1
fi

find "$SRC" -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.webp' -o -iname '*.gif' -o -iname '*.svg' \) | while read -r f; do
  # Key mirrors the path under uploads/, e.g. uploads/produse/prod_1_2_123.jpg
  rel="uploads/${f#"$SRC"/}"
  echo "-> $rel"
  npx wrangler r2 object put "$BUCKET/$rel" --file="$f"
done

echo "Done."

#!/usr/bin/env bash
# Chép phần dùng chung của plugin WebP từ bản hoangditru sang bản dùng cho site khác.
#
#   bash scripts/webp-sync.sh
#
# Hai bản (wp-content/plugins/hoangditru-webp và plugins-khac/webp-srcset-lightbox)
# phải giống hệt nhau ở các file dưới đây — scripts/webp.test.php kiểm điều đó.
# Sửa ở bản hoangditru rồi chạy script này.
set -euo pipefail
cd "$(dirname "$0")/.."

SRC=wp-content/plugins/hoangditru-webp
DST=plugins-khac/webp-srcset-lightbox

for f in $(cat scripts/webp-shared.txt | grep -vE '^\s*(#|$)'); do
	cp "$SRC/$f" "$DST/$f"
	echo "  $f"
done

#!/usr/bin/env bash
#
# Chạy MỘT LẦN trên Hostinger, sau khi hPanel Git đã kéo nhánh deploy vào
# <public_html>/wp-content/app
#
# Tạo symlink từ wp-content/themes|plugins tới thư mục trong app, để WordPress
# thấy chúng như theme/plugin bình thường (kích hoạt được, plugin_dir_url đúng).
# Symlink nằm NGOÀI app nên không bị lần deploy sau xoá.
#
#   bash hostinger-link.sh ~/domains/vidu.com/public_html
#
set -euo pipefail

SITE="${1:-}"
if [ -z "$SITE" ]; then
  echo "Dùng: bash hostinger-link.sh <đường-dẫn-public_html>" >&2
  exit 1
fi

cd "$SITE"
[ -f wp-load.php ] || { echo "Không phải thư mục WordPress: $SITE" >&2; exit 1; }
[ -d wp-content/app ] || { echo "Chưa có wp-content/app — kiểm tra lại hPanel Git." >&2; exit 1; }

link_all() {
  local kind="$1"   # themes | plugins
  [ -d "wp-content/app/$kind" ] || return 0
  for src in wp-content/app/"$kind"/*/; do
    [ -d "$src" ] || continue
    local name dest
    name=$(basename "$src")
    dest="wp-content/$kind/$name"

    if [ -e "$dest" ] && [ ! -L "$dest" ]; then
      echo "BỎ QUA $dest — đang là thư mục/file THẬT, không ghi đè." >&2
      echo "         Đổi tên nó rồi chạy lại nếu muốn dùng bản trong app." >&2
      continue
    fi

    ln -sfn "../app/$kind/$name" "$dest"
    if [ -e "$dest" ]; then
      echo "✓ $dest -> ../app/$kind/$name"
    else
      echo "✗ $dest — symlink hỏng" >&2
    fi
  done
}

link_all themes
link_all plugins

echo
echo "=== wp-content/themes ==="
ls -l wp-content/themes
echo "=== wp-content/plugins (chỉ symlink) ==="
ls -l wp-content/plugins | grep '^l' || echo "(không có symlink nào)"
echo
echo "Tiếp theo: wp theme activate flatsome-child && wp plugin activate --all"

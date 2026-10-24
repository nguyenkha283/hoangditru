#!/usr/bin/env bash
#
# Kiểm tra site sau khi deploy. Chạy từ máy bạn (Git Bash), không cần SSH.
#
#   bash check-site.sh https://domain-moi.com
#
set -uo pipefail

BASE="${1:-}"
if [ -z "$BASE" ]; then
  echo "Dùng: bash check-site.sh https://domain-cua-ban" >&2
  exit 1
fi
BASE="${BASE%/}"

UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
fail=0

get() { curl -sL --max-time 30 -A "$UA" "$1"; }

check_page() {
  local label="$1" path="$2" body code errs raw
  code=$(curl -sL --max-time 30 -A "$UA" -o /dev/null -w '%{http_code}' "$BASE$path")
  body=$(get "$BASE$path")

  printf '%-26s HTTP %s' "$label" "$code"

  errs=$(printf '%s' "$body" | grep -ciE "fatal error|parse error|warning: |notice: ") || errs=0
  if [ "$errs" != "0" ]; then printf '  [X] %s loi PHP' "$errs"; fail=1; fi

  raw=$(printf '%s' "$body" | grep -coE "\[(ux_text|ux_image|section|row|col|ux_banner)[ ]") || raw=0
  if [ "$raw" != "0" ]; then printf '  [X] %s shortcode tho' "$raw"; fail=1; fi

  if [ "$code" != "200" ] && [ "$code" != "404" ]; then fail=1; printf '  [X]'; fi
  echo
}

check_asset() {
  local label="$1" path="$2" code
  code=$(curl -sL --max-time 20 -A "$UA" -o /dev/null -w '%{http_code}' "$BASE$path")
  printf '%-26s HTTP %s' "$label" "$code"
  if [ "$code" != "200" ]; then printf '  [X]'; fail=1; fi
  echo
}

# Asset cua plugin loi co the nam o hai cho tuy cach deploy:
#   FTP        wp-content/plugins/hoangditru-core/...
#   hPanel Git wp-content/app/plugins/hoangditru-core/...
# Thu ca hai, dat o dau cung duoc, khong co o dau moi la loi. Nho vay script
# chay duoc truoc va sau khi chuyen sang Git ma khong phai sua tay.
check_core_asset() {
  local label="$1" rel="$2" c1 c2
  c1=$(curl -sL --max-time 20 -A "$UA" -o /dev/null -w '%{http_code}' "$BASE/wp-content/plugins/hoangditru-core/$rel")
  if [ "$c1" = "200" ]; then
    printf '%-26s HTTP 200  (wp-content/plugins)
' "$label"
    return
  fi
  c2=$(curl -sL --max-time 20 -A "$UA" -o /dev/null -w '%{http_code}' "$BASE/wp-content/app/plugins/hoangditru-core/$rel")
  if [ "$c2" = "200" ]; then
    printf '%-26s HTTP 200  (app/plugins, deploy qua Git)
' "$label"
    return
  fi
  printf '%-26s HTTP %s / %s  [X] khong thay o ca hai cho
' "$label" "$c1" "$c2"
  fail=1
}

echo "=== Trang ==="
check_page "Trang chu"   "/"
check_page "404"         "/duong-dan-khong-ton-tai-$RANDOM/"
check_page "Tim kiem"    "/?s=dinh+cu"

echo
echo "=== Theme va plugin da len chua ==="
check_asset "style.css theme" "/wp-content/themes/hoangditru/style.css"
check_core_asset "nav-global.css" "modules/trang-chu/assets/nav-global.css"
check_core_asset "cim-footer.css" "modules/cim-footer/assets/cim-footer.css"
check_core_asset "bds style.css"  "modules/bat-dong-san/assets/css/style.css"

echo
echo "=== Nav va footer co render ==="
home=$(get "$BASE/")

n=$(printf '%s' "$home" | grep -c "tc-nav__list") || n=0
printf '%-26s %s lan' "thanh nav" "$n"
if [ "$n" = "0" ]; then printf '  [X] khong thay'; fail=1; fi
echo

n=$(printf '%s' "$home" | grep -c 'id="cim-footer"') || n=0
printf '%-26s %s lan' "footer" "$n"
if [ "$n" = "0" ]; then printf '  [X] khong thay'; fail=1; fi
echo

echo
if [ "$fail" = "0" ]; then
  echo "OK - khong phat hien van de."
else
  echo "Co muc danh dau [X] o tren - gui lai ket qua nay."
fi
exit "$fail"

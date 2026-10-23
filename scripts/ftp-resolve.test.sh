#!/usr/bin/env bash
#
# Test cho ftp-resolve.sh. Dựng cây thư mục giả rồi kiểm từng tình huống.
# Không chạm tới FTP thật.
#
#   bash scripts/ftp-resolve.test.sh
#
set -u

HERE=$(cd "$(dirname "$0")" && pwd)
SUT="$HERE/ftp-resolve.sh"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

pass=0
fail=0

# A: gốc FTP là home directory — kiểu mặc định của Hostinger
mkdir -p "$TMP/A/public_html/wp-content/themes"  "$TMP/A/public_html/wp-content/plugins" \
         "$TMP/A/public_html/wp-admin"           "$TMP/A/public_html/wp-includes" \
         "$TMP/A/domains"

# B: gốc FTP đã là docroot
mkdir -p "$TMP/B/wp-content/themes" "$TMP/B/wp-content/plugins" \
         "$TMP/B/wp-admin"          "$TMP/B/wp-includes"

# C: không có WordPress ở đâu cả
mkdir -p "$TMP/C/public_html" "$TMP/C/domains"

# D: hai site cùng khớp — phải từ chối đoán
mkdir -p "$TMP/D/public_html/wp-content/themes"  "$TMP/D/public_html/wp-content/plugins" \
         "$TMP/D/public_html/wp-includes" \
         "$TMP/D/domains/site2.com/public_html/wp-content/themes" \
         "$TMP/D/domains/site2.com/public_html/wp-content/plugins" \
         "$TMP/D/domains/site2.com/public_html/wp-includes"

# E: có themes/ và plugins/ nhưng KHÔNG có wp-includes bên cạnh — cái bẫy
mkdir -p "$TMP/E/public_html/wp-content/themes" "$TMP/E/public_html/wp-content/plugins" \
         "$TMP/E/domains"

# F: FTP_PATH người dùng đặt sẵn, đúng — phải dùng luôn, không dò
mkdir -p "$TMP/F/domains/x.com/public_html/wp-content/themes" \
         "$TMP/F/domains/x.com/public_html/wp-content/plugins" \
         "$TMP/F/domains/x.com/public_html/wp-includes"

# G: FTP_PATH đặt sai — phải bỏ qua rồi tự dò ra cái đúng
mkdir -p "$TMP/G/public_html/wp-content/themes" "$TMP/G/public_html/wp-content/plugins" \
         "$TMP/G/public_html/wp-includes" "$TMP/G/domains"

check() {
  local name="$1" tree="$2" want_rc="$3" want_path="$4" ftp_path="${5:-}"
  local out rc

  out=$(FTP_FAKE_TREE="$TMP/$tree" FTP_HOST=h FTP_USER=u FTP_PASSWORD=p FTP_PATH="$ftp_path" \
        bash -e -o pipefail "$SUT" 2>/dev/null)
  rc=$?

  if [ "$rc" = "$want_rc" ] && [ "$out" = "$want_path" ]; then
    printf '  ok    %s\n' "$name"
    pass=$((pass + 1))
  else
    printf '  LỖI   %s\n        mong đợi rc=%s path=%s\n        nhận   rc=%s path=%s\n' \
      "$name" "$want_rc" "${want_path:-(rỗng)}" "$rc" "${out:-(rỗng)}"
    fail=$((fail + 1))
  fi
}

echo "ftp-resolve.sh"
check "gốc FTP là home"              A 0 "/public_html/wp-content/"
check "gốc FTP là docroot"           B 0 "/wp-content/"
check "không có WordPress"           C 1 ""
check "hai site khớp, từ chối đoán"  D 1 ""
check "thiếu wp-includes bên cạnh"   E 1 ""
check "FTP_PATH đặt sẵn và đúng"     F 0 "/domains/x.com/public_html/wp-content/" \
                                         "/domains/x.com/public_html/wp-content/"
check "FTP_PATH sai, tự dò lại"      G 0 "/public_html/wp-content/" "/sai/duong/dan/"

echo
echo "$pass ok, $fail lỗi"
[ "$fail" -eq 0 ]

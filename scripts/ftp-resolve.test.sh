#!/usr/bin/env bash
#
# Test cho ftp-resolve.sh. Dựng cây thư mục giả rồi kiểm từng tình huống.
# Không chạm tới FTP thật.
#
#   bash scripts/ftp-resolve.test.sh
#
# Ngoài kết quả, test còn đếm SỐ LẦN LIỆT KÊ THƯ MỤC — mỗi lần là một phiên
# đăng nhập FTP. Hostinger chặn tạm thời khi có quá nhiều phiên trong thời gian
# ngắn (đã xảy ra 2026-10-23), nên số này là một ràng buộc thật, không phải
# chuyện tối ưu cho vui.
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

# D: WordPress nằm trong domains/, không có ở public_html
mkdir -p "$TMP/D/public_html" \
         "$TMP/D/domains/site2.com/public_html/wp-content/themes" \
         "$TMP/D/domains/site2.com/public_html/wp-content/plugins" \
         "$TMP/D/domains/site2.com/public_html/wp-includes"

# E: hai site trong domains/ cùng khớp — phải từ chối đoán
mkdir -p "$TMP/E/public_html" \
         "$TMP/E/domains/a.com/public_html/wp-content/themes" \
         "$TMP/E/domains/a.com/public_html/wp-content/plugins" \
         "$TMP/E/domains/b.com/public_html/wp-content/themes" \
         "$TMP/E/domains/b.com/public_html/wp-content/plugins"

# F: có themes/ và plugins/ nhưng KHÔNG có wp-includes bên cạnh — cái bẫy
mkdir -p "$TMP/F/public_html/wp-content/themes" "$TMP/F/public_html/wp-content/plugins" \
         "$TMP/F/domains"

calls() {                     # calls <tree> <ftp_path> -> số lần ftp_list
  env FTP_FAKE_TREE="$TMP/$1" FTP_HOST=h FTP_USER=u FTP_PASSWORD=p FTP_PATH="$2" \
    bash -x -o pipefail "$SUT" 2>&1 >/dev/null | grep -cE '^\++ ftp_list '
}

check() {                     # check <tên> <tree> <rc> <path> [ftp_path]
  local name="$1" tree="$2" want_rc="$3" want_path="$4" ftp_path="${5:-}" out RC
  # Gọi thẳng ở đây, không bọc qua hàm: $? sau command substitution là mã thoát
  # của lệnh được thay thế, còn biến đặt trong subshell thì không ra ngoài được.
  out=$(env FTP_FAKE_TREE="$TMP/$tree" FTP_HOST=h FTP_USER=u FTP_PASSWORD=p FTP_PATH="$ftp_path" \
        bash -e -o pipefail "$SUT" 2>/dev/null)
  RC=$?
  if [ "$RC" = "$want_rc" ] && [ "$out" = "$want_path" ]; then
    printf '  ok    %s\n' "$name"; pass=$((pass + 1))
  else
    printf '  LỖI   %s\n        mong đợi rc=%s path=%s\n        nhận   rc=%s path=%s\n' \
      "$name" "$want_rc" "${want_path:-(rỗng)}" "$RC" "${out:-(rỗng)}"
    fail=$((fail + 1))
  fi
}

check_calls() {               # check_calls <tên> <tree> <tối-đa> [ftp_path]
  local name="$1" tree="$2" max="$3" ftp_path="${4:-}" n
  n=$(calls "$tree" "$ftp_path")
  if [ "$n" -le "$max" ]; then
    printf '  ok    %-44s %s phiên (tối đa %s)\n' "$name" "$n" "$max"; pass=$((pass + 1))
  else
    printf '  LỖI   %-44s %s phiên, QUÁ tối đa %s\n' "$name" "$n" "$max"; fail=$((fail + 1))
  fi
}

echo "ftp-resolve.sh — kết quả"
check "gốc FTP là home"              A 0 "/public_html/wp-content/"
check "gốc FTP là docroot"           B 0 "/wp-content/"
check "không có WordPress"           C 1 ""
check "WordPress trong domains/"     D 0 "/domains/site2.com/public_html/wp-content/"
check "hai site khớp, từ chối đoán"  E 1 ""
check "thiếu wp-includes bên cạnh"   F 1 ""
check "FTP_PATH đặt sẵn và đúng"     A 0 "/public_html/wp-content/" "/public_html/wp-content/"
check "FTP_PATH sai, tự dò lại"      A 0 "/public_html/wp-content/" "/sai/duong/dan/"

echo
echo "ftp-resolve.sh — số phiên FTP"
check_calls "có FTP_PATH đúng"        A 1 "/public_html/wp-content/"
check_calls "tự dò, WP ở public_html" A 2
check_calls "tự dò, WP ở gốc"         B 3

echo
echo "$pass ok, $fail lỗi"
[ "$fail" -eq 0 ]

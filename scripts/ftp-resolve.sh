#!/usr/bin/env bash
#
# Dò xem wp-content của site nằm ở đâu trên FTP, rồi in đường dẫn đó ra stdout.
# Dùng bởi .github/workflows/deploy.yml, tách ra file riêng để thử được ở máy.
#
# Biến môi trường: FTP_HOST, FTP_USER, FTP_PASSWORD, FTP_PATH (tuỳ chọn).
#
# Để thử logic mà không cần FTP thật, đặt FTP_FAKE_TREE trỏ tới một thư mục;
# khi đó list_dir đọc thư mục thật trên máy thay vì gọi curl.
#
# Thoát 0 và in đường dẫn nếu tìm được; thoát 1 và in chẩn đoán nếu không.

set -u
set -o pipefail
set +e   # GitHub chạy step bằng `bash -e`; phần dò bên dưới cố tình gọi lệnh có thể hỏng

TLS_OPT=""

note()  { echo "$@" >&2; }
warn()  { echo "::warning::$*" >&2; }
fail()  { echo "::error::$*" >&2; }

# ── Liệt kê một thư mục, mỗi dòng một tên ────────────────────────────────────
list_dir() {
  if [ -n "${FTP_FAKE_TREE:-}" ]; then
    ls -1 "${FTP_FAKE_TREE%/}$1" 2>/dev/null
    return 0
  fi
  curl -sS $TLS_OPT --ssl-reqd --max-time 45 \
       --user "$FTP_USER:$FTP_PASSWORD" \
       "ftp://${FTP_HOST}$1" 2>&1 | awk 'NF {print $NF}'
}

# ── Chứng chỉ FTPS của máy chủ này không khớp được với tên nào ta có ─────────
#
# Đã kiểm ngày 2026-10-23 trên 145.223.108.128:21:
#   - chứng chỉ là CN=*.hstgr.io, SAN chỉ có *.hstgr.io và hstgr.io
#   - hPanel chỉ công bố địa chỉ IP, không công bố hostname nào
#   - ftp.hoangditru.com và hoangditru.com trỏ đúng IP nhưng vẫn mismatch (62)
#   - srv1760.hstgr.io trỏ sang 153.92.15.39, không phải máy chủ FTP
#   - banner FTP là "220 FTP Server ready.", không lộ tên máy
#
# Nên không có cách nào để TLS xác thực đúng. Kết nối vẫn được MÃ HOÁ (FTPS),
# chỉ là không chứng thực được danh tính máy chủ. Giảm thiệt hại bằng cách dùng
# một tài khoản FTP riêng chỉ có quyền vào thư mục của site, thay vì tài khoản
# FTP chính của gói hosting.
detect_tls() {
  [ -n "${FTP_FAKE_TREE:-}" ] && return 0

  local err
  err=$(curl -sS --ssl-reqd --max-time 30 \
          --user "$FTP_USER:$FTP_PASSWORD" \
          "ftp://${FTP_HOST}/" 2>&1 >/dev/null)

  if printf '%s' "$err" | grep -qiE "certificate|ssl"; then
    TLS_OPT="--insecure"
    note "Chứng chỉ FTPS không xác thực được (đã biết trước, xem ghi chú trong script). Kết nối vẫn mã hoá, chạy tiếp."
  fi
}

# ── Chuẩn hoá: luôn có / ở đầu và cuối ───────────────────────────────────────
norm() {
  local p="$1"
  case "$p" in /*) ;; *) p="/$p" ;; esac
  case "$p" in */) ;; *) p="$p/" ;; esac
  printf '%s' "$p"
}

# ── Một đường dẫn chỉ được coi là wp-content khi có đủ themes, plugins, và ───
#    wp-includes nằm cạnh nó (tức thư mục cha đúng là gốc WordPress)
is_wp_content() {
  local p names parent
  p=$(norm "$1")
  parent="${p%wp-content/}"
  [ "$parent" = "$p" ] && return 1

  names=$(list_dir "$p")
  printf '%s\n' "$names" | grep -qx themes  || return 1
  printf '%s\n' "$names" | grep -qx plugins || return 1
  list_dir "$parent" | grep -qx wp-includes || return 1
  return 0
}

diagnose() {
  note ""
  note "─── Chẩn đoán: cây thư mục trên server ───"
  local probe
  for probe in / /public_html/ /domains/; do
    note "  [$probe]"
    list_dir "$probe" | sed 's/^/      /' | head -25 >&2
    note ""
  done
  note "Thư mục nào chứa cả 'themes' và 'plugins', và có 'wp-includes' bên cạnh,"
  note "thì đó là wp-content. Đặt secret FTP_PATH bằng đường dẫn tới nó."
}

detect_tls

# ── 1. FTP_PATH do người dùng đặt: ưu tiên tuyệt đối ─────────────────────────
if [ -n "${FTP_PATH:-}" ]; then
  candidate=$(norm "$FTP_PATH")
  note "FTP_PATH được đặt sẵn, đang kiểm tra: $candidate"
  if is_wp_content "$candidate"; then
    note "OK — dùng $candidate"
    printf '%s\n' "$candidate"
    exit 0
  fi
  warn "FTP_PATH đang đặt không phải wp-content hợp lệ. Thử tự dò."
fi

# ── 2. Tự dò các vị trí thường gặp trên Hostinger ────────────────────────────
candidates="/public_html/wp-content/ /wp-content/"

for d in $(list_dir /domains/ | head -10); do
  case "$d" in
    *.*) candidates="$candidates /domains/$d/public_html/wp-content/" ;;
  esac
done

found=""
for c in $candidates; do
  note "thử: $c"
  if is_wp_content "$c"; then
    note "  ✓ khớp"
    found="$found $c"
  fi
done

set -- $found

case $# in
  0)
    diagnose
    fail "Không tìm thấy wp-content nào qua FTP. Xem phần chẩn đoán ở trên rồi đặt secret FTP_PATH."
    exit 1
    ;;
  1)
    note "Tự dò ra: $1"
    [ -z "${FTP_PATH:-}" ] && warn "Nên đặt secret FTP_PATH = $1 để khỏi phải dò mỗi lần."
    printf '%s\n' "$1"
    exit 0
    ;;
  *)
    note "Có $# thư mục wp-content khớp:"
    for c in "$@"; do note "    $c"; done
    fail "Nhiều hơn một wp-content khớp. Đặt secret FTP_PATH để chỉ rõ cái nào, tránh deploy nhầm site."
    exit 1
    ;;
esac

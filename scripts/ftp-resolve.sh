#!/usr/bin/env bash
#
# Dò xem wp-content của site nằm ở đâu trên FTP, in đường dẫn đó ra stdout.
# Dùng bởi .github/workflows/deploy.yml, tách ra file riêng để thử được ở máy.
#
# Biến môi trường: FTP_HOST, FTP_USER, FTP_PASSWORD, FTP_PATH (tuỳ chọn).
#
# Thử logic mà không cần FTP thật: đặt FTP_FAKE_TREE trỏ tới một thư mục, khi đó
# ftp_list đọc thư mục thật trên máy thay vì gọi curl. Xem ftp-resolve.test.sh.
#
# Thoát 0 và in đường dẫn nếu tìm được; thoát 1 và in chẩn đoán nếu không.
#
# ── TIẾT KIỆM PHIÊN FTP ──────────────────────────────────────────────────────
# Mỗi lần liệt kê thư mục là một lần đăng nhập FTP mới (curl không giữ phiên
# giữa các lần gọi). Bản đầu tiên của script này tốn 3–9 phiên mỗi lần chạy, và
# ngày 2026-10-23 bốn lần deploy trong 17 phút đã làm máy chủ Hostinger chặn
# tạm thời: hai run đỏ liên tiếp ở hai bước FTP khác nhau, rồi tự khỏi sau 46
# phút nghỉ. Vì vậy bản này:
#   - có FTP_PATH đúng  -> đúng 1 phiên
#   - tự dò             -> 1–2 phiên cho hai vị trí thường gặp, chỉ quét
#                          /domains/ khi cả hai trượt
#   - bỏ hẳn phiên "thăm dò TLS" riêng, gộp vào lần liệt kê đầu tiên
#   - tự thử lại khi gặp lỗi kết nối tạm thời

set -u
set -o pipefail
# GitHub chạy step bằng `bash -e`. Script cố tình gọi những lệnh có thể hỏng,
# nên phải tắt -e, nếu không nó chết ngay ở lần dò đầu tiên.
set +e

TLS_OPT=""          # thành "--insecure" nếu chứng chỉ không khớp (xem ghi chú dưới)
TLS_DECIDED=0
RETRY_MAX=3
RETRY_WAIT=15       # giây, nhân đôi sau mỗi lần

note() { echo "$@" >&2; }
fail() { echo "::error::$*" >&2; }

# ── Chứng chỉ FTPS của máy chủ này không khớp được với tên nào ta có ─────────
#
# Đã kiểm 2026-10-23 trên 145.223.108.128:21:
#   - chứng chỉ là CN=*.hstgr.io, SAN chỉ có *.hstgr.io và hstgr.io
#   - hPanel chỉ công bố địa chỉ IP, không công bố hostname nào
#   - ftp.hoangditru.com và hoangditru.com trỏ đúng IP nhưng vẫn mismatch (62)
#   - srv1760.hstgr.io trỏ sang 153.92.15.39, không phải máy chủ FTP
#   - banner FTP là "220 FTP Server ready.", không lộ tên máy
#
# Nên không có cách nào để TLS xác thực đúng. Kết nối vẫn được MÃ HOÁ, chỉ là
# không chứng thực được danh tính máy chủ.

# ftp_list <đường-dẫn>
# In tên các mục trong thư mục, mỗi dòng một tên. Thoát khác 0 nếu không lấy được.
ftp_list() {
  local path="$1" attempt=1 wait="$RETRY_WAIT" raw rc

  if [ -n "${FTP_FAKE_TREE:-}" ]; then
    ls -1 "${FTP_FAKE_TREE%/}$path" 2>/dev/null
    return $?
  fi

  while :; do
    raw=$(curl -sS $TLS_OPT --ssl-reqd --max-time 45 \
            --user "$FTP_USER:$FTP_PASSWORD" \
            "ftp://${FTP_HOST}${path}" 2>&1)
    rc=$?

    if [ "$rc" -eq 0 ]; then
      printf '%s\n' "$raw" | awk 'NF {print $NF}'
      TLS_DECIDED=1
      return 0
    fi

    # Chứng chỉ không khớp: hạ xuống --insecure và thử lại NGAY, không tính là
    # một lần retry vì đây là tình trạng đã biết, không phải lỗi tạm thời.
    if [ "$TLS_DECIDED" -eq 0 ] && [ -z "$TLS_OPT" ] \
       && printf '%s' "$raw" | grep -qiE "certificate|ssl"; then
      TLS_OPT="--insecure"
      note "  (chứng chỉ FTPS không xác thực được — đã biết trước, chạy tiếp ở chế độ không kiểm chứng chỉ)"
      continue
    fi
    TLS_DECIDED=1

    # Lỗi tạm thời: hết thời gian chờ, không kết nối được, bị từ chối vì quá
    # nhiều phiên. Nghỉ rồi thử lại.
    if [ "$attempt" -lt "$RETRY_MAX" ] \
       && printf '%s\|%s' "$rc" "$raw" | grep -qiE "^(7|9|28|35|56)\||timed out|too many|try again|refused|reset"; then
      note "  (lần $attempt thất bại: ${raw%%$'\n'*} — nghỉ ${wait}s rồi thử lại)"
      sleep "$wait"
      attempt=$((attempt + 1))
      wait=$((wait * 3))
      continue
    fi

    note "  lỗi curl ($rc): ${raw%%$'\n'*}"
    return "$rc"
  done
}

# norm <đường-dẫn> -> luôn có / ở đầu và cuối
norm() {
  local p="$1"
  case "$p" in /*) ;; *) p="/$p" ;; esac
  case "$p" in */) ;; *) p="$p/" ;; esac
  printf '%s' "$p"
}

# has_wp_content <đường-dẫn> — 1 phiên FTP
has_wp_content() {
  local names
  names=$(ftp_list "$1") || return 1
  printf '%s\n' "$names" | grep -qx themes  || return 1
  printf '%s\n' "$names" | grep -qx plugins || return 1
  return 0
}

# looks_like_wp_root <đường-dẫn-cha> — 1 phiên FTP, chỉ dùng khi TỰ DÒ
looks_like_wp_root() {
  ftp_list "$1" | grep -qx wp-includes
}

diagnose() {
  note ""
  note "─── Chẩn đoán: cây thư mục trên server ───"
  local probe
  for probe in / /public_html/; do
    note "  [$probe]"
    ftp_list "$probe" | sed 's/^/      /' | head -25 >&2
    note ""
  done
  note "Thư mục nào chứa cả 'themes' và 'plugins' thì đó là wp-content."
  note "Đặt secret FTP_PATH bằng đường dẫn tới nó để khỏi phải dò."
}

# ── 1. FTP_PATH do người dùng đặt: tin tưởng, chỉ kiểm 1 phiên ───────────────
if [ -n "${FTP_PATH:-}" ]; then
  candidate=$(norm "$FTP_PATH")
  note "FTP_PATH đặt sẵn: $candidate"
  if has_wp_content "$candidate"; then
    note "OK — dùng $candidate (1 phiên FTP)"
    printf '%s\n' "$candidate"
    exit 0
  fi
  note "FTP_PATH không phải wp-content hợp lệ, chuyển sang tự dò."
fi

# ── 2. Hai vị trí thường gặp, dừng ngay ở cái đầu tiên khớp ──────────────────
for candidate in /public_html/wp-content/ /wp-content/; do
  note "thử: $candidate"
  if has_wp_content "$candidate"; then
    parent="${candidate%wp-content/}"
    if looks_like_wp_root "$parent"; then
      note "OK — tự dò ra $candidate"
      note "::warning::Nên đặt secret FTP_PATH = $candidate để mỗi lần chạy chỉ tốn 1 phiên FTP."
      printf '%s\n' "$candidate"
      exit 0
    fi
    note "  có themes/plugins nhưng không thấy wp-includes bên cạnh, bỏ qua"
  fi
done

# ── 3. Hiếm khi tới đây: quét từng domain ────────────────────────────────────
note "Quét /domains/ ..."
found=""
for d in $(ftp_list /domains/ | head -10); do
  case "$d" in
    *.*) ;;
    *) continue ;;
  esac
  candidate="/domains/$d/public_html/wp-content/"
  note "thử: $candidate"
  has_wp_content "$candidate" && found="$found $candidate"
done

set -- $found
case $# in
  0)
    diagnose
    fail "Không tìm thấy wp-content nào qua FTP. Xem chẩn đoán ở trên rồi đặt secret FTP_PATH."
    exit 1
    ;;
  1)
    note "OK — tự dò ra $1"
    note "::warning::Nên đặt secret FTP_PATH = $1 để mỗi lần chạy chỉ tốn 1 phiên FTP."
    printf '%s\n' "$1"
    exit 0
    ;;
  *)
    note "Có $# thư mục wp-content khớp:"
    for c in "$@"; do note "    $c"; done
    fail "Nhiều site cùng khớp. Đặt secret FTP_PATH để chỉ rõ cái nào, tránh deploy nhầm."
    exit 1
    ;;
esac

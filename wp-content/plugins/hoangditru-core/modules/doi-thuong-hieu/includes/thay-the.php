<?php
/**
 * Lõi thay chuỗi của module đổi thương hiệu: Khải Di Trú → Hoàng Di Trú, số
 * điện thoại cũ → số trong Thông tin liên hệ.
 *
 * File này KHÔNG gọi hàm WordPress nào, để scripts/doi-thuong-hieu.test.php
 * nạp thẳng được. Phần đọc/ghi database nằm ở admin.php.
 *
 * ── Ba luật ─────────────────────────────────────────────────────────────────
 *
 * 1. Tên: chỉ đổi âm "Khải" thành "Hoàng", phần "di trú" phía sau giữ nguyên
 *    từng byte. Hoa thường và dấu theo đúng bản gốc:
 *      KHẢI DI TRÚ → HOÀNG DI TRÚ     Khai di tru → Hoang di tru
 *      khaiditru   → hoangditru       KhaiDiTru   → HoangDiTru
 *    Nhận cả chữ có dấu dựng sẵn (NFC) lẫn dấu tổ hợp (NFD) — văn bản dán từ
 *    trình soạn thảo trên macOS hay mang NFD.
 *
 * 2. Tên file trong uploads KHÔNG đổi. Đổi chuỗi trong DB thì file thật trên
 *    đĩa vẫn mang tên cũ, ảnh vỡ. Với URL tới file thì chỉ đổi phần tên miền
 *    (khaiditru.com → hoangditru.com), đường dẫn giữ nguyên.
 *
 * 3. Số điện thoại 0906 225 610 ở mọi cách viết thành số đang cấu hình. Sau
 *    tel:, zalo.me/, wa.me/… thì ghi dạng chỉ có chữ số, vì link không nhận dấu
 *    chấm hay khoảng trắng.
 */

defined( 'ABSPATH' ) || exit;

/** Số cũ, chỉ chữ số. Để so với số mới: trùng thì không có gì để thay. */
const HDTTH_SDT_CU = '0906225610';

/** Phần mở rộng coi là tên file — chuỗi kết thúc bằng các đuôi này không đổi tên. */
const HDTTH_DUOI_FILE = 'jpe?g|png|gif|webp|svg|bmp|ico|avif|heic|tiff?|pdf|mp4|m4v|mov|webm|mp3|wav|ogg|zip|rar|docx?|xlsx?|pptx?|csv|txt';

/**
 * Regex tên thương hiệu.
 *
 * Nhóm k/h/a/i là âm "Khải" — thứ bị thay. Nhóm `sau` là "di trú" kèm dấu phân
 * cách, giữ nguyên. Phân cách nhận khoảng trắng, nbsp, -, _, . hoặc không có gì
 * (khaiditru). Hai đầu không được dính chữ/số/dấu tổ hợp, nên "Khai di trung"
 * và "Khải di trừ" không khớp.
 *
 * @return string
 */
function hdtth_regex_ten() {
	$sep = '(?:[\s\x{00A0}\-_.]|&nbsp;)*';

	return '/(?<![\p{L}\p{N}\p{M}])(?<k>k)(?<h>h)(?<a>a\x{0309}|ả|a)(?<i>i)'
		. '(?<sau>' . $sep . 'd' . $sep . 'i' . $sep . 'tr(?:u\x{0301}|ú|u))'
		. '(?![\p{L}\p{N}\p{M}])/iu';
}

/**
 * Regex số điện thoại cũ: 0906225610, +84 906 225 610, (+84) 906.225.610 …
 *
 * Giữa hai chữ số cho phép tối đa ba ký tự phân cách (khoảng trắng, nbsp, chấm,
 * gạch) để nhận cả "0906 - 225 - 610". Hai đầu không được dính chữ số khác.
 *
 * @return string
 */
function hdtth_regex_sdt() {
	$s     = '[\s.\-\x{00A0}]{0,3}';
	$so    = implode( $s, str_split( '906225610' ) );
	$dau   = '(?:\(\s*\+?\s*84\s*\)|\+\s?84|84)' . $s . '|0';

	return '/(?<![\d+])(?:' . $dau . ')' . $so . '(?!\d)/u';
}

/**
 * Chữ có phải chữ hoa không.
 *
 * @param string $c Một ký tự (có thể kèm dấu tổ hợp).
 * @return bool
 */
function hdtth_la_hoa( $c ) {
	return mb_strtoupper( $c, 'UTF-8' ) === $c && mb_strtolower( $c, 'UTF-8' ) !== $c;
}

/**
 * Dựng "Hoàng" theo hoa thường và kiểu dấu của "Khải" gốc.
 *
 * @param array $m Kết quả khớp của hdtth_regex_ten().
 * @return string
 */
function hdtth_ten_moi( array $m ) {
	$a    = $m['a'];
	$goc  = mb_substr( $a, 0, 1, 'UTF-8' );
	$dau  = mb_strlen( $a, 'UTF-8' ) > 1 ? "a\u{0300}" : ( 'ả' === mb_strtolower( $goc, 'UTF-8' ) ? 'à' : 'a' );
	$tu   = 'ho' . $dau . 'ng';

	if ( hdtth_la_hoa( $m['k'] ) ) {
		$tu = hdtth_la_hoa( $m['h'] ) && hdtth_la_hoa( $goc ) && hdtth_la_hoa( $m['i'] )
			? mb_strtoupper( $tu, 'UTF-8' )
			: 'H' . mb_substr( $tu, 1, null, 'UTF-8' );
	}

	return $tu . $m['sau'];
}

/**
 * Đổi tên thương hiệu trong một đoạn chữ (không xét tên file).
 *
 * @param string $s   Chuỗi.
 * @param int    $dem Số chỗ đã đổi, cộng dồn.
 * @return string
 */
function hdtth_doi_ten( $s, &$dem ) {
	$n   = 0;
	$out = preg_replace_callback( hdtth_regex_ten(), 'hdtth_ten_moi', $s, -1, $n );

	if ( null === $out ) {
		return $s;
	}

	$dem += $n;

	return $out;
}

/**
 * Số mới dạng chỉ chữ số, giữ dấu + ở đầu nếu có.
 *
 * @param string $sdt Số như cấu hình.
 * @return string
 */
function hdtth_chu_so( $sdt ) {
	$sdt = trim( (string) $sdt );

	return ( '+' === substr( $sdt, 0, 1 ) ? '+' : '' ) . preg_replace( '/\D/', '', $sdt );
}

/**
 * Đổi số điện thoại cũ trong một đoạn chữ.
 *
 * @param string $s   Chuỗi.
 * @param string $sdt Số mới, đúng như cấu hình.
 * @param int    $dem Số chỗ đã đổi, cộng dồn.
 * @return string
 */
function hdtth_doi_sdt( $s, $sdt, &$dem ) {
	$so = hdtth_chu_so( $sdt );

	if ( '' === $sdt || '' === trim( $so, '+' ) ) {
		return $s;
	}

	// Dạng quốc tế không dấu + cho wa.me: 0912… → 84912…
	$quoc_te = ltrim( $so, '+' );
	if ( '0' === substr( $quoc_te, 0, 1 ) ) {
		$quoc_te = '84' . substr( $quoc_te, 1 );
	}

	$n   = 0;
	$out = preg_replace_callback(
		hdtth_regex_sdt(),
		static function ( $m ) use ( $s, $sdt, $so, $quoc_te ) {
			$truoc = substr( $s, max( 0, $m[0][1] - 40 ), min( 40, $m[0][1] ) );

			if ( preg_match( '~(?:wa\.me/|whatsapp://send\?phone=|api\.whatsapp\.com/send\?phone=)\s*$~i', $truoc ) ) {
				return $quoc_te;
			}

			if ( preg_match( '~(?:tel:(?://)?|callto:|sms:|zalo\.me/|viber://\w+\?number=|phone=)\s*$~i', $truoc ) ) {
				return $so;
			}

			return $sdt;
		},
		$s,
		-1,
		$n,
		PREG_OFFSET_CAPTURE
	);

	if ( null === $out ) {
		return $s;
	}

	$dem += $n;

	return $out;
}

/**
 * Thay trong một chuỗi thường (không serialize).
 *
 * Tách chuỗi theo "token tên file": token kết thúc bằng đuôi file thì chỉ đổi
 * tên miền ở đầu (nếu có), phần còn lại đổi cả tên lẫn số điện thoại.
 *
 * @param string $s   Chuỗi.
 * @param string $sdt Số mới.
 * @param array  $dem ['ten' => int, 'sdt' => int], cộng dồn.
 * @return string
 */
function hdtth_thay_chuoi( $s, $sdt, array &$dem ) {
	if ( ! is_string( $s ) || '' === $s ) {
		return $s;
	}

	$dem += array( 'ten' => 0, 'sdt' => 0 );

	$file  = '~([^\s"\'<>()\[\]{},;|=*]+\.(?:' . HDTTH_DUOI_FILE . '))(?![\p{L}\p{N}_\-])~iu';
	$phan  = preg_split( $file, $s, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( false === $phan ) {
		return $s;
	}

	foreach ( $phan as $i => $p ) {
		if ( $i % 2 ) {
			// Tên file: chỉ đổi tên miền, kể cả dạng JSON có \/ .
			if ( preg_match( '~^((?:https?:)?(?:\\\\?/){2})([^/\\\\]+)(.*)$~s', $p, $m ) ) {
				$phan[ $i ] = $m[1] . hdtth_doi_ten( $m[2], $dem['ten'] ) . $m[3];
			}
			continue;
		}

		$p          = hdtth_doi_ten( $p, $dem['ten'] );
		$phan[ $i ] = hdtth_doi_sdt( $p, $sdt, $dem['sdt'] );
	}

	return implode( '', $phan );
}

/**
 * Chuỗi có phải dữ liệu PHP serialize không (giống is_serialized() của WP,
 * viết lại để file này không phụ thuộc WordPress).
 *
 * @param string $s Chuỗi.
 * @return bool
 */
function hdtth_la_serialize( $s ) {
	$s = trim( $s );

	if ( 'N;' === $s ) {
		return true;
	}

	return (bool) preg_match( '/^(?:[aOCs]:\d+:|[bid]:[^;]*;$)/', $s )
		&& in_array( substr( $s, -1 ), array( ';', '}' ), true );
}

/**
 * Đi qua mảng đã giải serialize, thay trong mọi chuỗi (kể cả chuỗi serialize
 * lồng bên trong).
 *
 * @param mixed  $v     Giá trị.
 * @param string $sdt   Số mới.
 * @param array  $dem   Bộ đếm.
 * @param string $ly_do Lý do bỏ qua, nếu có.
 * @return mixed
 */
function hdtth_di( $v, $sdt, array &$dem, &$ly_do ) {
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $con ) {
			$v[ $k ] = hdtth_di( $con, $sdt, $dem, $ly_do );
		}
		return $v;
	}

	if ( is_string( $v ) ) {
		$moi = hdtth_thay_gia_tri( $v, $sdt, $dem, $ly_do );
		return null === $moi ? $v : $moi;
	}

	return $v;
}

/**
 * Thay trong một giá trị lấy thẳng từ database.
 *
 * Dữ liệu serialize được giải ra, thay, rồi đóng gói lại — nên số độ dài s:N:
 * luôn đúng. Dữ liệu serialize có object thì BỎ QUA: giải ra không có class
 * tương ứng thì PHP chỉ cho __PHP_Incomplete_Class, không sửa an toàn được.
 *
 * @param string      $raw   Giá trị thô.
 * @param string      $sdt   Số mới.
 * @param array       $dem   Bộ đếm ['ten','sdt'], cộng dồn.
 * @param string|null $ly_do Gán lý do khi bỏ qua.
 * @return string|null Giá trị mới, hoặc null nếu không đổi gì / bỏ qua.
 */
function hdtth_thay_gia_tri( $raw, $sdt, array &$dem, &$ly_do = null ) {
	$dem += array( 'ten' => 0, 'sdt' => 0 );

	if ( ! is_string( $raw ) || '' === $raw ) {
		return null;
	}

	if ( hdtth_la_serialize( $raw ) ) {
		$v = @unserialize( $raw, array( 'allowed_classes' => false ) ); // phpcs:ignore

		if ( false === $v && 'b:0;' !== trim( $raw ) ) {
			// Không giải được — coi như chuỗi thường thì có thể làm hỏng nó.
			$thu = array();
			hdtth_thay_chuoi( $raw, $sdt, $thu );
			if ( $thu['ten'] || $thu['sdt'] ) {
				$ly_do = 'dữ liệu serialize hỏng, không giải ra được';
			}
			return null;
		}

		if ( preg_match( '/(?:^|[;{])[OC]:\d+:"/', $raw ) ) {
			$thu = array();
			hdtth_thay_chuoi( $raw, $sdt, $thu );
			if ( $thu['ten'] || $thu['sdt'] ) {
				$ly_do = 'serialize có object, không sửa an toàn được';
			}
			return null;
		}

		$truoc = $dem;
		$moi   = serialize( hdtth_di( $v, $sdt, $dem, $ly_do ) ); // phpcs:ignore

		return ( $dem === $truoc || $moi === $raw ) ? null : $moi;
	}

	$truoc = $dem;
	$moi   = hdtth_thay_chuoi( $raw, $sdt, $dem );

	return ( $dem === $truoc || $moi === $raw ) ? null : $moi;
}

/**
 * Slug mới cho một slug có tên thương hiệu.
 *
 * Slug tiếng Việt có dấu được WordPress lưu dạng mã hoá %xx chữ thường, nên
 * giải mã trước, đổi, rồi mã hoá lại. Không đổi số điện thoại trong slug.
 *
 * @param string $slug Slug cũ.
 * @return string|null Slug mới, hoặc null nếu không có gì để đổi.
 */
function hdtth_slug_moi( $slug ) {
	$giai = rawurldecode( (string) $slug );
	$n    = 0;
	$moi  = hdtth_doi_ten( $giai, $n );

	if ( ! $n ) {
		return null;
	}

	if ( $giai !== $slug ) {
		$moi = strtolower( preg_replace_callback(
			'/[^\x00-\x7F]+/',
			static function ( $m ) {
				return rawurlencode( $m[0] );
			},
			$moi
		) );
	}

	return $moi;
}

/**
 * Đoạn trích quanh chỗ khác đầu tiên giữa hai chuỗi, để xem trước.
 *
 * @param string $cu  Chuỗi cũ.
 * @param string $moi Chuỗi mới.
 * @return string[] [trước, sau]
 */
function hdtth_trich( $cu, $moi ) {
	$len = min( strlen( $cu ), strlen( $moi ) );
	$i   = 0;

	while ( $i < $len && $cu[ $i ] === $moi[ $i ] ) {
		$i++;
	}

	// Lùi về đầu ký tự UTF-8.
	while ( $i > 0 && ( ord( $cu[ $i ] ?? "\0" ) & 0xC0 ) === 0x80 ) {
		$i--;
	}

	$tu   = max( 0, $i - 60 );
	$cat  = static function ( $s ) use ( $tu ) {
		$doan = mb_strcut( $s, $tu, 180, 'UTF-8' );
		return ( $tu ? '…' : '' ) . $doan . ( strlen( $s ) > $tu + 180 ? '…' : '' );
	};

	return array( $cat( $cu ), $cat( $moi ) );
}

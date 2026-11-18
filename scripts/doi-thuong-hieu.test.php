<?php
/**
 * Test luật thay tên thương hiệu / số điện thoại, và phần làm sạch mô tả ngắn.
 *
 *   php scripts/doi-thuong-hieu.test.php
 *
 * Module doi-thuong-hieu ghi thẳng vào database của site thật, không có hoàn
 * tác. Sai một luật là hoặc vỡ ảnh (đổi tên file), hoặc hỏng dữ liệu serialize
 * (sai số độ dài s:N:), hoặc đổi nhầm chữ không liên quan.
 */

define( 'ABSPATH', __DIR__ );

$GLOBALS['shortcodes'] = array( 'ux_text', 'youtube_embed', 'cim_phone' );
function shortcode_exists( $t ) { return in_array( $t, $GLOBALS['shortcodes'], true ); }
function add_filter() {}

require __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/doi-thuong-hieu/includes/thay-the.php';
require __DIR__ . '/../wp-content/plugins/hoangditru-core/includes/excerpt.php';

$loi = 0;
$ok  = 0;

function kiem( $ten, $thuc, $mong ) {
	global $loi, $ok;
	if ( $thuc === $mong ) {
		$ok++;
		return;
	}
	$loi++;
	printf( "  LỖI  %s\n        được: %s\n        cần:  %s\n", $ten, var_export( $thuc, true ), var_export( $mong, true ) );
}

const SDT = '0912.345.678';

function thay( $s ) {
	$dem = array();
	return hdtth_thay_chuoi( $s, SDT, $dem );
}

// ── Tên ─────────────────────────────────────────────────────────────────────
$ten = array(
	'Khải di trú'                    => 'Hoàng di trú',
	'Khải Di Trú'                    => 'Hoàng Di Trú',
	'KHẢI DI TRÚ'                    => 'HOÀNG DI TRÚ',
	'khải di trú'                    => 'hoàng di trú',
	'Công ty Khải Di Trú.'           => 'Công ty Hoàng Di Trú.',
	"Kha\u{0309}i di tru\u{0301}"    => "Hoa\u{0300}ng di tru\u{0301}",   // NFD
	"KHA\u{0309}I DI TRU\u{0301}"    => "HOA\u{0300}NG DI TRU\u{0301}",
	'Khai di tru'                    => 'Hoang di tru',
	'KHAI DI TRU'                    => 'HOANG DI TRU',
	'khaiditru'                      => 'hoangditru',
	'KhaiDiTru'                      => 'HoangDiTru',
	'KHAIDITRU'                      => 'HOANGDITRU',
	'khai-di-tru'                    => 'hoang-di-tru',
	'khai_di_tru'                    => 'hoang_di_tru',
	'KhảiDiTrú'                      => 'HoàngDiTrú',
	'Khải&nbsp;Di&nbsp;Trú'          => 'Hoàng&nbsp;Di&nbsp;Trú',
	"Khải\u{00A0}Di Trú"             => "Hoàng\u{00A0}Di Trú",
	'Khải  di   trú'                 => 'Hoàng  di   trú',
	'https://khaiditru.com/lien-he/' => 'https://hoangditru.com/lien-he/',
	'info@khaiditru.com'             => 'info@hoangditru.com',
	'<b>Khải Di Trú</b>'             => '<b>Hoàng Di Trú</b>',
	'Khải di trú, Khải di trú'       => 'Hoàng di trú, Hoàng di trú',
	// Không được đổi
	'Khai di trung'                  => 'Khai di trung',
	'Khải di trừ'                    => 'Khải di trừ',
	'khaiditrung'                    => 'khaiditrung',
	'akhaiditru'                     => 'akhaiditru',
	'Khải'                           => 'Khải',
	'Khải Hoàn'                      => 'Khải Hoàn',
	'Hoàng Di Trú'                   => 'Hoàng Di Trú',
);
foreach ( $ten as $vao => $ra ) {
	kiem( "tên: $vao", thay( $vao ), $ra );
}

// ── Tên file: giữ nguyên, chỉ đổi tên miền ──────────────────────────────────
$file = array(
	'<img src="https://khaiditru.com/wp-content/uploads/2026/05/khaiditru-logo.jpg">'
		=> '<img src="https://hoangditru.com/wp-content/uploads/2026/05/khaiditru-logo.jpg">',
	'/wp-content/uploads/2026/05/logo-khai-di-tru.png.webp'
		=> '/wp-content/uploads/2026/05/logo-khai-di-tru.png.webp',
	'2026/05/khaiditru-banner.jpeg'
		=> '2026/05/khaiditru-banner.jpeg',
	'srcset="/u/khaiditru-300x200.jpg 300w, /u/khaiditru-768x512.jpg 768w"'
		=> 'srcset="/u/khaiditru-300x200.jpg 300w, /u/khaiditru-768x512.jpg 768w"',
	'"https:\/\/khaiditru.com\/wp-content\/uploads\/khaiditru.png"'
		=> '"https:\/\/hoangditru.com\/wp-content\/uploads\/khaiditru.png"',
	"background:url('/u/Khai-Di-Tru.webp')"
		=> "background:url('/u/Khai-Di-Tru.webp')",
	'Tải file khaiditru-bang-gia.pdf của Khải Di Trú'
		=> 'Tải file khaiditru-bang-gia.pdf của Hoàng Di Trú',
	// "khaiditru.com" không phải tên file
	'Xem khaiditru.com nhé'
		=> 'Xem hoangditru.com nhé',
);
foreach ( $file as $vao => $ra ) {
	kiem( "file: $vao", thay( $vao ), $ra );
}

// ── Số điện thoại ───────────────────────────────────────────────────────────
$sdt = array(
	'Hotline: 0906.225.610'                 => 'Hotline: 0912.345.678',
	'0906 225 610'                          => '0912.345.678',
	'0906225610'                            => '0912.345.678',
	'0906-225-610'                          => '0912.345.678',
	'0906 - 225 - 610'                      => '0912.345.678',
	'090.622.5610'                          => '0912.345.678',
	'+84 906 225 610'                       => '0912.345.678',
	'+84906225610'                          => '0912.345.678',
	'(+84) 906.225.610'                     => '0912.345.678',
	'84906225610'                           => '0912.345.678',
	"0906\u{00A0}225\u{00A0}610"            => '0912.345.678',
	'<a href="tel:0906225610">0906.225.610</a>' => '<a href="tel:0912345678">0912.345.678</a>',
	'<a href="tel:+84906225610">Gọi</a>'    => '<a href="tel:0912345678">Gọi</a>',
	'tel://0906.225.610'                    => 'tel://0912345678',
	'https://zalo.me/0906225610'            => 'https://zalo.me/0912345678',
	'https://wa.me/84906225610'             => 'https://wa.me/84912345678',
	// Không được đổi
	'10906225610'                           => '10906225610',
	'09062256101'                           => '09062256101',
	'0906 225 611'                          => '0906 225 611',
	'/u/zalo-0906225610.png'                => '/u/zalo-0906225610.png',
);
foreach ( $sdt as $vao => $ra ) {
	kiem( "sđt: $vao", thay( (string) $vao ), (string) $ra );
}

kiem( 'sđt mới có +', hdtth_chu_so( '+84 912 345 678' ), '+84912345678' );

// Bộ đếm
$dem = array();
hdtth_thay_chuoi( 'Khải Di Trú 0906.225.610, khaiditru.com, tel:0906225610', SDT, $dem );
kiem( 'đếm tên', $dem['ten'], 2 );
kiem( 'đếm sđt', $dem['sdt'], 2 );

// ── Serialize ───────────────────────────────────────────────────────────────
$goc = serialize( array( 'ten' => 'Khải Di Trú', 'sdt' => '0906.225.610', 'so' => 5, 'con' => array( 'Khai di tru' ) ) );
$dem = array();
$moi = hdtth_thay_gia_tri( $goc, SDT, $dem );
kiem( 'serialize: giải lại được', @unserialize( $moi ), array( 'ten' => 'Hoàng Di Trú', 'sdt' => '0912.345.678', 'so' => 5, 'con' => array( 'Hoang di tru' ) ) );
kiem( 'serialize: đếm', $dem, array( 'ten' => 2, 'sdt' => 1 ) );

$long = serialize( array( 'x' => serialize( array( 'Khải Di Trú' ) ) ) );
$moi  = hdtth_thay_gia_tri( $long, SDT, $dem );
kiem( 'serialize lồng', unserialize( unserialize( $moi )['x'] ), array( 'Hoàng Di Trú' ) );

$obj   = 'O:8:"stdClass":1:{s:1:"a";s:11:"Khai di tru";}';
$ly_do = null;
$dem   = array();
kiem( 'serialize có object: bỏ qua', hdtth_thay_gia_tri( $obj, SDT, $dem, $ly_do ), null );
kiem( 'serialize có object: có lý do', is_string( $ly_do ), true );

$ly_do = null;
kiem( 'serialize hỏng: bỏ qua', hdtth_thay_gia_tri( 'a:1:{s:99:"Khai di tru";}', SDT, $dem, $ly_do ), null );
kiem( 'serialize hỏng: có lý do', is_string( $ly_do ), true );

kiem( 'không có gì: null', hdtth_thay_gia_tri( 'Hoàng Di Trú', SDT, $dem ), null );
kiem( 'số mới rỗng: chỉ đổi tên', hdtth_thay_chuoi( 'Khải Di Trú 0906225610', '', $dem ), 'Hoàng Di Trú 0906225610' );

// ── Slug ────────────────────────────────────────────────────────────────────
kiem( 'slug thường', hdtth_slug_moi( 'gioi-thieu-khai-di-tru' ), 'gioi-thieu-hoang-di-tru' );
kiem( 'slug liền', hdtth_slug_moi( 'khaiditru-uy-tin' ), 'hoangditru-uy-tin' );
kiem( 'slug mã hoá', hdtth_slug_moi( strtolower( rawurlencode( 'khải' ) ) . '-di-tru' ), strtolower( rawurlencode( 'hoàng' ) ) . '-di-tru' );
kiem( 'slug không liên quan', hdtth_slug_moi( 'golden-visa-bo-dao-nha' ), null );

// ── Trích xem trước ─────────────────────────────────────────────────────────
list( $a, $b ) = hdtth_trich( 'Xin chào Khải Di Trú', 'Xin chào Hoàng Di Trú' );
kiem( 'trích: trước', $a, 'Xin chào Khải Di Trú' );
kiem( 'trích: sau', $b, 'Xin chào Hoàng Di Trú' );

// ── Mô tả ngắn ──────────────────────────────────────────────────────────────
$mo_ta = array(
	'[youtube_embed url="https://youtu.be/abc"] Chương trình Grenada'      => 'Chương trình Grenada',
	'Mở đầu [youtube_embed]https://youtu.be/abc[/youtube_embed] tiếp theo' => 'Mở đầu tiếp theo',
	'[youtube_embed url="a"] giữa [youtube_embed]b[/youtube_embed] cuối'   => 'giữa cuối',
	'[ux_text]Chữ thật[/ux_text]'                                          => 'Chữ thật',
	'[ux_text css="x"]Chữ[/ux_text] và [cim_phone]'                        => 'Chữ và',
	'[la_shortcode a="1"]Nội dung'                                         => 'Nội dung',
	'[Hot] Tin mới'                                                        => '[Hot] Tin mới',
	'Xem https://www.youtube.com/watch?v=abc123 nhé'                       => 'Xem nhé',
	'Video: //youtu.be/xyz'                                                => 'Video:',
	'<a href="https://youtube.com/@kenh">Kênh</a>'                         => '<a href="https://youtube.com/@kenh">Kênh</a>',
	'Mô tả bình thường'                                                    => 'Mô tả bình thường',
	"Đoạn 1\n\nĐoạn 2"                                                     => "Đoạn 1\n\nĐoạn 2",
	'Giá 100 [&hellip;]'                                                   => 'Giá 100 [&hellip;]',
	''                                                                     => '',
);
foreach ( $mo_ta as $vao => $ra ) {
	kiem( "mô tả: $vao", hdtc_clean_excerpt( $vao ), $ra );
}

// Template thẻ bài phải đi qua hàm làm sạch.
foreach ( array( 'programs', 'news' ) as $f ) {
	$src = file_get_contents( __DIR__ . "/../wp-content/plugins/hoangditru-core/modules/citizenship-investment/template-parts/sections/$f.php" );
	kiem( "$f.php gọi hdtc_clean_excerpt()", false !== strpos( $src, 'hdtc_clean_excerpt(' ), true );
}

// ── Quét/ghi database với $wpdb giả ────────────────────────────────────────
// Không có MySQL ở đây, nên chỉ kiểm phần PHP: hàng nào được ghi, ghi gì, và
// những thứ phải tránh (file ảnh, slug cũ, khoá meta bỏ qua) có thật sự tránh.
function add_action() {}
function is_admin() { return true; }
function add_management_page() {}
function esc_sql( $s ) { return addslashes( $s ); }
function get_edit_post_link( $id ) { return "edit:$id"; }
function get_edit_term_link( $id ) { return "term:$id"; }
function wp_unique_post_slug( $slug ) { return $slug; }
function get_post_meta() { return array(); }
function add_post_meta( $id, $k, $v ) { $GLOBALS['ghi'][] = array( 'postmeta+', $id, $k, $v ); }
function add_term_meta( $id, $k, $v ) { $GLOBALS['ghi'][] = array( 'termmeta+', $id, $k, $v ); }
function get_term_by() { return false; }
function clean_post_cache() {}
function clean_term_cache() {}
function wp_cache_delete() {}
function cim_get( $k ) { return 'phone' === $k ? SDT : ''; }

class Wpdb_Gia {
	public $posts = 'wp_posts', $postmeta = 'wp_postmeta', $options = 'wp_options';
	public $terms = 'wp_terms', $term_taxonomy = 'wp_term_taxonomy', $termmeta = 'wp_termmeta';
	public $sql = array();
	public function get_results( $sql ) {
		$this->sql[] = $sql;
		if ( strpos( $sql, 'post_name LIKE' ) ) {
			return array(
				(object) array( 'ID' => 1, 'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => 0, 'post_name' => 've-khai-di-tru' ),
				(object) array( 'ID' => 2, 'post_type' => 'post', 'post_status' => 'draft', 'post_parent' => 0, 'post_name' => 'khaiditru-nhap' ),
			);
		}
		if ( strpos( $sql, 'FROM wp_posts' ) ) {
			return array( (object) array(
				'ID' => 1, 'post_type' => 'page', 'post_status' => 'publish',
				'post_title' => 'Về Khải Di Trú', 'post_content' => '<img src="/u/khaiditru.jpg"> Gọi 0906.225.610', 'post_excerpt' => '',
			) );
		}
		if ( strpos( $sql, 'FROM wp_postmeta' ) ) {
			return array( (object) array( 'meta_id' => 7, 'post_id' => 1, 'meta_key' => 'lpb_hotline', 'meta_value' => serialize( array( '0906 225 610' ) ) ) );
		}
		if ( strpos( $sql, 'FROM wp_options' ) ) {
			return array( (object) array( 'option_id' => 3, 'option_name' => 'blogname', 'option_value' => 'KHẢI DI TRÚ' ) );
		}
		if ( strpos( $sql, 'FROM wp_terms' ) ) {
			return array( (object) array( 'term_id' => 9, 'name' => 'Tin Khải Di Trú', 'slug' => 'tin-khai-di-tru', 'term_taxonomy_id' => 19, 'taxonomy' => 'category', 'description' => '' ) );
		}
		return array();
	}
	public function update( $bang, $data, $where ) { $GLOBALS['ghi'][] = array( $bang, $data, $where ); return 1; }
}
$GLOBALS['wpdb'] = new Wpdb_Gia();
$GLOBALS['ghi']  = array();

require __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/doi-thuong-hieu/includes/admin.php';

$kq = hdtth_quet( SDT, false );
kiem( 'xem trước: không ghi gì', $GLOBALS['ghi'], array() );
kiem( 'xem trước: tổng', $kq['tong'], array( 'ten' => 3, 'sdt' => 2, 'slug' => 3, 'dong' => 8 ) );

$kq = hdtth_quet( SDT, true );
kiem( 'ghi: bài', $GLOBALS['ghi'][0], array( 'wp_posts', array( 'post_title' => 'Về Hoàng Di Trú', 'post_content' => '<img src="/u/khaiditru.jpg"> Gọi 0912.345.678' ), array( 'ID' => 1 ) ) );
kiem( 'ghi: slug', $GLOBALS['ghi'][1], array( 'wp_posts', array( 'post_name' => 've-hoang-di-tru' ), array( 'ID' => 1 ) ) );
kiem( 'ghi: _wp_old_slug cho bài đã xuất bản', $GLOBALS['ghi'][2], array( 'postmeta+', 1, '_wp_old_slug', 've-khai-di-tru' ) );
kiem( 'ghi: bản nháp không có _wp_old_slug', $GLOBALS['ghi'][3], array( 'wp_posts', array( 'post_name' => 'hoangditru-nhap' ), array( 'ID' => 2 ) ) );
kiem( 'ghi: meta serialize', $GLOBALS['ghi'][4], array( 'wp_postmeta', array( 'meta_value' => serialize( array( SDT ) ) ), array( 'meta_id' => 7 ) ) );
kiem( 'ghi: option', $GLOBALS['ghi'][5], array( 'wp_options', array( 'option_value' => 'HOÀNG DI TRÚ' ), array( 'option_id' => 3 ) ) );
kiem( 'ghi: term', $GLOBALS['ghi'][6], array( 'wp_terms', array( 'name' => 'Tin Hoàng Di Trú', 'slug' => 'tin-hoang-di-tru' ), array( 'term_id' => 9 ) ) );
kiem( 'ghi: slug cũ của term', $GLOBALS['ghi'][7], array( 'termmeta+', 9, HDTTH_TERM_OLD_SLUG, 'tin-khai-di-tru' ) );
kiem( 'ghi: hết', count( $GLOBALS['ghi'] ), 8 );

$sql = implode( "\n", $GLOBALS['wpdb']->sql );
kiem( 'SQL: bỏ revision', false !== strpos( $sql, "post_type <> 'revision'" ), true );
kiem( 'SQL: bỏ _wp_attached_file', false !== strpos( $sql, "'_wp_attached_file'" ), true );
kiem( 'SQL: bỏ transient', false !== strpos( $sql, "NOT LIKE '\\_transient\\_%'" ), true );
kiem( 'SQL: bỏ siteurl', false !== strpos( $sql, "'siteurl'" ), true );

kiem( 'sđt rỗng: chặn', '' !== hdtth_loi_sdt( '' ), true );
kiem( 'sđt vẫn là số cũ: chặn', '' !== hdtth_loi_sdt( '+84 906 225 610' ), true );
kiem( 'sđt mới: cho chạy', hdtth_loi_sdt( SDT ), '' );

printf( "\n%d ok, %d lỗi\n", $ok, $loi );
if ( $loi ) {
	exit( 1 );
}
echo "OK — đổi tên thương hiệu và làm sạch mô tả đúng.\n";

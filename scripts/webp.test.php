<?php
/**
 * Test plugin WebP (hai bản) với WordPress giả lập.
 *
 *   php scripts/webp.test.php
 *
 * Kiểm:
 *   - phần dùng chung giống hệt nhau giữa hai bản (scripts/webp-shared.txt);
 *   - chuyển đổi: trạng thái 1/0, bỏ qua khi chạy lại, ghi đè, xoá .webp theo ảnh;
 *   - đổi URL: thêm/bỏ .webp, tra ID theo tên file, srcset, data-full, url();
 *   - bộ lọc HTML: thẻ nhiều dòng, dấu > trong alt, lazy-load data-src, chạy hai
 *     lần cho cùng kết quả;
 *   - bản hoangditru: không đổi URL trong admin, REST, <head>;
 *   - bản dùng cho site khác: không lọc phản hồi không phải HTML;
 *   - LiteSpeed: tuỳ chọn WebP bị ép về 0;
 *   - hdtc_img() của plugin lõi.
 *
 * Giới hạn: hàm ảnh của WordPress (srcset, sizes) là bản giả lập đơn giản. Phần
 * chạy thật với WordPress đã được thử tay trên WordPress 6.5 — xem README.
 */

define( 'ABSPATH', __DIR__ . '/../' );

$root   = dirname( __DIR__ );
$v1     = $root . '/wp-content/plugins/hoangditru-webp';
$v2     = $root . '/plugins-khac/webp-srcset-lightbox';
$errors = 0;
$count  = 0;

function check( $name, $ok, $detail = '' ) {
	global $errors, $count;
	$count++;
	if ( $ok ) {
		printf( "  ok    %s\n", $name );
	} else {
		$errors++;
		printf( "  LỖI   %s%s\n", $name, '' !== $detail ? "\n        " . str_replace( "\n", "\n        ", $detail ) : '' );
	}
}

function same( $name, $expected, $actual ) {
	check( $name, $expected === $actual, "mong đợi: " . var_export( $expected, true ) . "\n        thực tế:  " . var_export( $actual, true ) );
}

// ── 1. Hai bản giống nhau ở phần dùng chung ─────────────────────────────────
echo "Phần dùng chung\n";
$shared = array_filter( array_map( 'trim', file( __DIR__ . '/webp-shared.txt' ) ), static function ( $l ) {
	return '' !== $l && '#' !== $l[0];
} );
foreach ( $shared as $f ) {
	check( "giống nhau: $f", is_file( "$v1/$f" ) && is_file( "$v2/$f" ) && sha1_file( "$v1/$f" ) === sha1_file( "$v2/$f" ), 'chạy: bash scripts/webp-sync.sh' );
}

// ── WordPress giả lập ───────────────────────────────────────────────────────
$GLOBALS['filters'] = array();
$GLOBALS['actions'] = array();
$GLOBALS['meta']    = array();  // [id][key] = value
$GLOBALS['options'] = array();
$GLOBALS['is_admin'] = false;
$GLOBALS['doing']   = array();
$GLOBALS['notices'] = 0;

$UP = sys_get_temp_dir() . '/wsl-test-' . getmypid();
@mkdir( "$UP/2026/10", 0777, true );

function add_filter( $hook, $cb, $pri = 10, $args = 1 ) { $GLOBALS['filters'][ $hook ][ $pri ][] = array( $cb, $args ); return true; }
function add_action( $hook, $cb, $pri = 10, $args = 1 ) {
	if ( 'admin_notices' === $hook ) { $GLOBALS['notices']++; }
	return add_filter( $hook, $cb, $pri, $args );
}
function remove_filter( $hook, $cb, $pri = 10 ) {
	foreach ( $GLOBALS['filters'][ $hook ][ $pri ] ?? array() as $i => $f ) {
		if ( $f[0] === $cb ) { unset( $GLOBALS['filters'][ $hook ][ $pri ][ $i ] ); }
	}
	return true;
}
function apply_filters( $hook, $value, ...$args ) {
	$all = $GLOBALS['filters'][ $hook ] ?? array();
	ksort( $all );
	foreach ( $all as $list ) {
		foreach ( $list as $f ) {
			$value = call_user_func_array( $f[0], array_slice( array_merge( array( $value ), $args ), 0, $f[1] ) );
		}
	}
	return $value;
}
function do_action( $hook, ...$args ) { $GLOBALS['actions'][] = array( $hook, $args ); }
function doing_action( $hook ) { return in_array( $hook, $GLOBALS['doing'], true ); }
function register_activation_hook( $f, $cb ) {}
function register_deactivation_hook( $f, $cb ) {}
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f ) { return 'https://ex.test/wp-content/plugins/' . basename( dirname( $f ) ) . '/'; }
function is_admin() { return $GLOBALS['is_admin']; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function wp_get_raw_referer() { return ''; }
function is_feed() { return false; }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function wp_basename( $p, $s = '' ) { return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $p ) ), $s ) ); }
function path_join( $a, $b ) { return rtrim( $a, '/' ) . '/' . $b; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $s ) { return $s; }
function wp_upload_dir( $t = null, $c = true ) { return array( 'basedir' => $GLOBALS['UP'], 'baseurl' => 'https://ex.test/wp-content/uploads' ); }
function get_option( $k, $d = false ) {
	$pre = apply_filters( "pre_option_$k", false, $k, $d );
	if ( false !== $pre ) { return $pre; }
	return array_key_exists( $k, $GLOBALS['options'] ) ? $GLOBALS['options'][ $k ] : $d;
}
function update_option( $k, $v ) { $GLOBALS['options'][ $k ] = $v; return true; }
function get_post_meta( $id, $k, $single = false ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function update_meta_cache( $t, $ids ) {}
function wp_get_attachment_metadata( $id ) { return $GLOBALS['meta'][ $id ]['_wp_attachment_metadata'] ?? false; }
function get_post_mime_type( $id ) { return $GLOBALS['meta'][ $id ]['mime'] ?? false; }
function get_attached_file( $id ) { return false; }
function wp_image_editor_supports( $a ) { return true; }
function wp_get_image_mime( $f ) { return preg_match( '/\.png$/', $f ) ? 'image/png' : 'image/jpeg'; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
class WP_Error {}
function wp_delete_file( $f ) { $f = apply_filters( 'wp_delete_file', $f ); if ( $f ) { @unlink( $f ); } }

// Trình xử lý ảnh giả: ghi .webp bằng ($GLOBALS['ratio'] × cỡ gốc) byte.
$GLOBALS['ratio'] = 0.5;
class Fake_Editor {
	private $src;
	function __construct( $src ) { $this->src = $src; }
	function set_quality( $q ) {}
	function save( $dest, $mime ) {
		$GLOBALS['saves'][] = basename( $dest );
		file_put_contents( $dest, str_repeat( 'w', (int) ( filesize( $this->src ) * $GLOBALS['ratio'] ) ) );
		return array( 'path' => $dest );
	}
}
function wp_get_image_editor( $src, $args = array() ) { return new Fake_Editor( $src ); }

// Hàm ảnh của WordPress, bản rút gọn đủ cho test.
function wp_image_src_get_dimensions( $src, $meta, $id = 0 ) {
	$name = wp_basename( $src );
	if ( wp_basename( $meta['file'] ) === $name ) { return array( $meta['width'], $meta['height'] ); }
	foreach ( $meta['sizes'] ?? array() as $s ) {
		if ( $s['file'] === $name ) { return array( $s['width'], $s['height'] ); }
	}
	return false;
}
function wp_calculate_image_srcset( $size, $src, $meta, $id ) {
	$base = 'https://ex.test/wp-content/uploads/' . dirname( $meta['file'] ) . '/';
	$out  = array();
	foreach ( $meta['sizes'] ?? array() as $s ) {
		if ( abs( $s['width'] / $s['height'] - $size[0] / $size[1] ) < 0.02 ) { $out[] = $base . $s['file'] . ' ' . $s['width'] . 'w'; }
	}
	$out[] = $base . wp_basename( $meta['file'] ) . ' ' . $meta['width'] . 'w';
	return count( $out ) > 1 ? implode( ', ', $out ) : false;
}
function wp_calculate_image_sizes( $size, $src, $meta, $id ) { return sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $size[0] ); }

class Fake_WPDB {
	public $postmeta = 'wp_postmeta';
	public $queries  = 0;
	function prepare( $q, $args ) { return array( $q, $args ); }
	function get_results( $p ) {
		$this->queries++;
		$rows = array();
		foreach ( $GLOBALS['meta'] as $id => $m ) {
			if ( isset( $m['_wp_attached_file'] ) && in_array( $m['_wp_attached_file'], $p[1], true ) ) {
				$rows[] = (object) array( 'post_id' => $id, 'meta_value' => $m['_wp_attached_file'] );
			}
		}
		return $rows;
	}
}
$GLOBALS['wpdb'] = new Fake_WPDB();

// Attachment 10: ảnh 2000×1000 với 3 size; 11: PNG 400×400 không có size lớn.
function make_attachment( $id, $file, $w, $h, $sizes, $mime = 'image/jpeg' ) {
	$dir = $GLOBALS['UP'] . '/' . dirname( $file );
	file_put_contents( $GLOBALS['UP'] . '/' . $file, str_repeat( 'x', 10000 ) );
	$meta = array( 'width' => $w, 'height' => $h, 'file' => $file, 'sizes' => array() );
	foreach ( $sizes as $name => $s ) {
		$sf = preg_replace( '/(\.\w+)$/', "-{$s[0]}x{$s[1]}$1", wp_basename( $file ) );
		file_put_contents( "$dir/$sf", str_repeat( 'x', 4000 ) );
		$meta['sizes'][ $name ] = array( 'file' => $sf, 'width' => $s[0], 'height' => $s[1], 'mime-type' => $mime );
	}
	$GLOBALS['meta'][ $id ] = array( '_wp_attached_file' => $file, '_wp_attachment_metadata' => $meta, 'mime' => $mime );
}
make_attachment( 10, '2026/10/anh.jpg', 2000, 1000, array( 'thumbnail' => array( 150, 150 ), 'medium' => array( 300, 150 ), 'large' => array( 1024, 512 ) ) );
make_attachment( 11, '2026/10/logo.png', 400, 400, array( 'thumbnail' => array( 150, 150 ) ), 'image/png' );

// ── Nạp bản hoangditru ──────────────────────────────────────────────────────
require "$v1/hoangditru-webp.php";

// ── 2. Chuyển đổi ───────────────────────────────────────────────────────────
echo "\nChuyển đổi\n";
$s = wsl_convert_attachment( 10 );
same( 'chuyển đủ 4 file của ảnh 10', 4, $s['converted'] );
check( 'ảnh gốc vẫn còn', is_file( "$UP/2026/10/anh.jpg" ) && is_file( "$UP/2026/10/anh-300x150.jpg" ) );
check( 'có anh.jpg.webp', is_file( "$UP/2026/10/anh.jpg.webp" ) );
same( 'trạng thái ghi 1 cho mọi file', array( 1, 1, 1, 1 ), array_values( wsl_state( 10 )['files'] ) );
$GLOBALS['saves'] = array();
$s = wsl_convert_attachment( 10 );
same( 'chạy lại: bỏ qua cả 4', array( 0, 4 ), array( $s['converted'], $s['skipped'] ) );
same( 'chạy lại: không ghi file nào', array(), $GLOBALS['saves'] );
unlink( "$UP/2026/10/anh-300x150.jpg.webp" );
$s = wsl_convert_attachment( 10 );
same( 'file .webp bị xoá tay thì chuyển lại đúng file đó', 1, $s['converted'] );
$s = wsl_convert_attachment( 10, null, true );
same( 'ghi đè: chuyển lại cả 4', 4, $s['converted'] );

$GLOBALS['ratio'] = 1.2; // WebP to hơn bản gốc
$s = wsl_convert_attachment( 11 );
same( 'WebP không nhỏ hơn: không giữ file', false, is_file( "$UP/2026/10/logo.png.webp" ) );
same( 'WebP không nhỏ hơn: trạng thái 0', array( 'logo.png' => 0, 'logo-150x150.png' => 0 ), wsl_state( 11 )['files'] );
$GLOBALS['saves'] = array();
wsl_convert_attachment( 11 );
same( 'trạng thái 0 thì không thử lại', array(), $GLOBALS['saves'] );
$GLOBALS['ratio'] = 0.5;

$GLOBALS['meta'][12] = array( 'mime' => 'image/gif' );
same( 'GIF bị bỏ qua', 0, wsl_convert_attachment( 12 )['converted'] );

$m10 = wp_get_attachment_metadata( 10 );
same( 'filter wp_update_attachment_metadata trả nguyên metadata', $m10, apply_filters( 'wp_update_attachment_metadata', $m10, 10 ) );

file_put_contents( "$UP/2026/10/xoa.jpg", 'x' );
file_put_contents( "$UP/2026/10/xoa.jpg.webp", 'w' );
wp_delete_file( "$UP/2026/10/xoa.jpg" );
check( 'xoá ảnh thì xoá luôn .webp', ! is_file( "$UP/2026/10/xoa.jpg.webp" ) && ! is_file( "$UP/2026/10/xoa.jpg" ) );

// ── 3. URL ──────────────────────────────────────────────────────────────────
echo "\nURL\n";
same( 'thêm .webp giữ query', 'https://ex.test/a.jpg.webp?v=2#x', wsl_append_webp( 'https://ex.test/a.jpg?v=2#x' ) );
same( 'không thêm hai lần', 'https://ex.test/a.jpg.webp', wsl_append_webp( 'https://ex.test/a.jpg.webp' ) );
same( 'bỏ .webp', 'https://ex.test/a.png?v=1', wsl_strip_webp( 'https://ex.test/a.png.webp?v=1' ) );
same( 'không đụng .webp thật', 'https://ex.test/a.webp', wsl_strip_webp( 'https://ex.test/a.webp' ) );
same( 'rel từ URL tuyệt đối', '2026/10/anh-300x150.jpg', wsl_upload_rel( 'https://ex.test/wp-content/uploads/2026/10/anh-300x150.jpg' ) );
same( 'rel từ URL tương đối', '2026/10/anh.jpg', wsl_upload_rel( '/wp-content/uploads/2026/10/anh.jpg' ) );
same( 'rel từ URL .jpg.webp', '2026/10/anh.jpg', wsl_upload_rel( '//ex.test/wp-content/uploads/2026/10/anh.jpg.webp' ) );
same( 'host khác: không nhận', null, wsl_upload_rel( 'https://cdn.khac.test/wp-content/uploads/2026/10/anh.jpg' ) );
same( 'ngoài uploads: không nhận', null, wsl_upload_rel( 'https://ex.test/wp-content/themes/t/a.jpg' ) );
same( 'ảnh có WebP → .webp', 'https://ex.test/wp-content/uploads/2026/10/anh-300x150.jpg.webp', wsl_webp_url( 'https://ex.test/wp-content/uploads/2026/10/anh-300x150.jpg', 10 ) );
same( 'trạng thái 0 → giữ gốc', 'https://ex.test/wp-content/uploads/2026/10/logo.png', wsl_webp_url( 'https://ex.test/wp-content/uploads/2026/10/logo.png', 11 ) );
same( 'URL .webp mà file không có WebP → về bản gốc', 'https://ex.test/wp-content/uploads/2026/10/logo.png', wsl_webp_url( 'https://ex.test/wp-content/uploads/2026/10/logo.png.webp', 11 ) );
same( 'tra ID từ URL của một size', array( '2026/10/anh-1024x512.jpg' => 10, '2026/10/khong-co.jpg' => 0 ), wsl_resolve_ids( array( '2026/10/anh-1024x512.jpg', '2026/10/khong-co.jpg' ) ) );

// ── 4. Bộ lọc HTML ──────────────────────────────────────────────────────────
echo "\nBộ lọc HTML\n";
$U = 'https://ex.test/wp-content/uploads/2026/10/';
$q = $GLOBALS['wpdb']->queries;
$in = "<p>a</p><img src=\"{$U}anh-300x150.jpg\" alt=\"x > src=y\" class=\"c\">\n<img\n  src=\"{$U}anh-1024x512.jpg\"\n  alt=\"\" loading=\"lazy\" />";
$out = wsl_rewrite_html( $in );
check( 'chỉ 1 truy vấn DB cho cả đoạn HTML', $GLOBALS['wpdb']->queries - $q <= 1 );
check( 'src đổi sang WebP', false !== strpos( $out, 'src="' . $U . 'anh-300x150.jpg.webp"' ), $out );
check( 'alt chứa "> src=" giữ nguyên', false !== strpos( $out, 'alt="x > src=y" class="c"' ), $out );
check( 'thêm srcset toàn WebP', (bool) preg_match( '/srcset="[^"]*anh-300x150\.jpg\.webp 300w[^"]*anh-1024x512\.jpg\.webp 1024w[^"]*anh\.jpg\.webp 2000w"/', $out ), $out );
check( 'thêm sizes', false !== strpos( $out, 'sizes="(max-width: 300px) 100vw, 300px"' ), $out );
check( 'ảnh lazy có sizes="auto, …"', false !== strpos( $out, 'sizes="auto, (max-width: 1024px) 100vw, 1024px"' ), $out );
check( 'data-full là bản đầy đủ WebP', false !== strpos( $out, 'data-full="' . $U . 'anh.jpg.webp"' ), $out );
check( 'thẻ nhiều dòng vẫn được đổi', false !== strpos( $out, $U . 'anh-1024x512.jpg.webp"' ), $out );
check( 'giữ kiểu đóng />', (bool) preg_match( '#loading="lazy"[^>]*/>#', $out ), $out );
same( 'chạy hai lần cho cùng kết quả', $out, wsl_rewrite_html( $out ) );

$in  = "<img src=\"{$U}anh-300x150.jpg\" srcset=\"{$U}anh-300x150.jpg 300w, {$U}anh-1024x512.jpg 1024w\" sizes=\"50vw\">";
$out = wsl_rewrite_html( $in );
same( 'srcset có sẵn: chỉ đổi URL, không thêm',
	"<img src=\"{$U}anh-300x150.jpg.webp\" srcset=\"{$U}anh-300x150.jpg.webp 300w, {$U}anh-1024x512.jpg.webp 1024w\" sizes=\"50vw\" data-full=\"{$U}anh.jpg.webp\">", $out );

$in  = "<img data-src=\"{$U}anh-1024x512.jpg\" src=\"data:image/svg+xml,%3Csvg%3E\" width=\"400\" height=\"200\" alt=\"\">";
$out = wsl_rewrite_html( $in );
check( 'lazy-load data-src: đổi data-src, giữ src giữ chỗ', false !== strpos( $out, 'data-src="' . $U . 'anh-1024x512.jpg.webp"' ) && false !== strpos( $out, 'src="data:image/svg+xml' ), $out );
check( 'lazy-load data-src: srcset vào data-srcset', false !== strpos( $out, 'data-srcset="' ) && false === strpos( $out, ' srcset="' ), $out );
check( 'width/height theo khung (2:1 khác) vẫn ra srcset theo file', false !== strpos( $out, 'anh-300x150.jpg.webp 300w' ), $out );

$out = wsl_rewrite_html( "<img src=\"{$U}logo.png\" alt=\"\">" );
check( 'ảnh 400px: không gắn data-full (dưới 600px)', false === strpos( $out, 'data-full' ), $out );
check( 'ảnh không có WebP: giữ .png', false !== strpos( $out, 'src="' . $U . 'logo.png"' ), $out );

$in = '<img src="https://khac.test/a.jpg" alt=""><img src="' . $U . 'anh.jpg" data-no-webp>';
same( 'ảnh ngoài site và data-no-webp: không đụng', $in, wsl_rewrite_html( $in ) );

$out = wsl_rewrite_html( '<picture><source srcset="' . $U . 'anh-1024x512.jpg 1024w" media="(min-width:800px)"><img src="' . $U . 'anh-300x150.jpg" alt=""></picture>' );
check( '<source srcset> đổi sang WebP', false !== strpos( $out, '<source srcset="' . $U . 'anh-1024x512.jpg.webp 1024w"' ), $out );

$out = wsl_rewrite_html( '<div style="background-image:url(&quot;' . $U . 'anh.jpg&quot;)"></div><section style="background:url(' . $U . 'logo.png) no-repeat">' );
check( 'url(&quot;…&quot;) trong style đổi sang WebP', false !== strpos( $out, 'url(&quot;' . $U . 'anh.jpg.webp&quot;)' ), $out );
check( 'url() của ảnh không có WebP: giữ', false !== strpos( $out, 'url(' . $U . 'logo.png)' ), $out );

$out = wsl_rewrite_html( "<img src=\"{$U}anh-300x150.jpg\" alt=\"\">", array( 'lightbox' => false, 'srcset' => false ) );
check( 'tắt srcset/lightbox qua tuỳ chọn', false === strpos( $out, 'srcset' ) && false === strpos( $out, 'data-full' ) && false !== strpos( $out, '.jpg.webp' ), $out );

// ── 5. Bản hoangditru: API ảnh của WordPress ────────────────────────────────
echo "\nBản hoangditru (API)\n";
$img = array( $U . 'anh-300x150.jpg', 300, 150, true );
same( 'wp_get_attachment_image_src → WebP', $U . 'anh-300x150.jpg.webp', apply_filters( 'wp_get_attachment_image_src', $img, 10, 'medium', false )[0] );
$src = apply_filters( 'wp_calculate_image_srcset', array( 300 => array( 'url' => $U . 'anh-300x150.jpg', 'descriptor' => 'w', 'value' => 300 ) ), array( 300, 150 ), '', array(), 10 );
same( 'wp_calculate_image_srcset → WebP', $U . 'anh-300x150.jpg.webp', $src[300]['url'] );
$GLOBALS['is_admin'] = true;
same( 'trong wp-admin: giữ URL gốc', $U . 'anh-300x150.jpg', apply_filters( 'wp_get_attachment_image_src', $img, 10, 'medium', false )[0] );
same( 'trong wp-admin: nội dung không bị lọc', '<img src="' . $U . 'anh.jpg">', apply_filters( 'the_content', '<img src="' . $U . 'anh.jpg">' ) );
$GLOBALS['is_admin'] = false;
$GLOBALS['doing']    = array( 'wp_head' );
same( 'trong <head> (og:image): giữ URL gốc', $U . 'anh-300x150.jpg', apply_filters( 'wp_get_attachment_image_src', $img, 10, 'medium', false )[0] );
$GLOBALS['doing'] = array();
define( 'REST_REQUEST', true );
same( 'REST API: giữ URL gốc', $U . 'anh-300x150.jpg', wsl_api_image_src( $img, 10, 'medium', false )[0] );
check( 'lightbox loại trừ lightbox riêng của BĐS', false !== strpos( wsl_lightbox_exclude(), '.bds-mb-gallery' ) && false !== strpos( wsl_lightbox_exclude(), '.bds-lb-trigger' ) );

// ── 6. LiteSpeed ────────────────────────────────────────────────────────────
echo "\nLiteSpeed\n";
$GLOBALS['options']['litespeed.conf.img_optm-webp'] = 1;
$GLOBALS['options']['litespeed.conf.img_optm-webp_replace_srcset'] = 1;
same( 'đọc img_optm-webp khi plugin bật → 0', 0, get_option( 'litespeed.conf.img_optm-webp' ) );
wsl_lscwp_disable_persist();
same( 'kích hoạt: ghi hẳn 0 vào DB', array( 0, 0 ), array( $GLOBALS['options']['litespeed.conf.img_optm-webp'], $GLOBALS['options']['litespeed.conf.img_optm-webp_replace_srcset'] ) );
check( 'kích hoạt: không tạo option LiteSpeed chưa có', ! array_key_exists( 'litespeed.conf.img_optm-webp_replace', $GLOBALS['options'] ) );
same( 'sau khi ghi: filter vẫn ép 0', 0, get_option( 'litespeed.conf.img_optm-webp' ) );
check( 'kích hoạt: purge cache LiteSpeed', in_array( 'litespeed_purge_all', array_column( $GLOBALS['actions'], 0 ), true ) );

// ── 7. Bản dùng cho site khác ───────────────────────────────────────────────
echo "\nBản dùng cho site khác\n";
$before = $GLOBALS['notices'];
require "$v2/webp-srcset-lightbox.php";
check( 'bật cùng bản hoangditru: tự đứng ngoài, báo trong admin', $GLOBALS['notices'] === $before + 1 && ! function_exists( 'wsl_buffer_filter' ) );
require "$v2/includes/buffer.php";
$out = wsl_buffer_filter( '<html><body><img src="' . $U . 'anh.jpg"></body></html>' );
check( 'buffer: HTML được lọc', false !== strpos( $out, 'src="' . $U . 'anh.jpg.webp"' ) && false !== strpos( $out, 'srcset=' ), $out );
check( 'buffer: ảnh đang ở cỡ đầy đủ thì không gắn data-full', false === strpos( $out, 'data-full' ), $out );

// ── 8. hdtc_img() của plugin lõi ────────────────────────────────────────────
echo "\nhdtc_img()\n";
function attachment_url_to_postid( $url ) {
	foreach ( $GLOBALS['meta'] as $id => $m ) {
		if ( isset( $m['_wp_attached_file'] ) && $url === 'https://ex.test/wp-content/uploads/' . $m['_wp_attached_file'] ) { return $id; }
	}
	return 0;
}
function wp_get_attachment_image( $id, $size, $icon = false, $attr = array() ) { return "<img id=$id size=" . ( is_array( $size ) ? implode( 'x', $size ) : $size ) . '>'; }
require $root . '/wp-content/plugins/hoangditru-core/includes/img.php';
same( 'ID → wp_get_attachment_image, size mặc định large', '<img id=10 size=large>', hdtc_img( 10 ) );
same( 'URL của size → đúng size đó', '<img id=10 size=thumbnail>', hdtc_img( $U . 'anh-150x150.jpg' ) );
same( 'URL bản chính → full', '<img id=10 size=full>', hdtc_img( $U . 'anh.jpg' ) );
same( 'URL .jpg.webp vẫn tra được', '<img id=10 size=medium>', hdtc_img( $U . 'anh-300x150.jpg.webp' ) );
same( 'URL ngoài thư viện → <img> như cũ', '<img src="https://khac.test/a.png" alt="" class="c">', hdtc_img( 'https://khac.test/a.png', null, array( 'class' => 'c', 'sizes' => '9px', 'loading' => false ) ) );
same( 'rỗng → chuỗi rỗng', '', hdtc_img( 0 ) );

// ── Dọn ─────────────────────────────────────────────────────────────────────
array_map( 'unlink', glob( "$UP/2026/10/*" ) );
@rmdir( "$UP/2026/10" );
@rmdir( "$UP/2026" );
@rmdir( $UP );

if ( $errors ) {
	printf( "\n%d/%d trường hợp LỖI\n", $errors, $count );
	exit( 1 );
}

printf( "\nOK — %d trường hợp.\n", $count );

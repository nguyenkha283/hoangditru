<?php
/**
 * Nạp thử plugin lõi với WordPress giả lập.
 *
 *   php scripts/plugin-load.test.php
 *
 * php -l chỉ kiểm cú pháp TỪNG file. Nó không bắt được: require sai đường dẫn,
 * hai module khai báo trùng tên hàm, hay lỗi chết ngay lúc nạp. Máy không có
 * WordPress chạy được nên đây là cách duy nhất kiểm những thứ đó.
 *
 * Cách làm: khai báo sẵn các hàm WordPress mà module gọi ở phạm vi file dưới
 * dạng hàm rỗng, rồi require file chính của plugin và đếm xem các module có nạp
 * đủ không.
 *
 * Giới hạn: chỉ chứng minh plugin NẠP được. Không chứng minh nó CHẠY đúng —
 * phần đó phải xem trên site thật.
 */

define( 'ABSPATH', __DIR__ . '/../' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

$GLOBALS['hooks']      = array();
$GLOBALS['filters']    = array();
$GLOBALS['shortcodes'] = array();
$GLOBALS['post_types'] = array();
$GLOBALS['taxonomies'] = array();

// ── Hàm WordPress mà module gọi ở phạm vi file ───────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) { $GLOBALS['hooks'][] = $hook; }
function add_filter( $hook, $cb = null, $pri = 10, $args = 1 ) {
	$GLOBALS['hooks'][] = $hook;
	// Giữ lại callback để test gọi thẳng, xem filter trả về gì.
	$GLOBALS['filters'][ $hook ][] = $cb;
}
function remove_action( $hook, $cb = null, $pri = 10 ) {}
function remove_filter( $hook, $cb = null, $pri = 10 ) {}
function do_action( $hook ) {}
function apply_filters( $hook, $value = null ) { return $value; }
function add_shortcode( $tag, $cb = null ) { $GLOBALS['shortcodes'][] = $tag; }
function shortcode_exists( $tag ) { return in_array( $tag, $GLOBALS['shortcodes'], true ); }
function register_post_type( $type, $args = array() ) { $GLOBALS['post_types'][] = $type; }
function register_taxonomy( $tax, $type = null, $args = array() ) { $GLOBALS['taxonomies'][] = $tax; }
function register_activation_hook( $file, $cb ) {}
function register_deactivation_hook( $file, $cb ) {}
function register_uninstall_hook( $file, $cb ) {}
function add_meta_box() {}
function add_menu_page() {}
function add_submenu_page() {}
function add_options_page() {}
function add_theme_page() {}
function register_setting() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_register_style() {}
function wp_register_script() {}
function wp_localize_script() {}
function wp_add_inline_style() {}
function wp_add_inline_script() {}
function wp_enqueue_media() {}
function load_plugin_textdomain() {}
function plugin_dir_path( $file ) { return rtrim( str_replace( '\\', '/', dirname( $file ) ), '/' ) . '/'; }
function plugin_dir_url( $file ) { return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
function plugins_url( $path = '', $plugin = '' ) { return 'https://example.test/wp-content/plugins/' . ltrim( $path, '/' ); }
function content_url( $path = '' ) { return 'https://example.test/wp-content' . $path; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function site_url( $path = '' ) { return 'https://example.test' . $path; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function get_option( $k, $d = false ) { return $d; }
function add_option( $k, $v = '' ) { return true; }
function update_option( $k, $v = '' ) { return true; }
function delete_option( $k ) { return true; }
function get_post_meta( $id, $k = '', $single = false ) { return $single ? '' : array(); }
function update_post_meta() { return true; }
function delete_post_meta() { return true; }
function get_term_meta( $id, $k = '', $single = false ) { return $single ? '' : array(); }
function update_term_meta() { return true; }
function delete_term_meta() { return true; }
function flush_rewrite_rules() {}
function is_admin() { return false; }
function current_user_can() { return true; }
function esc_html( $s ) { return $s; }
function esc_attr( $s ) { return $s; }
function esc_url( $s ) { return $s; }
function esc_url_raw( $s ) { return $s; }
function esc_js( $s ) { return $s; }
function esc_textarea( $s ) { return $s; }
function wp_kses_post( $s ) { return $s; }
function sanitize_text_field( $s ) { return $s; }
function sanitize_hex_color( $s ) { return $s; }
function sanitize_key( $s ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( (array) $d, (array) $a ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_create_nonce( $a = -1 ) { return 'nonce'; }
function wp_nonce_field() {}
function wp_verify_nonce() { return true; }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ) ; }
function __( $t, $d = null ) { return $t; }
function _e( $t, $d = null ) { echo $t; }
function _x( $t, $c = '', $d = null ) { return $t; }
function esc_html__( $t, $d = null ) { return $t; }
function esc_html_e( $t, $d = null ) { echo $t; }
function esc_attr__( $t, $d = null ) { return $t; }
function esc_attr_e( $t, $d = null ) { echo $t; }
function wp_doing_ajax() { return false; }
function wp_next_scheduled() { return false; }
function wp_schedule_event() {}
function wp_clear_scheduled_hook() {}
function get_bloginfo( $s = '' ) { return ''; }
function wp_get_attachment_image_url() { return ''; }
function wp_get_attachment_image() { return ''; }
function wp_upload_dir() { return array( 'basedir' => '/tmp', 'baseurl' => 'https://example.test/uploads' ); }

// ── Nạp ─────────────────────────────────────────────────────────────────────
$plugin = __DIR__ . '/../wp-content/plugins/hoangditru-core/hoangditru-core.php';

if ( ! file_exists( $plugin ) ) {
	fwrite( STDERR, "Không thấy $plugin\n" );
	exit( 1 );
}

require $plugin;

// ── Kiểm ────────────────────────────────────────────────────────────────────
$errors = array();

if ( ! defined( 'HDTC_VERSION' ) ) {
	$errors[] = 'Không định nghĩa HDTC_VERSION';
}

// Mỗi module phải thật sự được nạp — nhận biết qua hàm đặc trưng của nó.
$expect = array(
	'phan-quyen'             => 'hdtq_sync_role',
	'bang-dieu-khien'        => 'hdtbd_dashboard',
	'contact-info-manager'   => 'cim_activate',
	'cim-footer'             => 'cimf_enqueue',
	'trang-chu'              => 'tc_register_template',
	'bat-dong-san'           => 'bds_activate',
	'landing-page'           => 'lpb_register_golden_visa_cpt',
	'citizenship-investment' => 'ci_register_cpt',
	'smart-breadcrumb'       => 'sbc_defaults',
	'snera-youtube-embed'    => 'snera_yt_extract_id',
);

$loaded = 0;

foreach ( $expect as $module => $fn ) {
	if ( function_exists( $fn ) ) {
		$loaded++;
		printf( "  ok    %-26s (%s)\n", $module, $fn );
	} else {
		printf( "  LỖI   %-26s không nạp — thiếu %s()\n", $module, $fn );
		$errors[] = $module;
	}
}

// Hàm in ảnh dùng chung cho template của các module (includes/img.php).
if ( ! function_exists( 'hdtc_img' ) ) {
	echo "  LỖI   thiếu hdtc_img() — includes/img.php không được nạp\n";
	$errors[] = 'hdtc_img';
}

// Những định danh nằm trong database, đổi là mất dữ liệu.
$must_have_shortcodes = array( 'cim_phone', 'cim_card', 'cim_footer_section', 'youtube_embed' );

foreach ( $must_have_shortcodes as $sc ) {
	if ( ! in_array( $sc, $GLOBALS['shortcodes'], true ) ) {
		printf( "  LỖI   thiếu shortcode [%s]\n", $sc );
		$errors[] = 'shortcode ' . $sc;
	}
}

/*
 * Thứ tự meta box ở màn soạn.
 *
 * Gọi thẳng filter đã đăng ký và so chuỗi. Gõ sai một id hộp thì hộp đó rơi
 * xuống cuối màn hình mà không có dấu hiệu gì — test này bắt được.
 */
$layouts = array(
	'golden_visa'        => array(
		'normal' => 'lpb_hero_icon,postexcerpt,lpb_advantages,lpb_benefits,lpb_conditions,lpb_process,lpb_du_an,lpb_faqs',
		'side'   => 'submitdiv,postimagediv',
	),
	'citizenship_invest' => array(
		'normal' => 'ci_page_overlay,ci_carousel,postexcerpt,ci_features,ci_why_us,ci_du_an,ci_programs,ci_news',
		'side'   => 'submitdiv,postimagediv',
	),
	'bat-dong-san'       => array(
		'normal' => 'bds_carousel,bds_thong_tin,bds_tong_quan,bds_mo_ta,bds_mat_bang',
		'side'   => 'submitdiv,postimagediv',
	),
);

foreach ( $layouts as $pt => $want ) {
	$hook = "get_user_option_meta-box-order_{$pt}";

	if ( empty( $GLOBALS['filters'][ $hook ] ) ) {
		printf( "  LỖI   %s không đăng ký thứ tự meta box\n", $pt );
		$errors[] = $hook;
		continue;
	}

	$got = call_user_func( $GLOBALS['filters'][ $hook ][0], false );

	foreach ( array( 'normal', 'side' ) as $col ) {
		if ( ( $got[ $col ] ?? '' ) === $want[ $col ] ) {
			printf( "  ok    %-20s %-6s %s\n", $pt, $col, $want[ $col ] );
		} else {
			printf( "  LỖI   %-20s %-6s\n        mong đợi: %s\n        nhận    : %s\n", $pt, $col, $want[ $col ], $got[ $col ] ?? '(rỗng)' );
			$errors[] = $pt . '/' . $col;
		}
	}
}

/*
 * Bỏ ẩn "Mô tả ngắn" mà KHÔNG bỏ ẩn những hộp WordPress vốn ẩn.
 *
 * get_hidden_meta_boxes() chỉ dùng danh sách mặc định khi giá trị không phải
 * mảng, nên filter trả về mảng rỗng sẽ lôi cả Trường tuỳ biến, Thảo luận,
 * Đường dẫn ra màn hình. Đây đúng là cái bẫy dễ dính nhất ở chỗ này.
 */
// Chỉ hai post type này cần hiện "Mô tả ngắn"; bat-dong-san không dùng excerpt
// nên không đăng ký filter, kiểm nó ở đây sẽ báo sai.
foreach ( array( 'golden_visa', 'citizenship_invest' ) as $pt ) {
	$hook = "get_user_option_metaboxhidden_{$pt}";

	if ( empty( $GLOBALS['filters'][ $hook ] ) ) {
		printf( "  LỖI   %s không đăng ký danh sách hộp ẩn\n", $pt );
		$errors[] = $hook;
		continue;
	}

	// false = người dùng chưa kéo thả bao giờ, chưa có tuỳ chọn riêng.
	$hidden = call_user_func( $GLOBALS['filters'][ $hook ][0], false );

	if ( in_array( 'postexcerpt', $hidden, true ) ) {
		printf( "  LỖI   %s vẫn ẩn Mô tả ngắn\n", $pt );
		$errors[] = $pt . '/postexcerpt';
	} elseif ( ! in_array( 'postcustom', $hidden, true ) || ! in_array( 'commentsdiv', $hidden, true ) ) {
		printf( "  LỖI   %s bỏ ẩn quá tay, Trường tuỳ biến hoặc Thảo luận sẽ hiện ra\n", $pt );
		$errors[] = $pt . '/hidden';
	} else {
		printf( "  ok    %-20s hiện Mô tả ngắn, vẫn ẩn %d hộp mặc định\n", $pt, count( $hidden ) );
	}
}

printf(
	"\n  %d/%d module nạp được, %d shortcode đăng ký, %d hook\n",
	$loaded,
	count( $expect ),
	count( $GLOBALS['shortcodes'] ),
	count( $GLOBALS['hooks'] )
);

if ( $errors ) {
	printf( "\n%d lỗi\n", count( $errors ) );
	exit( 1 );
}

printf( "\nOK — plugin lõi nạp được, đủ %d module.\n", count( $expect ) );

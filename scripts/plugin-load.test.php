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
 * dạng hàm rỗng, rồi require file chính của plugin và đếm xem 8 module có nạp
 * đủ không.
 *
 * Giới hạn: chỉ chứng minh plugin NẠP được. Không chứng minh nó CHẠY đúng —
 * phần đó phải xem trên site thật.
 */

define( 'ABSPATH', __DIR__ . '/../' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

$GLOBALS['hooks']      = array();
$GLOBALS['shortcodes'] = array();
$GLOBALS['post_types'] = array();
$GLOBALS['taxonomies'] = array();

// ── Hàm WordPress mà module gọi ở phạm vi file ───────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) { $GLOBALS['hooks'][] = $hook; }
function add_filter( $hook, $cb = null, $pri = 10, $args = 1 ) { $GLOBALS['hooks'][] = $hook; }
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

// Những định danh nằm trong database, đổi là mất dữ liệu.
$must_have_shortcodes = array( 'cim_phone', 'cim_card', 'cim_footer_section', 'youtube_embed' );

foreach ( $must_have_shortcodes as $sc ) {
	if ( ! in_array( $sc, $GLOBALS['shortcodes'], true ) ) {
		printf( "  LỖI   thiếu shortcode [%s]\n", $sc );
		$errors[] = 'shortcode ' . $sc;
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

echo "\nOK — plugin lõi nạp được, đủ 8 module.\n";

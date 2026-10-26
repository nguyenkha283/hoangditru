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
$GLOBALS['actions']    = array();
$GLOBALS['shortcodes'] = array();
$GLOBALS['post_types'] = array();
$GLOBALS['taxonomies'] = array();

// ── Hàm WordPress mà module gọi ở phạm vi file ───────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) {
	$GLOBALS['hooks'][] = $hook;
	// Giữ callback để test chạy thẳng hook, xem nó làm gì.
	$GLOBALS['actions'][ $hook ][] = $cb;
}
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
function remove_post_type_support() {}

/**
 * remove_meta_box() giả lập, giống bản thật: đặt false ở CẢ bốn priority.
 *
 * Giống thật mới kiểm được rằng hdtc_demote_unlisted_boxes() không dời dấu đó
 * đi — dời là mất dấu và hộp đã gỡ sẽ bị dựng lại.
 */
function remove_meta_box( $id, $page, $context ) {
	foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
		$GLOBALS['wp_meta_boxes'][ $page ][ $context ][ $priority ][ $id ] = false;
	}
}

/**
 * Dựng mảng hộp giả cho $wp_meta_boxes: id => mảng khai báo.
 *
 * @param string[] $ids Id hộp.
 * @return array
 */
function fake_boxes( array $ids ) {
	$out = array();

	foreach ( $ids as $id ) {
		$out[ $id ] = array( 'id' => $id, 'title' => $id );
	}

	return $out;
}
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
	'xoa-cache'              => 'hdtxc_purge',
	'menu-tu-dong'           => 'hdtmenu_add',
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

/*
 * Hộp lạ ở cột chính phải xuống cuối, cả ba màn.
 *
 * Rank Math SEO đăng ký hộp ở normal/high, mà do_meta_boxes() render 'high'
 * trước 'sorted' — chỗ chứa thứ tự ta ép — nên không hạ priority thì nó chen
 * lên trên hộp đầu tiên của mình. Dựng lại $wp_meta_boxes y như WordPress rồi
 * chạy qua chính hook đã đăng ký.
 *
 * Phải chạy qua hook, không gọi thẳng hdtc_demote_unlisted_boxes(): gọi thẳng
 * thì tắt cờ 'tail' đi test vẫn xanh, tức test không kiểm được gì.
 *
 * Giới hạn: stub không giữ priority nên callback chạy theo thứ tự đăng ký. Thực
 * tế closure của hdtc_admin_layout() đăng ký ở priority 9999 nên chạy sau cùng,
 * mà nó cũng là cái đăng ký sau cùng trong mỗi file — hai thứ tự trùng nhau.
 */
/**
 * Priority nào đã mất dấu false của một hộp đã gỡ.
 *
 * @param array  $cot Mảng priority => hộp của một cột.
 * @param string $id  Id hộp đã gỡ.
 * @return string[]
 */
function hdtc_test_thieu_dau( array $cot, $id ) {
	$thieu = array();

	foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
		if ( false !== ( $cot[ $priority ][ $id ] ?? null ) ) {
			$thieu[] = $priority;
		}
	}

	return $thieu;
}

$tail_cases = array(
	'golden_visa'        => LPB_ADMIN_BOX_ORDER,
	'citizenship_invest' => CI_ADMIN_BOX_ORDER,
	'bat-dong-san'       => BDS_ADMIN_BOX_ORDER,
);

foreach ( $tail_cases as $pt => $order ) {
	// Chia hộp của mình ra hai priority khác nhau: hàm hạ phải chừa cả hai.
	$half = (int) ceil( count( $order ) / 2 );

	$GLOBALS['wp_meta_boxes'] = array(
		$pt => array(
			'normal' => array(
				'high'    => fake_boxes( array( 'rank_math_metabox' ) ),
				'core'    => fake_boxes( array_slice( $order, 0, $half ) ),
				'default' => fake_boxes( array_merge( array_slice( $order, $half ), array( 'hop_la_khac' ) ) ),
			),
		),
	);

	/*
	 * Thêm một hộp đã bị remove_meta_box() gỡ. Dấu của nó là giá trị false đặt
	 * ở CẢ bốn priority; hàm hạ không được dời dấu đi, vì mất dấu ở priority
	 * gốc là một add_meta_box() sau đó dựng lại đúng hộp đáng ra đã gỡ.
	 */
	remove_meta_box( 'hop_da_go', $pt, 'normal' );

	foreach ( (array) ( $GLOBALS['actions'][ "add_meta_boxes_{$pt}" ] ?? array() ) as $cb ) {
		call_user_func( $cb, null );
	}

	$cot  = $GLOBALS['wp_meta_boxes'][ $pt ]['normal'];
	$low  = array_keys( array_filter( $cot['low'] ?? array() ) );
	$high = array_keys( array_filter( $cot['high'] ?? array() ) );

	/*
	 * Hộp của mình phải NGUYÊN priority cũ. Hạ cả chúng xuống 'low' thì thứ tự
	 * ép mất tác dụng: mọi hộp cùng một priority, trật tự lại do thứ tự đăng ký.
	 */
	$cua_minh = array_merge(
		array_keys( array_filter( $cot['core'] ?? array() ) ),
		array_keys( array_filter( $cot['default'] ?? array() ) )
	);

	if ( array( 'rank_math_metabox', 'hop_la_khac' ) !== $low ) {
		printf( "  LỖI   %-20s hộp lạ chưa xuống 'low': %s\n", $pt, $low ? implode( ',', $low ) : '(rỗng)' );
		$errors[] = $pt . '/tail-low';
	} elseif ( $high ) {
		printf( "  LỖI   %-20s còn hộp ở 'high', nó sẽ nằm trên tất cả: %s\n", $pt, implode( ',', $high ) );
		$errors[] = $pt . '/tail-high';
	} elseif ( $thieu_dau = hdtc_test_thieu_dau( $cot, 'hop_da_go' ) ) {
		printf( "  LỖI   %-20s mất dấu hộp đã gỡ ở priority: %s
", $pt, implode( ',', $thieu_dau ) );
		$errors[] = $pt . '/tail-da-go';
	} elseif ( $order !== $cua_minh ) {
		printf( "  LỖI   %-20s hộp của mình bị đụng priority: %s\n", $pt, implode( ',', $cua_minh ) );
		$errors[] = $pt . '/tail-keep';
	} else {
		printf( "  ok    %-20s %d hộp lạ xuống sau %s\n", $pt, count( $low ), end( $order ) );
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

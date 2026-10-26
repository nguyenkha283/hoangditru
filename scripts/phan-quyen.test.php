<?php
/**
 * Test cho allowlist màn hình admin của module phan-quyen.
 *
 *   php scripts/phan-quyen.test.php
 *
 * Module này quyết định Biên tập viên được mở màn hình admin nào. Sai một mục
 * theo chiều lỏng là lộ mục đáng ra phải chặn; sai theo chiều chặt là biên tập
 * viên không sửa được nội dung của chính họ. Không có WordPress chạy được trên
 * máy nên test giả lập mấy hàm post mà module gọi, rồi gọi thẳng hàm quyết
 * định.
 *
 * Chỉ kiểm phần logic thuần. Việc menu có thật sự biến mất và wp_die có thật sự
 * chặn thì phải xem trên site thật.
 */

define( 'ABSPATH', __DIR__ . '/../' );

// ── Hàm WordPress mà module gọi ─────────────────────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function add_filter( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function absint( $v ) { return abs( (int) $v ); }
function wp_parse_str( $string, &$array ) { parse_str( $string, $array ); }

/**
 * Post giả lập: id => post type.
 *
 * 301 và 302 là revision, post_type của chúng là 'revision' y như WordPress
 * thật — module phải lần về post gốc mới biết được phép hay không.
 */
const FAKE_POSTS = array(
	101 => 'post',
	102 => 'attachment',
	103 => 'golden_visa',
	104 => 'bat-dong-san',
	105 => 'citizenship_invest',
	106 => 'page',
	301 => 'revision',
	302 => 'revision',
	999 => 'product',
);

const FAKE_PARENTS = array(
	301 => 101,
	302 => 999,
);

function get_post_type( $id ) { return FAKE_POSTS[ (int) $id ] ?? false; }

/**
 * $submenu của WordPress, dạng rút gọn: chỉ giữ ô [2] là slug.
 *
 * Giao diện giống trên site thật sau khi WordPress dựng menu — mục "Menu" nằm
 * lẫn với Chủ đề, Customizer, Widget, Sửa file theme.
 */
$GLOBALS['submenu'] = array(
	'themes.php' => array(
		5  => array( 'Chủ đề', 'switch_themes', 'themes.php' ),
		6  => array( 'Customize', 'customize', 'customize.php?return=x' ),
		7  => array( 'Widget', 'edit_theme_options', 'widgets.php' ),
		10 => array( 'Menu', 'edit_theme_options', 'nav-menus.php' ),
		15 => array( 'Sửa file theme', 'edit_themes', 'theme-editor.php' ),
	),
	'tools.php' => array(
		5 => array( 'Công cụ', 'edit_posts', 'tools.php' ),
		10 => array( 'Nhập vào', 'import', 'import.php' ),
	),
);
function wp_get_post_parent_id( $id ) { return FAKE_PARENTS[ (int) $id ] ?? 0; }

require __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/phan-quyen/phan-quyen.php';

$pass = 0;
$fail = 0;

function t( $name, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		printf( "  ok    %s\n", $name );
		$pass++;
		return;
	}

	printf(
		"  LỖI   %s\n          nhận %s, cần %s\n",
		$name,
		var_export( $got, true ),
		var_export( $want, true )
	);
	$fail++;
}

/** @param bool $want Màn hình có được phép không. */
function screen( $pagenow, $args, $want ) {
	$label = $pagenow . ( $args ? '?' . http_build_query( $args ) : '' );
	t( $label, hdtq_screen_allowed( $pagenow, $args ), $want );
}

echo "\n── 8 mục được phép ─────────────────────────────────────────────────\n";

screen( 'edit.php', array(), true );                                  // Bài viết
screen( 'post-new.php', array(), true );
screen( 'edit.php', array( 'post_type' => 'page' ), true );           // Trang
screen( 'upload.php', array(), true );                                // Media
screen( 'media-new.php', array(), true );
screen( 'edit.php', array( 'post_type' => 'golden_visa' ), true );    // Golden Visa
screen( 'edit.php', array( 'post_type' => 'bat-dong-san' ), true );   // Bất động sản
screen( 'edit.php', array( 'post_type' => 'citizenship_invest' ), true ); // Đầu tư quốc tịch
screen( 'admin.php', array( 'page' => 'trang-chu-settings' ), true ); // Trang chủ
screen( 'admin.php', array( 'page' => 'contact-info-manager' ), true ); // Thông tin liên hệ

echo "\n── Bảng tin, Hồ sơ cá nhân và endpoint phải mở ─────────────────────\n";

screen( 'index.php', array(), true );
screen( 'profile.php', array(), true );
screen( 'admin-ajax.php', array(), true );
screen( 'async-upload.php', array(), true );
screen( 'media-upload.php', array(), true );

echo "\n── Mục phải chặn ───────────────────────────────────────────────────\n";

screen( 'edit-comments.php', array(), false );
screen( 'comment.php', array(), false );
screen( 'tools.php', array(), false );
screen( 'import.php', array(), false );
screen( 'export.php', array(), false );
screen( 'options-general.php', array(), false );
screen( 'options.php', array(), false );
screen( 'plugins.php', array(), false );
screen( 'plugin-install.php', array(), false );
screen( 'plugin-editor.php', array(), false );
screen( 'themes.php', array(), false );
screen( 'theme-editor.php', array(), false );
screen( 'widgets.php', array(), false );
screen( 'customize.php', array(), false );
screen( 'users.php', array(), false );
screen( 'user-new.php', array(), false );
screen( 'user-edit.php', array(), false );
screen( 'update-core.php', array(), false );
screen( 'site-health.php', array(), false );
screen( 'admin.php', array(), false );

echo "\n── Post type ngoài allowlist ───────────────────────────────────────\n";

screen( 'edit.php', array( 'post_type' => 'product' ), false );
screen( 'post-new.php', array( 'post_type' => 'product' ), false );
screen( 'edit.php', array( 'post_type' => 'shop_order' ), false );
screen( 'edit.php', array( 'post_type' => 'wpforms' ), false );

echo "\n── Taxonomy ────────────────────────────────────────────────────────\n";

screen( 'edit-tags.php', array( 'taxonomy' => 'category' ), true );
screen( 'edit-tags.php', array( 'taxonomy' => 'post_tag' ), true );
screen( 'edit-tags.php', array( 'taxonomy' => 'bds_category' ), true );
screen( 'edit-tags.php', array( 'taxonomy' => 'golden_visa_category' ), true );
screen( 'term.php', array( 'taxonomy' => 'bds_category' ), true );
screen( 'edit-tags.php', array( 'taxonomy' => 'product_cat' ), false );
screen( 'edit-tags.php', array(), false );

echo "\n── Sửa một post cụ thể ─────────────────────────────────────────────\n";

screen( 'post.php', array( 'post' => 101 ), true );
screen( 'post.php', array( 'post' => 102 ), true );
screen( 'post.php', array( 'post' => 103 ), true );
screen( 'post.php', array( 'post' => 999 ), false );
screen( 'post.php', array( 'post_ID' => 106 ), true );  // form submit dùng post_ID
screen( 'post.php', array( 'post_ID' => 999 ), false );
screen( 'post.php', array(), false );
screen( 'post.php', array( 'post' => 42 ), false );     // post không tồn tại

echo "\n── Revision phải lần về post gốc ───────────────────────────────────\n";

screen( 'revision.php', array( 'revision' => 301 ), true );
screen( 'revision.php', array( 'revision' => 302 ), false );
screen( 'revision.php', array( 'from' => 301 ), true );
screen( 'revision.php', array(), false );

echo "\n── Trang plugin lạ, kể cả khi nằm dưới menu được phép ──────────────\n";

screen( 'admin.php', array( 'page' => 'wc-settings' ), false );
screen( 'options-general.php', array( 'page' => 'smart-breadcrumb-settings' ), false );
screen( 'edit.php', array( 'post_type' => 'post', 'page' => 'plugin-nao-do' ), false );
screen( 'upload.php', array( 'page' => 'plugin-nao-do' ), false );

echo "\n── Tham số dạng mảng không được lọt ────────────────────────────────\n";

t( 'edit.php?post_type[]=post', hdtq_screen_allowed( 'edit.php', array( 'post_type' => array( 'post' ) ) ), false );
t( 'admin.php?page[]=…', hdtq_screen_allowed( 'admin.php', array( 'page' => array( 'trang-chu-settings' ) ) ), false );

echo "\n── Slug menu ───────────────────────────────────────────────────────\n";

$menu_slugs = array(
	'edit.php'                              => true,
	'upload.php'                            => true,
	'edit.php?post_type=page'               => true,
	'edit.php?post_type=golden_visa'        => true,
	'post-new.php?post_type=bat-dong-san'   => true,
	'edit-tags.php?taxonomy=bds_category&post_type=bat-dong-san' => true,
	'trang-chu-settings'                    => true,
	'contact-info-manager'                  => true,
	'index.php'                             => true,
	'profile.php'                           => true,
	'edit-comments.php'                     => false,
	'tools.php'                             => false,
	'options-general.php'                   => false,
	'themes.php'                            => false,
	'plugins.php'                           => false,
	'users.php'                             => false,
	'separator1'                            => false,
	'separator-last'                        => false,
	'woocommerce'                           => false,
	'wpforms-overview'                      => false,
	'edit.php?post_type=product'            => false,
);

foreach ( $menu_slugs as $slug => $want ) {
	t( "menu $slug", hdtq_menu_slug_allowed( (string) $slug ), $want );
}

echo "\n── Màn Giao diện: chỉ mục Menu được mở ─────────────────────────────\n";

screen( 'nav-menus.php', array(), true );
screen( 'nav-menus.php', array( 'action' => 'edit', 'menu' => '12' ), true );
t( 'trang Xoá cache', hdtq_screen_allowed( 'admin.php', array( 'page' => 'hdt-xoa-cache' ) ), true );

/*
 * themes.php là hai thứ khác nhau: màn đổi chủ đề thì chặn, còn mục cha trên
 * sidebar thì giữ để chở mục "Menu". Lẫn hai cái là hoặc mất mục Menu, hoặc mở
 * cả màn đổi chủ đề — nên kiểm cả hai chiều.
 */
t( 'màn themes.php vẫn chặn', hdtq_screen_allowed( 'themes.php', array() ), false );
t( 'themes.php là menu cha được giữ', isset( HDTQ_MENU_PARENTS['themes.php'] ), true );
t( 'themes.php còn con được phép', hdtq_submenu_has_allowed( 'themes.php' ), true );
t( 'tools.php không còn con nào được phép', hdtq_submenu_has_allowed( 'tools.php' ), false );
t( 'menu cha không có trong $submenu', hdtq_submenu_has_allowed( 'khong-co.php' ), false );

/*
 * edit_theme_options kéo theo `customize` và `edit_css`. Phần lưu của
 * Customizer đi qua admin-ajax.php, chỗ tầng chặn màn hình phải thoát sớm, nên
 * hai cap này phải bị từ chối ở tầng cap. Nhưng không được chặn chính
 * edit_theme_options — chặn là mất luôn màn Menu.
 */
t( 'chặn cap customize', in_array( 'customize', HDTQ_BLOCKED_CAPS, true ), true );
t( 'chặn cap edit_css', in_array( 'edit_css', HDTQ_BLOCKED_CAPS, true ), true );
t( 'KHÔNG chặn edit_theme_options', in_array( 'edit_theme_options', HDTQ_BLOCKED_CAPS, true ), false );

echo "\n── Bộ capability ───────────────────────────────────────────────────\n";

$must_have = array(
	'read',
	'upload_files',
	'manage_categories',
	'edit_posts',
	'delete_others_posts',
	'publish_posts',
	'edit_pages',
	'delete_others_pages',
	'publish_pages',
	HDTQ_CAP_SITE_SETTINGS,

	// Giao diện → Menu. Xem chú thích ở HDTQ_CAPS.
	'edit_theme_options',
);

foreach ( $must_have as $cap ) {
	t( "có $cap", in_array( $cap, HDTQ_CAPS, true ), true );
}

$must_not_have = array(
	'manage_options',
	'moderate_comments',
	'manage_links',
	'activate_plugins',
	'list_users',
	'edit_users',
	'switch_themes',
	'import',
	'export',
	'edit_files',
);

foreach ( $must_not_have as $cap ) {
	t( "không có $cap", in_array( $cap, HDTQ_CAPS, true ), false );
}

printf( "\n  %d ok, %d lỗi\n", $pass, $fail );

if ( $fail ) {
	exit( 1 );
}

echo "\nOK — allowlist phân quyền đúng.\n";

<?php
/**
 * Module: Phân quyền Biên tập viên
 *
 * Đặt lại quyền của role `editor` (WordPress tiếng Việt gọi là "Biên tập viên")
 * để tài khoản mang role này chỉ làm việc được với đúng 8 mục nội dung:
 *
 *   Bài viết · Trang · Media · Golden Visa · Bất động sản · Đầu tư quốc tịch
 *   Trang chủ (cài đặt) · Thông tin liên hệ (cài đặt)
 *
 * cộng Bảng tin và Hồ sơ cá nhân. Mọi mục khác bị chặn cả ở menu, ở màn hình
 * admin và ở tầng quyền.
 *
 * ── Bốn tầng chặn, vì một tầng không đủ ─────────────────────────────────────
 *
 *   1. Quyền (hdtq_sync_role)
 *      Role editor được set lại bằng đúng danh sách HDTQ_CAPS. Bỏ
 *      moderate_comments và manage_links mà WordPress cấp sẵn. Đây là tầng
 *      thật sự có hiệu lực với REST API, XML-RPC và mọi lối vào khác, không
 *      chỉ giao diện admin.
 *
 *   2. Menu (hdtq_prune_admin_menu)
 *      Menu "Bình luận" và "Công cụ" của WordPress đăng ký với cap `edit_posts`
 *      nên editor vẫn thấy chúng dù không còn quyền làm gì. Phải xoá tay. Cách
 *      làm là allowlist: cái gì không có trong danh sách thì biến mất, nên
 *      plugin cài thêm sau này cũng tự động bị ẩn, không phải sửa file này.
 *
 *   3. Màn hình (hdtq_guard_admin_screens)
 *      Ẩn menu không chặn được người biết URL. Tầng này kiểm chính URL đang mở
 *      và wp_die 403 nếu không nằm trong allowlist.
 *
 *   4. map_meta_cap (hdtq_block_other_post_types)
 *      Một CPT của plugin thứ ba mà để `capability_type` mặc định là `post` thì
 *      editor tự động sửa được qua REST API — tầng 2 và 3 không với tới. Tầng
 *      này từ chối mọi thao tác trên post không thuộc HDTQ_POST_TYPES.
 *
 * ── Vì sao Trang (page) nằm trong danh sách cho phép ────────────────────────
 * `golden_visa` đăng ký với `capability_type => 'page'` (xem
 * modules/landing-page/includes/cpt-golden-visa.php). Quyền sửa Golden Visa và
 * quyền sửa Trang là cùng một bộ cap, không tách được mà không đổi định danh
 * cap của CPT. Biên tập viên được giao cả mục Trang nên giữ nguyên như vậy.
 *
 * ── Vì sao có cap riêng cho 2 trang cài đặt ─────────────────────────────────
 * "Trang chủ" và "Thông tin liên hệ" trước đây gác bằng `manage_options`. Cấp
 * `manage_options` cho editor là mở luôn Cài đặt, Plugin, Người dùng, Giao
 * diện. Nên hai trang đó chuyển sang cap riêng HDTQ_CAP_SITE_SETTINGS, cấp cho
 * administrator và editor, không kéo theo gì khác.
 *
 * ── Ai không bị chặn ────────────────────────────────────────────────────────
 * hdtq_is_restricted_user() bỏ qua người có `manage_options`. Tài khoản vừa là
 * administrator vừa là editor vẫn vào được mọi nơi — tránh tự khoá chính mình.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bump số này để hdtq_sync_role() chạy lại sau khi sửa HDTQ_CAPS.
 *
 * Không có nó thì mỗi request đều ghi lại option quyền của role; có nó thì chỉ
 * ghi một lần cho mỗi phiên bản. Giá trị đang lưu nằm ở option
 * `hdt_phan_quyen_version`.
 */
define( 'HDTQ_VERSION', '1.1.0' );

const HDTQ_ROLE              = 'editor';
const HDTQ_SYNC_OPTION       = 'hdt_phan_quyen_version';
const HDTQ_CAP_SITE_SETTINGS = 'hdt_manage_site_content';

/**
 * Post type Biên tập viên được thao tác. Ngoài danh sách này là chặn.
 *
 * `attachment` là Media. `page` đi kèm `golden_visa` vì chung bộ cap.
 */
const HDTQ_POST_TYPES = array(
	'post',
	'page',
	'attachment',
	'golden_visa',
	'bat-dong-san',
	'citizenship_invest',
);

/** Taxonomy được vào trang quản lý term. */
const HDTQ_TAXONOMIES = array(
	'category',
	'post_tag',
	'bds_category',
	'golden_visa_category',
);

/** Trang admin.php?page=… được phép. */
const HDTQ_PAGES = array(
	'trang-chu-settings',
	'contact-info-manager',
	'hdt-xoa-cache',
);

/**
 * Menu cha giữ lại dù bản thân nó bị chặn, kèm cap để hiện: slug => cap.
 *
 * WordPress treo mục "Menu" dưới menu cha "Giao diện" (themes.php), mà
 * themes.php là màn đổi chủ đề — không được mở. Xoá cha thì mất luôn con, nên
 * cha được giữ lại và hdtq_prune_admin_menu() hạ cap của nó xuống cap ở đây;
 * _wp_menu_output() tự trỏ link của cha sang mục con đầu tiên còn lại.
 *
 * Cha chỉ sống khi còn ít nhất một con được phép — hết con thì xoá như thường,
 * để không còn lại một mục bấm vào là 403.
 */
const HDTQ_MENU_PARENTS = array(
	'themes.php' => 'edit_theme_options',
);

/**
 * Cap bị từ chối thẳng, kể cả khi WordPress map ra từ cap đã cấp.
 *
 * `edit_theme_options` kéo theo `customize` và `edit_css`. Màn customize.php
 * thì hdtq_guard_admin_screens() đã trả 403, nhưng phần lưu của Customizer đi
 * qua admin-ajax.php — chỗ tầng đó buộc phải thoát sớm. Không chặn thêm ở tầng
 * cap thì một request tự dựng vẫn đổi được logo site và Additional CSS. Tiêu đề
 * và mô tả site không nằm trong số đó: hai setting ấy đòi `manage_options`.
 *
 * Màn Giao diện → Menu không dùng hai cap này nên chặn không ảnh hưởng gì.
 */
const HDTQ_BLOCKED_CAPS = array(
	'customize',
	'edit_css',
);

/** File wp-admin không cần tham số, luôn được phép. */
const HDTQ_SCREENS = array(
	'index.php',        // Bảng tin
	'profile.php',      // Hồ sơ cá nhân
	'nav-menus.php',    // Giao diện → Menu
	'upload.php',       // Media
	'media-new.php',
	'media-upload.php', // iframe của popup chèn media
	'async-upload.php', // endpoint upload
	'admin-ajax.php',
	'admin-post.php',
);

/**
 * Toàn bộ quyền của Biên tập viên. Đây là danh sách ĐẦY ĐỦ: cap nào không có
 * ở đây sẽ bị xoá khỏi role.
 *
 * So với mặc định của WordPress thì thiếu `moderate_comments` (mục Bình luận)
 * và `manage_links`. Giữ `unfiltered_html` vì meta box của landing page và
 * Golden Visa cần dán được iframe/embed. Giữ `level_*` y như role gốc: một số
 * plugin cũ còn đọc chúng.
 */
const HDTQ_CAPS = array(
	'read',
	'upload_files',
	'unfiltered_html',
	'manage_categories',

	// Bài viết
	'edit_posts',
	'edit_others_posts',
	'edit_published_posts',
	'edit_private_posts',
	'publish_posts',
	'read_private_posts',
	'delete_posts',
	'delete_others_posts',
	'delete_published_posts',
	'delete_private_posts',

	// Trang — cũng là bộ cap của Golden Visa
	'edit_pages',
	'edit_others_pages',
	'edit_published_pages',
	'edit_private_pages',
	'publish_pages',
	'read_private_pages',
	'delete_pages',
	'delete_others_pages',
	'delete_published_pages',
	'delete_private_pages',

	/*
	 * Giao diện → Menu.
	 *
	 * WordPress không có cap riêng cho menu điều hướng: edit_theme_options gác
	 * chung cả Chủ đề, Customizer, Widget và Sửa file theme. Cấp nó là mở nguyên
	 * cụm đó ở tầng cap, nên phải đi kèm ba thứ: nav-menus.php là màn duy nhất
	 * được thêm vào HDTQ_SCREENS, HDTQ_MENU_PARENTS chỉ chở đúng mục "Menu", và
	 * hdtq_prune_admin_bar() gỡ các mục Giao diện trên admin bar. Theme này
	 * không đăng ký sidebar nào nên màn Widget cũng không có gì để sửa.
	 */
	'edit_theme_options',

	// Hai trang cài đặt nội dung
	HDTQ_CAP_SITE_SETTINGS,

	'level_0',
	'level_1',
	'level_2',
	'level_3',
	'level_4',
	'level_5',
	'level_6',
	'level_7',
);

// ═══════════════════════════════════════════════════════════════
// 1. QUYỀN
// ═══════════════════════════════════════════════════════════════

add_action( 'init', 'hdtq_maybe_sync_role', 1 );
/**
 * Đồng bộ quyền một lần cho mỗi HDTQ_VERSION.
 */
function hdtq_maybe_sync_role() {
	if ( HDTQ_VERSION === get_option( HDTQ_SYNC_OPTION ) ) {
		return;
	}

	hdtq_sync_role();
	update_option( HDTQ_SYNC_OPTION, HDTQ_VERSION );
}

/**
 * Set lại capabilities của role Biên tập viên theo đúng HDTQ_CAPS.
 *
 * Ghi thẳng vào option `wp_user_roles` qua WP_Role, nên có hiệu lực ngay với
 * mọi tài khoản đang mang role — không phải gán lại role cho từng người.
 */
function hdtq_sync_role() {
	$role = get_role( HDTQ_ROLE );

	if ( ! $role ) {
		return;
	}

	foreach ( array_keys( (array) $role->capabilities ) as $cap ) {
		if ( ! in_array( $cap, HDTQ_CAPS, true ) ) {
			$role->remove_cap( $cap );
		}
	}

	foreach ( HDTQ_CAPS as $cap ) {
		if ( empty( $role->capabilities[ $cap ] ) ) {
			$role->add_cap( $cap );
		}
	}

	// Không cấp thì chính administrator mất hai trang cài đặt.
	$admin = get_role( 'administrator' );

	if ( $admin && empty( $admin->capabilities[ HDTQ_CAP_SITE_SETTINGS ] ) ) {
		$admin->add_cap( HDTQ_CAP_SITE_SETTINGS );
	}
}

/**
 * User này có bị giới hạn không.
 *
 * Nhớ kết quả theo ID trong suốt request: hdtq_block_other_post_types() gọi hàm
 * này cho MỖI lần kiểm quyền, tức mỗi dòng của mỗi list table. Không nhớ thì
 * mỗi dòng tốn một lượt user_can().
 *
 * Cache chỉ sống trong một request nên không cần dọn: role của user thay đổi là
 * sang request khác. Mà người bị giới hạn cũng không sửa được role của ai.
 *
 * @param int|WP_User|null $user User cần kiểm, mặc định là người đang đăng nhập.
 * @return bool
 */
function hdtq_is_restricted_user( $user = null ) {
	static $cache = array();

	if ( null === $user ) {
		$user = wp_get_current_user();
	} elseif ( ! $user instanceof WP_User ) {
		$user = get_userdata( (int) $user );
	}

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	if ( ! isset( $cache[ $user->ID ] ) ) {
		// Người quản trị không bị chặn, kể cả khi mang thêm role editor — nếu
		// không thì admin tự khoá chính mình.
		//
		// 'manage_options' không phải meta cap nên user_can() ở đây không gọi
		// lại hdtq_block_other_post_types() theo vòng: filter đó thoát ngay khi
		// cap không nằm trong danh sách meta cap của nó.
		$cache[ $user->ID ] = ! user_can( $user, 'manage_options' )
			&& in_array( HDTQ_ROLE, (array) $user->roles, true );
	}

	return $cache[ $user->ID ];
}

// ═══════════════════════════════════════════════════════════════
// 2. ALLOWLIST MÀN HÌNH
// ═══════════════════════════════════════════════════════════════

/**
 * Đọc một tham số dạng chuỗi từ mảng request.
 *
 * Phân biệt hai trường hợp, và phải phân biệt: KHÔNG CÓ khoá thì trả về mặc
 * định (edit.php không kèm post_type nghĩa là Bài viết), còn khoá CÓ nhưng là
 * mảng (`?post_type[]=post`) thì trả về chuỗi rỗng — chuỗi rỗng không khớp
 * allowlist nào nên rơi vào nhánh chặn. Nếu gộp hai trường hợp thành một thì
 * `?post_type[]=…` được hiểu là giá trị mặc định và lọt qua.
 *
 * @param array  $args    Mảng tham số.
 * @param string $key     Khoá cần đọc.
 * @param string $default Giá trị mặc định khi không có khoá.
 * @return string
 */
function hdtq_arg( $args, $key, $default = '' ) {
	if ( ! isset( $args[ $key ] ) ) {
		return $default;
	}

	if ( ! is_scalar( $args[ $key ] ) ) {
		return '';
	}

	return (string) $args[ $key ];
}

/**
 * Màn hình admin này có được phép mở không.
 *
 * @param string $pagenow Tên file wp-admin đang chạy.
 * @param array  $args    Tham số của request.
 * @return bool
 */
function hdtq_screen_allowed( $pagenow, $args ) {
	// Trang do plugin/theme đăng ký: quyết định theo slug, bất kể file cha.
	if ( isset( $args['page'] ) ) {
		return in_array( hdtq_arg( $args, 'page' ), HDTQ_PAGES, true );
	}

	if ( in_array( $pagenow, HDTQ_SCREENS, true ) ) {
		return true;
	}

	switch ( $pagenow ) {
		case 'edit.php':
		case 'post-new.php':
			return in_array( hdtq_arg( $args, 'post_type', 'post' ), HDTQ_POST_TYPES, true );

		case 'post.php':
			$id = absint( hdtq_arg( $args, 'post' ) ) ?: absint( hdtq_arg( $args, 'post_ID' ) );
			return hdtq_post_allowed( $id );

		case 'revision.php':
			$id = absint( hdtq_arg( $args, 'revision' ) ) ?: absint( hdtq_arg( $args, 'from' ) );
			return hdtq_post_allowed( $id ? wp_get_post_parent_id( $id ) : 0 );

		case 'edit-tags.php':
		case 'term.php':
			return in_array( hdtq_arg( $args, 'taxonomy' ), HDTQ_TAXONOMIES, true );
	}

	return false;
}

/**
 * Post này thuộc post type được phép không.
 *
 * Revision mang post_type `revision`, phải lần về post gốc, nếu không thì thao
 * tác phục hồi bản nháp cũ bị chặn oan.
 *
 * @param int $post_id ID post.
 * @return bool
 */
function hdtq_post_allowed( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return false;
	}

	$type = get_post_type( $post_id );

	if ( 'revision' === $type ) {
		$parent = wp_get_post_parent_id( $post_id );
		$type   = $parent ? get_post_type( $parent ) : false;
	}

	return $type && in_array( $type, HDTQ_POST_TYPES, true );
}

// ═══════════════════════════════════════════════════════════════
// 3. MENU
// ═══════════════════════════════════════════════════════════════

add_action( 'admin_menu', 'hdtq_prune_admin_menu', 999 );
/**
 * Xoá mọi menu và submenu không nằm trong allowlist.
 *
 * Priority 999 để chạy sau khi tất cả plugin đã đăng ký menu của chúng.
 */
function hdtq_prune_admin_menu() {
	if ( ! hdtq_is_restricted_user() ) {
		return;
	}

	global $menu, $submenu;

	foreach ( (array) $menu as $i => $item ) {
		$slug = isset( $item[2] ) ? (string) $item[2] : '';

		if ( '' !== $slug && isset( HDTQ_MENU_PARENTS[ $slug ] ) && hdtq_submenu_has_allowed( $slug ) ) {
			/*
			 * Cap gốc của mục cha là cap của mục con đầu tiên — Giao diện là
			 * switch_themes, thứ Biên tập viên không có. Để nguyên thì WordPress
			 * tự gỡ mục cha ở bước lọc quyền sau `admin_menu`. Slug thì không
			 * đổi: $submenu vẫn đánh khoá theo slug cũ.
			 */
			$menu[ $i ][1] = HDTQ_MENU_PARENTS[ $slug ];
			continue;
		}

		if ( '' === $slug || ! hdtq_menu_slug_allowed( $slug ) ) {
			unset( $menu[ $i ] );

			if ( '' !== $slug ) {
				unset( $submenu[ $slug ] );
			}
		}
	}

	foreach ( (array) $submenu as $parent => $items ) {
		foreach ( (array) $items as $j => $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';

			if ( '' === $slug || ! hdtq_menu_slug_allowed( $slug ) ) {
				unset( $submenu[ $parent ][ $j ] );
			}
		}

		if ( empty( $submenu[ $parent ] ) ) {
			unset( $submenu[ $parent ] );
		}
	}
}

/**
 * Menu cha này còn mục con nào được phép không.
 *
 * Chạy trước vòng lọc $submenu nên đọc $submenu lúc còn nguyên — đúng cái cần:
 * câu hỏi là "sau khi lọc thì còn gì không".
 *
 * @param string $parent Slug menu cha.
 * @return bool
 */
function hdtq_submenu_has_allowed( $parent ) {
	global $submenu;

	foreach ( (array) ( $submenu[ $parent ] ?? array() ) as $item ) {
		$slug = isset( $item[2] ) ? (string) $item[2] : '';

		if ( '' !== $slug && hdtq_menu_slug_allowed( $slug ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Slug trong $menu/$submenu có được phép không.
 *
 * Slug có ba dạng: file wp-admin kèm query (`edit.php?post_type=golden_visa`),
 * slug trang của add_menu_page (`contact-info-manager`, chạy qua
 * admin.php?page=…), và dấu phân cách (`separator1`).
 *
 * @param string $slug Giá trị $menu[$i][2].
 * @return bool
 */
function hdtq_menu_slug_allowed( $slug ) {
	if ( 0 === strpos( $slug, 'separator' ) ) {
		return false;
	}

	$file  = $slug;
	$query = '';

	if ( false !== strpos( $slug, '?' ) ) {
		list( $file, $query ) = explode( '?', $slug, 2 );
	}

	if ( '.php' !== substr( $file, -4 ) ) {
		return in_array( $file, HDTQ_PAGES, true );
	}

	$args = array();

	if ( '' !== $query ) {
		wp_parse_str( $query, $args );
	}

	return hdtq_screen_allowed( $file, $args );
}

// ═══════════════════════════════════════════════════════════════
// 4. CHẶN TRUY CẬP TRỰC TIẾP BẰNG URL
// ═══════════════════════════════════════════════════════════════

add_action( 'admin_init', 'hdtq_guard_admin_screens', 1 );
/**
 * Chặn mọi màn hình admin ngoài allowlist.
 *
 * Ẩn menu chỉ làm mất đường dẫn, không chặn người gõ URL. Tầng này mới chặn.
 *
 * admin-ajax.php cũng fire `admin_init`, nên phải thoát sớm: AJAX của media,
 * của Gutenberg và của chính các module trong plugin này đều đi qua đó và tự
 * kiểm quyền riêng.
 */
function hdtq_guard_admin_screens() {
	if ( wp_doing_ajax() || ! hdtq_is_restricted_user() ) {
		return;
	}

	global $pagenow;

	if ( hdtq_screen_allowed( (string) $pagenow, $_REQUEST ) ) {
		return;
	}

	wp_die(
		'<h1>Không có quyền truy cập</h1>' .
		'<p>Tài khoản Biên tập viên không được mở mục này.</p>',
		'Không có quyền truy cập',
		array( 'response' => 403 )
	);
}

add_filter( 'map_meta_cap', 'hdtq_block_customizer', 10, 3 );
/**
 * Từ chối các cap trong HDTQ_BLOCKED_CAPS.
 *
 * @param string[] $caps    Cap mà WordPress đã map ra.
 * @param string   $cap     Cap gốc được hỏi.
 * @param int      $user_id User đang bị kiểm.
 * @return string[]
 */
function hdtq_block_customizer( $caps, $cap, $user_id ) {
	if ( ! in_array( $cap, HDTQ_BLOCKED_CAPS, true ) ) {
		return $caps;
	}

	return hdtq_is_restricted_user( $user_id ) ? array( 'do_not_allow' ) : $caps;
}

add_filter( 'map_meta_cap', 'hdtq_block_other_post_types', 10, 4 );
/**
 * Từ chối mọi thao tác trên post thuộc post type ngoài allowlist.
 *
 * Đây là tầng duy nhất với tới REST API: một CPT của plugin thứ ba để
 * `capability_type` mặc định là `post` thì editor có sẵn cap để sửa nó, ẩn menu
 * không giải quyết được.
 *
 * @param string[] $caps    Cap mà WordPress đã map ra.
 * @param string   $cap     Cap gốc được hỏi.
 * @param int      $user_id User đang bị kiểm.
 * @param array    $args    $args[0] là ID post.
 * @return string[]
 */
function hdtq_block_other_post_types( $caps, $cap, $user_id, $args ) {
	static $meta_caps = array(
		'edit_post',
		'read_post',
		'delete_post',
		'publish_post',
		'edit_post_meta',
		'delete_post_meta',
		'add_post_meta',
	);

	if ( ! in_array( $cap, $meta_caps, true ) || empty( $args[0] ) ) {
		return $caps;
	}

	if ( ! hdtq_is_restricted_user( $user_id ) ) {
		return $caps;
	}

	return hdtq_post_allowed( $args[0] ) ? $caps : array( 'do_not_allow' );
}

// ═══════════════════════════════════════════════════════════════
// 5. DỌN NHỮNG CHỖ CÒN LỘ MỤC BỊ CHẶN
// ═══════════════════════════════════════════════════════════════

add_action( 'admin_bar_menu', 'hdtq_prune_admin_bar', 999 );
/**
 * Bỏ các mục trên thanh admin dẫn vào chỗ bị chặn.
 *
 * Bong bóng Bình luận chỉ là thứ không dùng. Customizer và Widget mới là thứ
 * phải gỡ: edit_theme_options — cấp để sửa menu điều hướng — làm WordPress hiện
 * chúng ở đây, mà hdtq_guard_admin_screens() trả 403 khi mở, nên để lại chỉ là
 * link dẫn vào mặt tường. Mục "Menu" thì giữ.
 *
 * @param WP_Admin_Bar $bar Thanh admin.
 */
function hdtq_prune_admin_bar( $bar ) {
	if ( ! hdtq_is_restricted_user() ) {
		return;
	}

	$nodes = array(
		'comments',
		'customize',
		'widgets',
		'themes',
		'background',
		'header',
		'site-editor',
	);

	foreach ( $nodes as $node ) {
		$bar->remove_node( $node );
	}
}

add_action( 'wp_dashboard_setup', 'hdtq_prune_dashboard_widgets' );
/**
 * Bỏ hai widget Bảng tin có đếm và liệt kê bình luận.
 *
 * "Tổng quan" và "Hoạt động" đều gọi thẳng hàm bình luận của WordPress, không
 * kiểm `moderate_comments`, nên bỏ cap thôi là chưa đủ.
 */
function hdtq_prune_dashboard_widgets() {
	if ( ! hdtq_is_restricted_user() ) {
		return;
	}

	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}

add_filter( 'manage_posts_columns', 'hdtq_drop_comments_column' );
add_filter( 'manage_pages_columns', 'hdtq_drop_comments_column' );
/**
 * Bỏ cột Bình luận trong danh sách bài viết — nó trỏ tới màn hình đã bị chặn.
 *
 * @param array $columns Cột của list table.
 * @return array
 */
function hdtq_drop_comments_column( $columns ) {
	if ( hdtq_is_restricted_user() ) {
		unset( $columns['comments'] );
	}

	return $columns;
}

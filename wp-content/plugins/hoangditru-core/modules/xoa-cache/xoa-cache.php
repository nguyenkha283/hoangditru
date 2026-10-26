<?php
/**
 * Module: Xoá cache
 *
 * LiteSpeed Cache chốt cứng `manage_options` cho cả menu, cả nút purge trên
 * thanh admin, và không để lại filter nào đổi được cap đó (đọc
 * src/admin.cls.php, src/admin-display.cls.php, src/gui.cls.php của bản 7.9.2).
 * Nên Biên tập viên không thấy gì của LiteSpeed ngoài hộp trong màn sửa bài —
 * hộp đó không kiểm quyền.
 *
 * Cấp `manage_options` để mở menu LiteSpeed là mở luôn Cài đặt, Plugin, Người
 * dùng ở tầng REST và XML-RPC, tức phá đúng thứ module phan-quyen tồn tại để
 * chặn. Module này làm phần Biên tập viên thật sự cần — xoá cache sau khi sửa
 * nội dung — bằng API công khai của LiteSpeed (`litespeed_purge_all`, khai ở
 * src/api.cls.php), gác bằng cap riêng của site.
 *
 * Phần cấu hình LiteSpeed vẫn là việc của administrator.
 */

defined( 'ABSPATH' ) || exit;

/** Slug trang. Phải khớp mục trong HDTQ_PAGES của module phan-quyen. */
const HDTXC_PAGE = 'hdt-xoa-cache';

/** Tên action của admin-post.php, cũng là tên nonce. */
const HDTXC_ACTION = 'hdt_xoa_cache';

/** Hook công khai của LiteSpeed Cache để xoá toàn bộ cache trang. */
const HDTXC_PURGE_HOOK = 'litespeed_purge_all';

add_action( 'admin_menu', 'hdtxc_menu' );
/**
 * Menu "Xoá cache".
 *
 * Dùng HDTQ_CAP_SITE_SETTINGS nên cả Biên tập viên và administrator đều thấy —
 * hdtq_sync_role() cấp cap này cho cả hai role.
 */
function hdtxc_menu() {
	add_menu_page(
		'Xoá cache',
		'Xoá cache',
		HDTQ_CAP_SITE_SETTINGS,
		HDTXC_PAGE,
		'hdtxc_render',
		'dashicons-update'
	);
}

add_action( 'admin_bar_menu', 'hdtxc_admin_bar', 100 );
/**
 * Lối tắt trên thanh admin.
 *
 * Chỉ hiện cho người không có `manage_options`: ai có thì đã có nút purge của
 * chính LiteSpeed ở đó rồi, thêm nữa chỉ là hai nút cạnh nhau làm một việc.
 *
 * Link trỏ vào trang chứ không tự xoá: một cái bấm nhầm trên thanh admin không
 * nên làm xoá sạch cache của cả site.
 *
 * @param WP_Admin_Bar $bar Thanh admin.
 */
function hdtxc_admin_bar( $bar ) {
	if ( current_user_can( 'manage_options' ) || ! current_user_can( HDTQ_CAP_SITE_SETTINGS ) ) {
		return;
	}

	$bar->add_node(
		array(
			'id'    => 'hdt-xoa-cache',
			'title' => 'Xoá cache',
			'href'  => admin_url( 'admin.php?page=' . HDTXC_PAGE ),
		)
	);
}

add_action( 'admin_post_' . HDTXC_ACTION, 'hdtxc_purge' );
/**
 * Xoá cache rồi quay về trang.
 */
function hdtxc_purge() {
	if ( ! current_user_can( HDTQ_CAP_SITE_SETTINGS ) ) {
		wp_die( 'Không có quyền xoá cache.', '', array( 'response' => 403 ) );
	}

	check_admin_referer( HDTXC_ACTION );

	$available = (bool) has_action( HDTXC_PURGE_HOOK );

	if ( $available ) {
		do_action( HDTXC_PURGE_HOOK );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'     => HDTXC_PAGE,
				'ket-qua'  => $available ? 'da-xoa' : 'khong-co-plugin',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Nội dung trang.
 */
function hdtxc_render() {
	if ( ! current_user_can( HDTQ_CAP_SITE_SETTINGS ) ) {
		wp_die( 'Không có quyền truy cập.', '', array( 'response' => 403 ) );
	}

	$result    = isset( $_GET['ket-qua'] ) ? sanitize_key( wp_unslash( $_GET['ket-qua'] ) ) : '';
	$available = (bool) has_action( HDTXC_PURGE_HOOK );
	?>
	<div class="wrap">
		<h1>Xoá cache</h1>

		<?php if ( 'da-xoa' === $result ) : ?>
			<div class="notice notice-success"><p>Đã xoá cache. Tải lại trang ngoài site để xem nội dung mới.</p></div>
		<?php elseif ( 'khong-co-plugin' === $result ) : ?>
			<div class="notice notice-warning"><p>Không xoá được: plugin cache đang tắt. Nhờ administrator kiểm tra.</p></div>
		<?php endif; ?>

		<p>
			Trang ngoài site được lưu tạm để tải nhanh. Nếu bạn vừa sửa nội dung mà
			ngoài site vẫn thấy bản cũ, bấm nút dưới đây để xoá bản lưu tạm đó.
		</p>

		<?php if ( ! $available ) : ?>
			<p><strong>Plugin cache đang không hoạt động</strong>, nút này sẽ không có tác dụng.</p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( HDTXC_ACTION ); ?>">
			<?php wp_nonce_field( HDTXC_ACTION ); ?>
			<?php submit_button( 'Xoá toàn bộ cache', 'primary', 'submit', true, $available ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>
	</div>
	<?php
}

<?php
/**
 * Module: Bảng điều khiển
 *
 * Màn "Trang quản trị" mặc định của WordPress toàn thứ không dùng được cho site
 * này: tin tức WordPress.org, sự kiện meetup, ô nháp nhanh tạo bài trơn. Thay
 * bằng đúng thứ người dùng vào đây để làm — lối tắt thêm mới nội dung.
 *
 * Nhãn và icon lấy thẳng từ đối tượng post type, không chép cứng, nên đổi tên
 * CPT ở cpt.php là ô ở đây đổi theo.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các mục hiện trên bảng điều khiển, theo thứ tự.
 *
 * Chỉ cần tên post type. Phần còn lại đọc từ get_post_type_object().
 *
 * @var string[]
 */
const HDTBD_POST_TYPES = array(
	'post',
	'golden_visa',
	'citizenship_invest',
	'bat-dong-san',
);

/**
 * Icon dự phòng cho post type không khai báo menu_icon.
 *
 * 'post' của WordPress dùng icon riêng chứ không đặt menu_icon.
 *
 * @var array<string,string>
 */
const HDTBD_FALLBACK_ICONS = array(
	'post' => 'dashicons-admin-post',
);

/**
 * Widget "Thêm mới" ở đầu bảng điều khiển, và dọn widget mặc định.
 */
add_action( 'wp_dashboard_setup', 'hdtbd_dashboard' );
function hdtbd_dashboard() {
	/*
	 * Gỡ hai widget gây nhiễu. Phải thử nhiều context vì WordPress đăng ký
	 * chúng ở 'side', nhưng người dùng kéo được sang cột khác và khi đó
	 * remove_meta_box với context cũ sẽ không khớp.
	 */
	$drop = array(
		'dashboard_primary',     // Tin tức và sự kiện về WordPress
		'dashboard_quick_press', // Bản nháp nhanh
	);

	foreach ( $drop as $id ) {
		foreach ( array( 'normal', 'side', 'column3', 'column4' ) as $context ) {
			remove_meta_box( $id, 'dashboard', $context );
		}
	}

	wp_add_dashboard_widget( 'hdtbd_shortcuts', 'Thêm mới', 'hdtbd_render_shortcuts' );

	hdtbd_move_widget_first();
}

/**
 * Đưa widget của mình lên đầu cột chính.
 *
 * wp_add_dashboard_widget() nối vào cuối, mà đây là thứ cần thấy trước tiên.
 * Chỉ sắp lại mảng đăng ký, không đụng tới tuỳ chọn kéo thả của người dùng —
 * ai muốn xếp lại vẫn kéo được và WordPress nhớ như thường.
 */
function hdtbd_move_widget_first() {
	global $wp_meta_boxes;

	if ( empty( $wp_meta_boxes['dashboard']['normal']['core']['hdtbd_shortcuts'] ) ) {
		return;
	}

	$column = $wp_meta_boxes['dashboard']['normal']['core'];
	$ours   = array( 'hdtbd_shortcuts' => $column['hdtbd_shortcuts'] );

	unset( $column['hdtbd_shortcuts'] );

	$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $column );
}

/**
 * Nội dung widget: lưới ô lối tắt.
 *
 * Ô nào người dùng không có quyền tạo thì không hiện. Role Biên tập viên bị
 * module phan-quyen giới hạn, nên kiểm quyền ở đây là cần thật chứ không phải
 * cho đẹp.
 */
function hdtbd_render_shortcuts() {
	$tiles = array();

	foreach ( HDTBD_POST_TYPES as $post_type ) {
		$object = get_post_type_object( $post_type );

		if ( ! $object || ! current_user_can( $object->cap->create_posts ) ) {
			continue;
		}

		$icon = is_string( $object->menu_icon ) && 0 === strpos( $object->menu_icon, 'dashicons-' )
			? $object->menu_icon
			: ( HDTBD_FALLBACK_ICONS[ $post_type ] ?? 'dashicons-plus' );

		$url = 'post' === $post_type
			? admin_url( 'post-new.php' )
			: admin_url( 'post-new.php?post_type=' . $post_type );

		$tiles[] = sprintf(
			'<a class="hdtbd-tile" href="%s"><span class="dashicons %s" aria-hidden="true"></span><span class="hdtbd-tile__label">%s</span><span class="hdtbd-tile__hint">Thêm mới</span></a>',
			esc_url( $url ),
			esc_attr( $icon ),
			esc_html( $object->labels->name )
		);
	}

	if ( ! $tiles ) {
		echo '<p>Tài khoản này chưa có quyền thêm nội dung nào.</p>';
		return;
	}

	echo '<div class="hdtbd-grid">' . implode( '', $tiles ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Bảng điều khiển về một cột.
 *
 * Bố cục 4 cột của WordPress để lại hai ô trống ghi "Kéo các mục vào đây" sau
 * khi đã gỡ widget nhiễu. Một cột thì widget lối tắt trải hết chiều ngang và
 * bốn ô nằm cạnh nhau — đúng cái cần nhìn thấy, không còn ô trống.
 *
 * @param mixed $columns Số cột đã lưu của người dùng.
 * @return int
 */
add_filter( 'get_user_option_screen_layout_dashboard', 'hdtbd_one_column' );
function hdtbd_one_column( $columns ) {
	return 1;
}

/**
 * CSS cho lưới ô.
 *
 * In thẳng vào head của riêng màn bảng điều khiển: vài chục dòng, chỉ dùng ở
 * một màn, không đáng thêm một file và một lượt tải.
 */
add_action( 'admin_head-index.php', 'hdtbd_styles' );
function hdtbd_styles() {
	?>
	<style>
		#hdtbd_shortcuts .inside { margin: 0; padding: 0; }

		.hdtbd-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 1px;
			background: #dcdcde;
			border-top: 1px solid #dcdcde;
		}

		.hdtbd-tile {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 6px;
			padding: 22px 10px;
			background: #fff;
			text-align: center;
			text-decoration: none;
			color: #1d2327;
		}

		.hdtbd-tile:hover,
		.hdtbd-tile:focus {
			background: #f0f6fc;
			color: #1d2327;
			box-shadow: none;
		}

		.hdtbd-tile .dashicons {
			width: 32px; height: 32px;
			font-size: 32px;
			color: #2271b1;
		}

		.hdtbd-tile__label { font-size: 13px; font-weight: 600; line-height: 1.3; }
		.hdtbd-tile__hint  { font-size: 11px; color: #646970; }

		@media screen and (max-width: 782px) {
			.hdtbd-grid { grid-template-columns: repeat(2, 1fr); }
		}
	</style>
	<?php
}

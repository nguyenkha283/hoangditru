<?php
/**
 * Xếp màn soạn bài của một post type: thứ tự meta box và hộp nào được hiện.
 *
 * Dùng chung cho các module có màn soạn nhiều meta box (landing-page,
 * citizenship-investment). Tách ra đây vì phần dựng lại danh sách ẩn mặc định
 * rất dễ viết sai, và viết sai thì màn hình lòi ra Trường tuỳ biến, Thảo luận,
 * Đường dẫn — ngược hẳn mục đích.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách hộp WordPress ẩn mặc định ở màn soạn bài.
 *
 * get_hidden_meta_boxes() chỉ dùng danh sách này khi giá trị user option KHÔNG
 * phải mảng. Nghĩa là filter trả về mảng rỗng sẽ bỏ ẩn tất cả, chứ không phải
 * "giữ nguyên mặc định". Nên khi người dùng chưa có tuỳ chọn riêng, phải tự
 * dựng lại danh sách rồi mới bỏ bớt.
 *
 * @return string[]
 */
function hdtc_default_hidden_meta_boxes() {
	return array(
		'slugdiv',
		'trackbacksdiv',
		'postcustom',
		'postexcerpt',
		'commentstatusdiv',
		'commentsdiv',
		'authordiv',
		'revisionsdiv',
	);
}

/**
 * Ép thứ tự meta box và bỏ ẩn một số hộp cho một post type.
 *
 * WordPress nhớ thứ tự kéo thả theo TỪNG người dùng trong user meta
 * meta-box-order_<post_type>, và thứ tự đó đè lên thứ tự đăng ký. Nên sắp lại
 * lúc add_meta_box là không đủ: ai từng kéo một lần sẽ vẫn thấy thứ tự cũ.
 *
 * Hộp không có trong danh sách vẫn hiện, xếp sau các hộp đã liệt kê. Nhờ vậy
 * chỉ cần liệt kê những hộp mình quan tâm — ví dụ đưa postimagediv lên trước
 * LiteSpeed mà không cần biết id hộp của LiteSpeed.
 *
 * @param string $post_type Post type cần xếp.
 * @param array  $args      normal: string[] thứ tự cột chính.
 *                          side:   string[] thứ tự cột phải.
 *                          show:   string[] id hộp cần bỏ ẩn.
 */
function hdtc_admin_layout( $post_type, array $args ) {
	$normal = isset( $args['normal'] ) ? (array) $args['normal'] : array();
	$side   = isset( $args['side'] ) ? (array) $args['side'] : array();
	$show   = isset( $args['show'] ) ? (array) $args['show'] : array();

	if ( $normal || $side ) {
		add_filter(
			"get_user_option_meta-box-order_{$post_type}",
			static function ( $order ) use ( $normal, $side ) {
				return array(
					'normal'   => implode( ',', $normal ),
					'side'     => implode( ',', $side ),
					'advanced' => '',
				);
			}
		);
	}

	if ( $show ) {
		add_filter(
			"get_user_option_metaboxhidden_{$post_type}",
			static function ( $hidden ) use ( $show ) {
				if ( ! is_array( $hidden ) ) {
					$hidden = hdtc_default_hidden_meta_boxes();
				}

				return array_values( array_diff( $hidden, $show ) );
			}
		);
	}
}

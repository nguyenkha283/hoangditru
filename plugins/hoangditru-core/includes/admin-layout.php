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
 * Hộp không có trong danh sách vẫn hiện và xếp sau các hộp đã liệt kê — nhưng
 * CHỈ khi nó đăng ký ở priority 'core', 'default' hoặc 'low'. Hộp đăng ký
 * priority 'high' thì luôn nằm trên tất cả, vì thứ tự ta ép nằm ở 'sorted' mà
 * do_meta_boxes() chạy high trước sorted. Dùng 'tail' cho trường hợp đó.
 *
 * @param string $post_type Post type cần xếp.
 * @param array  $args      normal: string[] thứ tự cột chính.
 *                          side:   string[] thứ tự cột phải.
 *                          show:   string[] id hộp cần bỏ ẩn.
 *                          tail:   bool đẩy hộp lạ ở cột chính xuống cuối.
 */
function hdtc_admin_layout( $post_type, array $args ) {
	$normal = isset( $args['normal'] ) ? (array) $args['normal'] : array();
	$side   = isset( $args['side'] ) ? (array) $args['side'] : array();
	$show   = isset( $args['show'] ) ? (array) $args['show'] : array();
	$tail   = ! empty( $args['tail'] );

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

	if ( $normal && $tail ) {
		/*
		 * Priority 9999: hook add_meta_boxes_<post_type> fire sau hook
		 * add_meta_boxes chung, nhưng plugin vẫn có thể hook chính nó, nên chạy
		 * sau cùng cho chắc.
		 */
		add_action(
			"add_meta_boxes_{$post_type}",
			static function () use ( $post_type, $normal ) {
				hdtc_demote_unlisted_boxes( $post_type, 'normal', $normal );
			},
			9999
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

/**
 * Đẩy mọi hộp không liệt kê ở một cột xuống cuối cột đó.
 *
 * do_meta_boxes() render các priority theo đúng thứ tự
 * high → sorted → core → default → low, mà thứ tự do người dùng (và do filter
 * get_user_option_meta-box-order_*) quyết định thì nằm ở 'sorted'. Nên hộp nào
 * đăng ký với priority 'high' cũng nằm trên toàn bộ danh sách đã xếp, bất kể ta
 * xếp thế nào. Rank Math SEO đúng là như vậy: normal/high.
 *
 * Hạ chúng xuống 'low' là xong, và không phải chép cứng id hộp nào — hộp của
 * plugin cài thêm sau này cũng tự xuống dưới.
 *
 * Hộp đã liệt kê thì không đụng tới: do_meta_boxes() sẽ tự chuyển chúng sang
 * 'sorted', và add_meta_box() tìm hộp cũ trong cả bốn priority nên chuyển được
 * từ bất kỳ đâu.
 *
 * @param string   $post_type Post type đang xếp.
 * @param string   $context   Cột: normal / side / advanced.
 * @param string[] $keep      Id hộp giữ nguyên priority.
 */
function hdtc_demote_unlisted_boxes( $post_type, $context, array $keep ) {
	global $wp_meta_boxes;

	if ( empty( $wp_meta_boxes[ $post_type ][ $context ] ) ) {
		return;
	}

	foreach ( $wp_meta_boxes[ $post_type ][ $context ] as $priority => $boxes ) {
		if ( 'low' === $priority ) {
			continue;
		}

		foreach ( (array) $boxes as $id => $box ) {
			/*
			 * false là dấu remove_meta_box() để lại, và nó đặt dấu đó ở CẢ bốn
			 * priority. Dời dấu đi là mất dấu ở priority gốc, rồi một
			 * add_meta_box() sau đó sẽ dựng lại đúng hộp đáng ra đã bị gỡ.
			 */
			if ( false === $box || in_array( $id, $keep, true ) ) {
				continue;
			}

			unset( $wp_meta_boxes[ $post_type ][ $context ][ $priority ][ $id ] );

			$wp_meta_boxes[ $post_type ][ $context ]['low'][ $id ] = $box;
		}
	}
}

<?php
/**
 * Module: Tự động thêm mục mới vào menu
 *
 * Menu chính của site xếp theo cụm: mục cha "Golden Visa" chở các bài
 * golden_visa, "Đầu tư quốc tịch" chở các bài citizenship_invest, "Bất động
 * sản" chở các term bds_category. WordPress chỉ tự thêm vào menu được với
 * `page` (tuỳ chọn "Tự động thêm trang"), không có gì cho CPT và taxonomy — nên
 * mỗi lần thêm nội dung lại phải vào Giao diện → Menu kéo tay, và quên là trang
 * mới không ai vào được.
 *
 * Module này tìm mục cha bằng cách xem chính menu đang có: mục cha của các mục
 * cùng loại là chỗ mục mới thuộc về. Không chép cứng tên "Golden Visa", nên đổi
 * tên mục cha hay dựng lại menu đều không phải sửa code.
 */

defined( 'ABSPATH' ) || exit;

/** Post type được tự thêm vào menu khi xuất bản lần đầu. */
const HDTMENU_POST_TYPES = array(
	'golden_visa',
	'citizenship_invest',
);

/** Taxonomy được tự thêm vào menu khi tạo term mới. */
const HDTMENU_TAXONOMIES = array(
	'bds_category',
);

/**
 * Menu dùng khi chưa có mục nào cùng loại để dựa vào.
 *
 * Vị trí theme đăng ký ở functions.php. Mục mới vào cấp 1 của menu này.
 */
const HDTMENU_LOCATION = 'primary';

/**
 * Post meta đánh dấu "đã thêm vào menu rồi".
 *
 * Không có nó thì người quản trị xoá mục khỏi menu, lần sau hạ bài xuống nháp
 * rồi xuất bản lại là mục quay về — tức là không xoá được. Term không cần dấu
 * này: `created_term` chỉ chạy đúng một lần trong đời term.
 */
const HDTMENU_DONE_META = '_hdt_menu_da_them';

add_action( 'transition_post_status', 'hdtmenu_on_post', 10, 3 );
/**
 * Bài vừa được xuất bản lần đầu.
 *
 * Bắt `transition_post_status` chứ không phải `save_post`: chỉ lần đổi sang
 * publish mới đáng thêm, còn mỗi lượt sửa bài đã xuất bản thì không.
 *
 * @param string  $new_status Trạng thái mới.
 * @param string  $old_status Trạng thái cũ.
 * @param WP_Post $post       Bài vừa lưu.
 */
function hdtmenu_on_post( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}

	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, HDTMENU_POST_TYPES, true ) ) {
		return;
	}

	if ( get_post_meta( $post->ID, HDTMENU_DONE_META, true ) ) {
		return;
	}

	if ( hdtmenu_add( 'post_type', $post->post_type, (int) $post->ID ) ) {
		update_post_meta( $post->ID, HDTMENU_DONE_META, current_time( 'mysql' ) );
	}
}

add_action( 'created_term', 'hdtmenu_on_term', 10, 3 );
/**
 * Term vừa được tạo.
 *
 * @param int    $term_id  ID term.
 * @param int    $tt_id    ID term taxonomy, không dùng.
 * @param string $taxonomy Tên taxonomy.
 */
function hdtmenu_on_term( $term_id, $tt_id, $taxonomy ) {
	if ( ! in_array( $taxonomy, HDTMENU_TAXONOMIES, true ) ) {
		return;
	}

	hdtmenu_add( 'taxonomy', $taxonomy, (int) $term_id );
}

/**
 * Thêm đối tượng vào mọi menu có chỗ cho nó.
 *
 * Chạy qua tất cả menu, không chỉ menu chính: cùng một loại nội dung có thể
 * xuất hiện ở cả menu đầu trang và menu chân trang, và chỗ nào đang chở loại đó
 * thì chỗ đó cần mục mới.
 *
 * @param string $type      'post_type' hoặc 'taxonomy'.
 * @param string $object    Tên post type / taxonomy.
 * @param int    $object_id ID bài / term.
 * @return bool Đã xong việc với đối tượng này chưa — đã thêm, hoặc vốn đã có.
 */
function hdtmenu_add( $type, $object, $object_id ) {
	$done = false;

	foreach ( (array) wp_get_nav_menus() as $menu ) {
		$items = hdtmenu_items( (int) $menu->term_id );

		if ( null === $items ) {
			continue;
		}

		if ( hdtmenu_has_item( $items, $type, $object, $object_id ) ) {
			$done = true;
			continue;
		}

		$parent = hdtmenu_find_parent( $items, $type, $object );

		if ( false === $parent ) {
			continue;
		}

		hdtmenu_insert( (int) $menu->term_id, $parent, $type, $object, $object_id );
		$done = true;
	}

	if ( $done ) {
		return true;
	}

	/*
	 * Chưa menu nào chở loại này — menu mới dựng, hoặc mục cha vừa bị xoá hết
	 * con. Thả vào cấp 1 của menu chính: hiện sai chỗ thì còn sửa được, chứ
	 * không hiện thì không ai biết là thiếu.
	 */
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations[ HDTMENU_LOCATION ] ) ? (int) $locations[ HDTMENU_LOCATION ] : 0;

	if ( ! $menu_id ) {
		return false;
	}

	$items = hdtmenu_items( $menu_id );

	if ( null !== $items && hdtmenu_has_item( $items, $type, $object, $object_id ) ) {
		return true;
	}

	hdtmenu_insert( $menu_id, 0, $type, $object, $object_id );

	return true;
}

/**
 * Các mục của một menu, kể cả mục đang ở trạng thái nháp.
 *
 * @param int $menu_id ID menu.
 * @return array|null Mảng mục, hoặc null khi không đọc được menu.
 */
function hdtmenu_items( $menu_id ) {
	$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish,draft' ) );

	return is_array( $items ) ? $items : null;
}

/**
 * Menu này đã có mục trỏ tới đúng đối tượng đó chưa.
 *
 * @param array  $items     Mục của menu.
 * @param string $type      'post_type' hoặc 'taxonomy'.
 * @param string $object    Tên post type / taxonomy.
 * @param int    $object_id ID bài / term.
 * @return bool
 */
function hdtmenu_has_item( array $items, $type, $object, $object_id ) {
	foreach ( $items as $item ) {
		if ( $type === $item->type && $object === $item->object && (int) $object_id === (int) $item->object_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Mục cha mà các mục cùng loại đang nằm dưới.
 *
 * Đếm theo mục cha rồi lấy cái đông nhất: menu có thể lẫn một mục golden_visa
 * bị kéo ra ngoài cụm, nhưng số đông vẫn chỉ đúng chỗ. 0 là cấp 1 — đó cũng là
 * một câu trả lời hợp lệ, nên "không tìm được" phải là false chứ không phải 0,
 * hai thứ khác nhau.
 *
 * @param array  $items  Mục của menu.
 * @param string $type   'post_type' hoặc 'taxonomy'.
 * @param string $object Tên post type / taxonomy.
 * @return int|false ID mục cha, hoặc false khi menu không chở loại này.
 */
function hdtmenu_find_parent( array $items, $type, $object ) {
	$counts = array();

	foreach ( $items as $item ) {
		if ( $type !== $item->type || $object !== $item->object ) {
			continue;
		}

		$parent            = (int) $item->menu_item_parent;
		$counts[ $parent ] = isset( $counts[ $parent ] ) ? $counts[ $parent ] + 1 : 1;
	}

	if ( ! $counts ) {
		return false;
	}

	arsort( $counts );

	return (int) key( $counts );
}

/**
 * Tạo mục menu mới.
 *
 * `menu-item-title` để rỗng là có ý: WordPress khi đó lấy tiêu đề bài / tên
 * term làm nhãn mỗi lần dựng menu, nên đổi tên nội dung là menu đổi theo. Điền
 * sẵn tiêu đề thì nhãn đóng băng tại thời điểm thêm.
 *
 * Không truyền `menu-item-position`: WordPress xếp vào cuối menu, tức cuối
 * trong nhóm các mục cùng cha — đúng chỗ cho mục vừa thêm.
 *
 * @param int    $menu_id   ID menu.
 * @param int    $parent_id ID mục cha, 0 là cấp 1.
 * @param string $type      'post_type' hoặc 'taxonomy'.
 * @param string $object    Tên post type / taxonomy.
 * @param int    $object_id ID bài / term.
 * @return int|WP_Error ID mục menu.
 */
function hdtmenu_insert( $menu_id, $parent_id, $type, $object, $object_id ) {
	return wp_update_nav_menu_item(
		(int) $menu_id,
		0,
		array(
			'menu-item-type'      => $type,
			'menu-item-object'    => $object,
			'menu-item-object-id' => (int) $object_id,
			'menu-item-parent-id' => (int) $parent_id,
			'menu-item-title'     => '',
			'menu-item-status'    => 'publish',
		)
	);
}

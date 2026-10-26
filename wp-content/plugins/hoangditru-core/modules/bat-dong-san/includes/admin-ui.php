<?php
/**
 * Xếp màn soạn "Bất động sản" theo đúng thứ tự trang hiển thị.
 *
 * Chỉ tác động lên giao diện quản trị. Không đổi định danh nào, không đụng dữ liệu.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Thứ tự ngoài trang, theo templates/single-bat-dong-san.php:
 *
 *   bds-page-title        ← tiêu đề
 *   bds-carousel-section  ← Hình ảnh carousel
 *   bds-price-type-row    ← Thông tin bất động sản (giá, loại hình, địa chỉ)
 *   bds-tong-quan         ← Tổng quan dự án
 *   bds-mo-ta             ← Ô NỘI DUNG, tiêu đề section là "Mô tả dự án"
 *   bds-mat-bang          ← Mặt bằng
 *
 * @var string[]
 */
const BDS_ADMIN_BOX_ORDER = array(
	'bds_carousel',
	'bds_thong_tin',
	'bds_tong_quan',
	'bds_mo_ta',   // Ô nội dung
	'bds_mat_bang',
);

/**
 * Cột phải. Hộp không liệt kê vẫn hiện và xếp sau, nên LiteSpeed tự xuống dưới
 * mà không cần biết id của nó.
 *
 * @var string[]
 */
const BDS_ADMIN_SIDE_ORDER = array(
	'submitdiv',    // Xuất bản
	'postimagediv', // Ảnh đại diện
);

/**
 * Ô nội dung thành meta box "Mô tả dự án".
 *
 * Ngoài trang, phần này nằm GIỮA "Tổng quan dự án" và "Mặt bằng". WordPress
 * render ô nội dung trong edit-form-advanced.php chứ không qua hệ thống meta
 * box, nên nó luôn bị ghim sát dưới tiêu đề và không kéo thả được — không thể
 * xếp vào giữa. Cách duy nhất là gỡ 'editor' khỏi supports rồi dựng lại bằng
 * wp_editor() trong một meta box.
 *
 * Id trình soạn phải giữ đúng 'content': WordPress đọc $_POST['content'] trong
 * _wp_translate_postdata() rồi gán vào post_content. Đổi id là không lưu được.
 */
add_action( 'add_meta_boxes_bat-dong-san', 'bds_move_editor_to_metabox' );
function bds_move_editor_to_metabox() {
	remove_post_type_support( 'bat-dong-san', 'editor' );

	add_meta_box(
		'bds_mo_ta',
		'Mô tả dự án',
		'bds_meta_mo_ta_cb',
		'bat-dong-san',
		'normal',
		'default'
	);
}

/**
 * Nội dung meta box "Mô tả dự án".
 *
 * @param WP_Post $post Bài đang sửa.
 */
function bds_meta_mo_ta_cb( $post ) {
	?>
	<p class="description" style="margin:0 0 10px">
		Phần này hiển thị ở mục <strong>Mô tả dự án</strong>, nằm giữa Tổng quan và Mặt bằng.
	</p>
	<?php
	wp_editor(
		$post->post_content,
		'content',
		array(
			'textarea_name' => 'content',
			'textarea_rows' => 18,
			'media_buttons' => true,
			'tinymce'       => true,
			'quicktags'     => true,
		)
	);
}

/**
 * Ép thứ tự meta box.
 *
 * Chi tiết vì sao cần ép thay vì chỉ sắp lại lúc đăng ký, xem
 * hoangditru-core/includes/admin-layout.php.
 */
hdtc_admin_layout(
	'bat-dong-san',
	array(
		'normal' => BDS_ADMIN_BOX_ORDER,
		'side'   => BDS_ADMIN_SIDE_ORDER,

		/*
		 * Hộp của plugin khác xuống dưới Mặt bằng.
		 *
		 * Rank Math SEO đăng ký hộp ở normal/high (bản 1.0.280,
		 * includes/admin/metabox/class-metabox.php), mà 'high' chạy trước
		 * 'sorted' — chỗ chứa thứ tự ta ép — nên nó chen lên trên cả Hình ảnh
		 * carousel. Cờ này hạ mọi hộp không liệt kê xuống 'low', không cần biết
		 * id của hộp nào.
		 */
		'tail'   => true,
	)
);

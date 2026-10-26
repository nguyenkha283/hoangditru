<?php
/**
 * Gọn lại màn soạn Golden Visa.
 *
 * Ba việc, đều chỉ tác động lên GIAO DIỆN quản trị, không đụng tới dữ liệu:
 *   1. Dán nhãn "Câu hỏi thường gặp" ngay trên ô nội dung, vì ô đó chính là
 *      mục FAQs chứ không phải phần mô tả chung — nhìn ô trống thì không ai đoán được.
 *   2. Ép thứ tự meta box khớp thứ tự hiển thị ngoài trang.
 *   3. Ẩn hai hộp không dùng ở cột phải.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Thứ tự các mục ngoài trang, theo page-templates/template-landing.php:
 *
 *   banner  ← tiêu đề + ảnh đại diện + mô tả ngắn + meta box "Hero — Icon"
 *   advantages, benefits, conditions, process, du-an  ← các meta box
 *   faqs    ← Ô NỘI DUNG
 *
 * Ô nội dung nằm cuối trang nhưng WordPress ghim nó ở đầu màn soạn và không cho
 * kéo thả — nó không phải meta box. Đó là lý do phải dán nhãn ở bước 1 thay vì
 * xếp nó xuống cuối.
 */
const LPB_ADMIN_BOX_ORDER = array(
	'lpb_hero_icon',
	'lpb_advantages',
	'lpb_benefits',
	'lpb_conditions',
	'lpb_process',
	'lpb_du_an',
);

/**
 * 1. Nhãn trên ô nội dung.
 *
 * edit_form_after_title in ra đúng khoảng giữa dòng đường dẫn và ô soạn thảo.
 *
 * @param WP_Post $post Bài đang sửa.
 */
add_action( 'edit_form_after_title', 'lpb_admin_content_label' );
function lpb_admin_content_label( $post ) {
	if ( ! $post instanceof WP_Post || 'golden_visa' !== $post->post_type ) {
		return;
	}
	?>
	<div class="lpb-content-label">
		<span class="lpb-content-label__title">Câu hỏi thường gặp</span>
		<span class="lpb-content-label__hint">
			Mỗi câu hỏi là một tiêu đề, câu trả lời là đoạn văn ngay dưới.
			Phần này hiển thị ở mục <strong>FAQs</strong>, cuối trang.
		</span>
	</div>
	<?php
}

/**
 * 2. Ép thứ tự meta box.
 *
 * WordPress nhớ thứ tự kéo thả theo TỪNG người dùng trong user meta
 * meta-box-order_golden_visa, và thứ tự đó đè lên thứ tự đăng ký. Nên chỉ sắp
 * lại trong meta-boxes.php là không đủ: ai từng kéo một lần sẽ vẫn thấy thứ tự
 * cũ. Filter này trả về thứ tự chuẩn cho mọi người.
 *
 * Hộp nào không có trong danh sách vẫn hiện, xếp sau các hộp đã liệt kê.
 *
 * @param mixed $order Thứ tự đã lưu của người dùng.
 * @return array
 */
add_filter( 'get_user_option_meta-box-order_golden_visa', 'lpb_force_meta_box_order' );
function lpb_force_meta_box_order( $order ) {
	return array(
		'normal'   => implode( ',', LPB_ADMIN_BOX_ORDER ),
		'side'     => '',
		'advanced' => '',
	);
}

/**
 * 3. Ẩn hai hộp ở cột phải.
 *
 *   golden_visa_categorydiv  "Danh mục Golden Visa" — taxonomy vẫn đăng ký như
 *                            cũ, không template nào dùng tới nó.
 *   pageparentdiv            "Thuộc tính bài viết" — chứa ô chọn Mẫu trang.
 *
 * Ẩn ô chọn Mẫu trang chỉ an toàn vì LPB_Page_Template::load() đã đổi sang áp
 * template cho MỌI bài golden_visa, không còn phụ thuộc _wp_page_template.
 * Bỏ một trong hai thay đổi đó là bài tạo mới sẽ rơi về template mặc định.
 */
add_action( 'add_meta_boxes_golden_visa', 'lpb_hide_unused_meta_boxes', 99 );
function lpb_hide_unused_meta_boxes() {
	remove_meta_box( 'golden_visa_categorydiv', 'golden_visa', 'side' );
	remove_meta_box( 'pageparentdiv', 'golden_visa', 'side' );
}

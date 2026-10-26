<?php
/**
 * Gọn lại màn soạn Golden Visa, và xếp nó theo đúng thứ tự trang hiển thị.
 *
 * Tất cả đều chỉ tác động lên GIAO DIỆN quản trị. Không đổi định danh nào, không
 * đụng tới dữ liệu.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Thứ tự ngoài trang, theo page-templates/template-landing.php:
 *
 *   Tiêu đề + ảnh đại diện + mô tả ngắn + meta box Hero  → banner
 *   advantages, benefits, conditions, process, du-an     → các meta box
 *   Ô nội dung                                           → faqs, CUỐI trang
 *
 * Ô nội dung vốn bị WordPress ghim ngay dưới tiêu đề và không kéo thả được, nên
 * nó được chuyển thành meta box lpb_faqs để xếp xuống cuối cho khớp (xem
 * lpb_move_editor_to_metabox bên dưới).
 *
 * @var string[]
 */
const LPB_ADMIN_BOX_ORDER = array(
	'lpb_hero_icon',   // Hero — Icon góc dưới trái
	'postexcerpt',     // Mô tả ngắn, banner.php đọc qua get_the_excerpt()
	'lpb_advantages',
	'lpb_benefits',
	'lpb_conditions',
	'lpb_process',
	'lpb_du_an',
	'lpb_faqs',        // Ô nội dung
);

/**
 * Cột phải. Hộp nào không liệt kê vẫn hiện, xếp sau — LiteSpeed nằm trong số đó,
 * nên không cần biết id của nó để đẩy Ảnh đại diện lên trên.
 *
 * @var string[]
 */
const LPB_ADMIN_SIDE_ORDER = array(
	'submitdiv',    // Xuất bản
	'postimagediv', // Ảnh đại diện
);

/**
 * Ô nội dung thành meta box "Câu hỏi thường gặp", đặt cuối.
 *
 * WordPress render ô nội dung trong edit-form-advanced.php chứ không qua hệ
 * thống meta box, nên nó luôn nằm sát dưới tiêu đề và không kéo thả được. Cách
 * duy nhất để xếp nó xuống cuối là gỡ 'editor' khỏi supports rồi tự dựng lại
 * bằng wp_editor() trong một meta box.
 *
 * Id của trình soạn phải giữ đúng là 'content': WordPress đọc $_POST['content']
 * trong _wp_translate_postdata() rồi gán vào post_content. Đổi id là không lưu
 * được nội dung.
 *
 * remove_post_type_support gọi trong add_meta_boxes_* là kịp: lúc đó
 * edit-form-advanced.php mới gọi register_and_do_post_meta_boxes(), phần HTML
 * kiểm tra post_type_supports('editor') chạy sau.
 */
add_action( 'add_meta_boxes_golden_visa', 'lpb_move_editor_to_metabox' );
function lpb_move_editor_to_metabox() {
	remove_post_type_support( 'golden_visa', 'editor' );

	add_meta_box(
		'lpb_faqs',
		'Câu hỏi thường gặp',
		'lpb_mb_faqs',
		'golden_visa',
		'normal',
		'low'
	);
}

/**
 * Nội dung meta box FAQs.
 *
 * @param WP_Post $post Bài đang sửa.
 */
function lpb_mb_faqs( $post ) {
	?>
	<p class="description" style="margin:0 0 10px">
		Mỗi câu hỏi là một tiêu đề, câu trả lời là đoạn văn ngay dưới.
		Phần này hiển thị ở mục <strong>FAQs</strong>, cuối trang.
	</p>
	<?php
	wp_editor(
		$post->post_content,
		'content',
		array(
			'textarea_name' => 'content',
			'textarea_rows' => 20,
			'media_buttons' => true,
			'tinymce'       => true,
			'quicktags'     => true,
		)
	);
}

/**
 * Ép thứ tự meta box và hiện hộp "Mô tả ngắn".
 *
 * banner.php đọc mô tả ngắn qua get_the_excerpt() để in dưới tiêu đề, mà
 * WordPress ẩn postexcerpt theo mặc định — ẩn thì người nhập không biết có ô đó.
 *
 * Chi tiết vì sao phải ép thứ tự, và vì sao bỏ ẩn không được trả mảng rỗng, xem
 * hoangditru-core/includes/admin-layout.php.
 */
hdtc_admin_layout(
	'golden_visa',
	array(
		'normal' => LPB_ADMIN_BOX_ORDER,
		'side'   => LPB_ADMIN_SIDE_ORDER,
		'show'   => array( 'postexcerpt' ),
	)
);

/**
 * Ẩn hai hộp không dùng ở cột phải.
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

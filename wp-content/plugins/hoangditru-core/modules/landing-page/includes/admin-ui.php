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
 * Ép thứ tự meta box.
 *
 * WordPress nhớ thứ tự kéo thả theo TỪNG người dùng trong user meta
 * meta-box-order_golden_visa, và thứ tự đó đè lên thứ tự đăng ký. Nên chỉ sắp
 * lại lúc đăng ký là không đủ: ai từng kéo một lần sẽ vẫn thấy thứ tự cũ.
 *
 * @param mixed $order Thứ tự đã lưu của người dùng.
 * @return array
 */
add_filter( 'get_user_option_meta-box-order_golden_visa', 'lpb_force_meta_box_order' );
function lpb_force_meta_box_order( $order ) {
	return array(
		'normal'   => implode( ',', LPB_ADMIN_BOX_ORDER ),
		'side'     => implode( ',', LPB_ADMIN_SIDE_ORDER ),
		'advanced' => '',
	);
}

/**
 * Hiện hộp "Mô tả ngắn".
 *
 * WordPress ẩn postexcerpt theo mặc định, trong khi banner.php đọc nó qua
 * get_the_excerpt() để in mô tả dưới tiêu đề — ẩn thì người nhập không biết có
 * ô đó.
 *
 * Phải dựng lại danh sách ẩn mặc định khi người dùng chưa có tuỳ chọn riêng:
 * get_hidden_meta_boxes() chỉ dùng danh sách mặc định khi giá trị KHÔNG phải
 * mảng, nên trả về mảng rỗng sẽ bỏ ẩn tất cả, lôi cả Trường tuỳ biến và Thảo
 * luận ra màn hình.
 *
 * @param mixed $hidden Danh sách hộp đang ẩn.
 * @return string[]
 */
add_filter( 'get_user_option_metaboxhidden_golden_visa', 'lpb_show_excerpt_box' );
function lpb_show_excerpt_box( $hidden ) {
	if ( ! is_array( $hidden ) ) {
		// Đúng danh sách WordPress ẩn mặc định ở màn soạn bài.
		$hidden = array(
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

	return array_values( array_diff( $hidden, array( 'postexcerpt', 'postimagediv' ) ) );
}

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

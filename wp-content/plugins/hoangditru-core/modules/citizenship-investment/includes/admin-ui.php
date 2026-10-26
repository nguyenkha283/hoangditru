<?php
/**
 * Xếp màn soạn "Đầu tư quốc tịch" theo đúng thứ tự trang hiển thị.
 *
 * Chỉ tác động lên giao diện quản trị. Không đổi định danh nào, không đụng dữ liệu.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thứ tự ngoài trang, theo page-templates/single-citizenship-invest.php:
 *
 *   page-title  ← tiêu đề + meta box "Hero — Icon góc dưới trái"
 *   carousel    ← meta box Carousel
 *   page-desc   ← MÔ TẢ NGẮN, page-desc.php đọc qua get_the_excerpt()
 *   features, why-us, du-an, programs, news  ← các meta box
 *
 * CPT này không có ô nội dung ('editor' không nằm trong supports), nên không
 * gặp chuyện ô nội dung bị ghim trên đầu như bên Golden Visa.
 *
 * @var string[]
 */
const CI_ADMIN_BOX_ORDER = array(
	'ci_page_overlay', // Hero — Icon góc dưới trái
	'ci_carousel',
	'postexcerpt',     // Mô tả ngắn
	'ci_features',
	'ci_why_us',
	'ci_du_an',
	'ci_programs',
	'ci_news',
);

/**
 * Cột phải. Hộp không liệt kê vẫn hiện và xếp sau, nên LiteSpeed tự xuống dưới
 * mà không cần biết id của nó.
 *
 * @var string[]
 */
const CI_ADMIN_SIDE_ORDER = array(
	'submitdiv',    // Xuất bản
	'postimagediv', // Ảnh đại diện
);

hdtc_admin_layout(
	'citizenship_invest',
	array(
		'normal' => CI_ADMIN_BOX_ORDER,
		'side'   => CI_ADMIN_SIDE_ORDER,
		'show'   => array( 'postexcerpt' ),

		// Hộp Rank Math (normal/high) và hộp lạ khác xuống sau Tin tức. Vì sao
		// cần, xem hdtc_demote_unlisted_boxes() trong includes/admin-layout.php.
		'tail'   => true,
	)
);

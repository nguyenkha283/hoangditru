<?php
/**
 * Plugin Name: Hoàng Di Trú Core
 * Description: Toàn bộ chức năng lõi của site, gộp từ 8 plugin rời. Đây là code của site, không phải tiện ích thêm vào — không thiết kế để bật/tắt từng phần.
 * Version:     1.2.0
 * Author:      Hoàng Di Trú
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: hoangditru-core
 *
 * ── Vì sao gộp ──────────────────────────────────────────────────────────────
 * 8 plugin này là lõi hệ thống chứ không phải tính năng tuỳ chọn: tắt bất kỳ
 * cái nào là site hỏng (mất nav, mất footer, mất trang chủ, mất CPT). Gộp lại
 * thì không ai tắt nhầm được, và deploy chỉ còn một thư mục.
 *
 * ── Điều TUYỆT ĐỐI không được đổi ───────────────────────────────────────────
 * Mọi định danh dưới đây đang nằm trong database. Đổi là mất dữ liệu đang có:
 *
 *   post type      bat-dong-san, citizenship_invest, golden_visa
 *   taxonomy       bds_category, golden_visa_category
 *   option         cim_contact_info, smart_breadcrumb_options, trang_chu_settings
 *   capability     hdt_manage_site_content  (gác trang Trang chủ + Thông tin liên hệ)
 *   page template  tct-trang-chu
 *                  landing-page-plugin/page-templates/template-landing.php
 *   16 shortcode   cim_*, youtube_embed
 *   post meta      _bds_*, s3_title_orange, s4_title_orange, ...
 *
 * Chuỗi template của landing page CÒN GIỮ TÊN THƯ MỤC CŨ. Đó là cố ý: nó là
 * khoá lưu trong _wp_page_template của các post golden_visa, không phải đường
 * dẫn file. Đường dẫn file thật lấy từ hằng LPB_DIR. Đừng "dọn" chuỗi đó.
 *
 * ── Đường dẫn trong module ──────────────────────────────────────────────────
 * Các module vẫn dùng plugin_dir_path(__FILE__) và plugin_dir_url(__FILE__) như
 * cũ, không phải sửa: plugin_dir_path chỉ là dirname() nên luôn đúng, còn
 * plugin_dir_url tính theo wp-content/plugins nên cũng đúng khi plugin nằm ở
 * vị trí chuẩn. Nếu sau này nạp plugin từ nơi khác (ví dụ wp-content/app qua
 * Git) thì phải thêm filter 'plugins_url', xem README.
 */

defined( 'ABSPATH' ) || exit;

define( 'HDTC_VERSION', '1.2.0' );
define( 'HDTC_DIR', plugin_dir_path( __FILE__ ) );
define( 'HDTC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Các module, nạp theo thứ tự này.
 *
 * phan-quyen đứng đầu vì nó định nghĩa hằng HDTQ_CAP_SITE_SETTINGS mà
 * contact-info-manager và trang-chu dùng để gác trang cài đặt của chúng.
 *
 * contact-info-manager đứng trước trang-chu và cim-footer vì hai cái đó đọc dữ
 * liệu liên hệ của nó. Thực tế việc đọc diễn ra trong hook nên thứ tự không
 * bắt buộc, nhưng để vậy cho dễ hiểu.
 *
 * @var array<string,string> thư mục module => file chính
 */
const HDTC_MODULES = array(
	'phan-quyen'             => 'phan-quyen.php',
	'bang-dieu-khien'        => 'bang-dieu-khien.php',
	'contact-info-manager'   => 'contact-info-manager.php',
	'cim-footer'             => 'cim-footer.php',
	'trang-chu'              => 'trang-chu.php',
	'bat-dong-san'           => 'bat-dong-san.php',
	'landing-page'           => 'landing-page-plugin.php',
	'citizenship-investment' => 'citizenship-investment.php',
	'smart-breadcrumb'       => 'smart-breadcrumb.php',
	'snera-youtube-embed'    => 'snera-youtube-embed.php',
);

/**
 * Hàm đặc trưng của từng plugin cũ. Nếu một trong số này đã tồn tại nghĩa là
 * plugin rời tương ứng vẫn đang bật — nạp thêm bản gộp sẽ "cannot redeclare
 * function" và trắng trang. Thà không nạp và báo rõ còn hơn.
 *
 * @var array<string,string> hàm => tên plugin cũ
 */
const HDTC_LEGACY_SENTINELS = array(
	'cim_activate'                 => 'Contact Info Manager',
	'cimf_enqueue'                 => 'CIM Footer',
	'tc_register_template'         => 'Trang Chủ',
	'bds_activate'                 => 'Bất Động Sản',
	'lpb_register_golden_visa_cpt' => 'Landing Page Builder',
	'ci_register_cpt'              => 'Citizenship Investment',
	'sbc_defaults'                 => 'Smart Breadcrumb',
	'snera_yt_extract_id'          => 'Snera YouTube Embed',
);

/**
 * Những plugin cũ nào còn đang bật.
 *
 * @return string[]
 */
function hdtc_legacy_still_active() {
	$found = array();

	foreach ( HDTC_LEGACY_SENTINELS as $fn => $label ) {
		if ( function_exists( $fn ) ) {
			$found[] = $label;
		}
	}

	return $found;
}

$hdtc_conflict = hdtc_legacy_still_active();

if ( $hdtc_conflict ) {
	add_action(
		'admin_notices',
		static function () use ( $hdtc_conflict ) {
			printf(
				'<div class="notice notice-error"><p><strong>Hoàng Di Trú Core chưa chạy.</strong> Hãy tắt các plugin cũ sau rồi tải lại trang: %s</p></div>',
				esc_html( implode( ', ', $hdtc_conflict ) )
			);
		}
	);

	return;
}

// Hàm dùng chung cho template của mọi module: hdtc_img().
require_once HDTC_DIR . 'includes/img.php';

// Xếp màn soạn bài: hdtc_admin_layout(). Phải nạp TRƯỚC module vì module gọi nó
// ngay lúc nạp.
require_once HDTC_DIR . 'includes/admin-layout.php';

foreach ( HDTC_MODULES as $hdtc_dir => $hdtc_entry ) {
	$hdtc_file = HDTC_DIR . 'modules/' . $hdtc_dir . '/' . $hdtc_entry;

	if ( file_exists( $hdtc_file ) ) {
		require_once $hdtc_file;
	}
}

unset( $hdtc_conflict, $hdtc_dir, $hdtc_entry, $hdtc_file );

/**
 * Kích hoạt.
 *
 * Các module vẫn còn register_activation_hook của riêng chúng, nhưng chúng gắn
 * vào file module chứ không phải file chính của plugin nên không bao giờ chạy.
 * Vì vậy việc của chúng được làm lại ở đây. Không module nào tạo bảng riêng
 * (đã kiểm: không có CREATE TABLE hay dbDelta), nên chỉ cần đăng ký post type
 * rồi nạp lại rewrite, cộng một option mặc định.
 */
register_activation_hook( __FILE__, 'hdtc_activate' );
function hdtc_activate() {
	foreach ( array( 'bds_register_cpt', 'lpb_register_golden_visa_cpt', 'ci_register_cpt' ) as $fn ) {
		if ( function_exists( $fn ) ) {
			$fn();
		}
	}

	if ( defined( 'CIM_OPTION_KEY' ) && function_exists( 'cim_default_data' ) && ! get_option( CIM_OPTION_KEY ) ) {
		add_option( CIM_OPTION_KEY, cim_default_data() );
	}

	// Quyền của role Biên tập viên. Hook 'init' của module cũng tự chạy việc
	// này, nhưng làm ngay lúc kích hoạt thì quyền đúng từ request đầu tiên.
	if ( function_exists( 'hdtq_sync_role' ) ) {
		hdtq_sync_role();
		update_option( HDTQ_SYNC_OPTION, HDTQ_VERSION );
	}

	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'hdtc_deactivate' );
function hdtc_deactivate() {
	flush_rewrite_rules();
}

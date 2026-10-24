<?php
/**
 * Plugin Name: Hoàng Di Trú WebP
 * Description: Chuyển toàn bộ ảnh (đang có và upload sau này) sang WebP, giữ ảnh gốc; phục vụ WebP qua srcset theo kích thước; lightbox chỉ tải ảnh lớn khi bấm. Tắt WebP của LiteSpeed Cache. Bản riêng cho hoangditru — template đã được sửa tay để in ảnh bằng API của WordPress.
 * Version:     1.0.0
 * Author:      Hoàng Di Trú
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wsl
 *
 * ── Hai bản của cùng một plugin ─────────────────────────────────────────────
 * hoangditru-webp (bản này)   bám vào API ảnh của WordPress. Chạy được vì mọi
 *                             <img> trong template của site đã sửa tay sang
 *                             wp_get_attachment_image() / hdtc_img().
 * webp-srcset-lightbox        lọc HTML của cả trang bằng output buffer, dùng cho
 *                             site có template không sửa được. Nằm ở
 *                             plugins-khac/ trong repo, KHÔNG deploy lên đây.
 *
 * includes/{convert,rewrite,litespeed,lightbox,admin}.php và assets/ giống hệt
 * nhau giữa hai bản — scripts/webp.test.php kiểm điều đó. Chỉ file chính và
 * includes/api.php là riêng của bản này.
 *
 * Bật cả hai cùng lúc thì bản bật sau tự đứng ngoài và báo trong admin.
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WSL_VERSION' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>Hoàng Di Trú WebP chưa chạy:</strong> một bản khác của plugin WebP (webp-srcset-lightbox) đang bật. Chỉ bật một trong hai.</p></div>';
		}
	);
	return;
}

define( 'WSL_VERSION', '1.0.0' );
define( 'WSL_FILE', __FILE__ );
define( 'WSL_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSL_URL', plugin_dir_url( __FILE__ ) );

require_once WSL_DIR . 'includes/convert.php';
require_once WSL_DIR . 'includes/rewrite.php';
require_once WSL_DIR . 'includes/litespeed.php';
require_once WSL_DIR . 'includes/lightbox.php';
require_once WSL_DIR . 'includes/admin.php';
require_once WSL_DIR . 'includes/api.php';

/**
 * Lightbox mới chạy toàn site, TRỪ phần mặt bằng của trang bất động sản: phần
 * đó đã có lightbox riêng (#bds-lightbox, modules/bat-dong-san/assets/js/main.js)
 * với chú thích theo từng tầng. Thumbnail điều hướng của slider BĐS cũng loại,
 * vì bấm vào đó là để chuyển slide.
 *
 * @param string[] $list
 * @return string[]
 */
function hdtw_lightbox_exclude( $list ) {
	$list[] = '.bds-lb-trigger';
	$list[] = '.bds-slider-nav'; // thumbnail điều hướng của slider, bấm là chuyển slide
	$list[] = '.bds-mb-gallery';
	$list[] = '.bds-lightbox';

	return $list;
}
add_filter( 'wsl_lightbox_exclude', 'hdtw_lightbox_exclude' );

register_activation_hook( __FILE__, 'wsl_lscwp_disable_persist' );
register_deactivation_hook( __FILE__, 'wsl_lscwp_purge' );

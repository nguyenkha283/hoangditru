<?php
/**
 * Plugin Name: WebP Srcset Lightbox
 * Description: Chuyển toàn bộ ảnh (đang có và upload sau này) sang WebP, giữ ảnh gốc; lọc HTML của cả trang để phục vụ WebP và thêm srcset theo kích thước; lightbox chỉ tải ảnh lớn khi bấm. Tắt WebP của LiteSpeed Cache. Không cần sửa theme.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wsl
 * License:     GPL-2.0-or-later
 *
 * ── Hai bản của cùng một plugin ─────────────────────────────────────────────
 * webp-srcset-lightbox (bản này)  lọc HTML cả trang bằng output buffer
 *                                 (includes/buffer.php). Dùng cho site có theme
 *                                 in <img src="…"> tự viết mà không sửa được.
 * hoangditru-webp                 bám vào API ảnh của WordPress, dùng khi mọi
 *                                 template đã in ảnh bằng wp_get_attachment_image().
 *
 * includes/{convert,rewrite,litespeed,lightbox,admin}.php và assets/ giống hệt
 * nhau giữa hai bản. Chỉ file chính và includes/buffer.php là riêng của bản này.
 *
 * Bật cả hai cùng lúc thì bản bật sau tự đứng ngoài và báo trong admin.
 *
 * ── Tuỳ biến bằng filter ────────────────────────────────────────────────────
 *   wsl_quality                   chất lượng WebP, mặc định 82
 *   wsl_lightbox_exclude          mảng bộ chọn CSS không dùng lightbox
 *                                 (mặc định .no-lightbox, [data-no-lightbox])
 *   wsl_lightbox_enabled          false để tắt lightbox
 *   wsl_lightbox_min_width        bản lớn phải rộng từ bấy nhiêu px, mặc định 600
 *   wsl_upload_hosts              thêm host CDN đang phục vụ uploads
 *   wsl_buffer_enabled            false để không lọc một trang nào đó
 * Thuộc tính data-no-webp trên <img> để bỏ qua riêng ảnh đó.
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WSL_VERSION' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>WebP Srcset Lightbox chưa chạy:</strong> một bản khác của plugin này (hoangditru-webp) đang bật. Chỉ bật một trong hai.</p></div>';
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
require_once WSL_DIR . 'includes/buffer.php';

register_activation_hook( __FILE__, 'wsl_lscwp_disable_persist' );
register_deactivation_hook( __FILE__, 'wsl_lscwp_purge' );

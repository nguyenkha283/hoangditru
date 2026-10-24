<?php
/**
 * Bản dùng cho site khác: lọc HTML của cả trang.
 *
 * Mở output buffer ở 'template_redirect', và trước khi HTML rời PHP thì chạy
 * wsl_rewrite_html() trên toàn bộ: mọi <img>/<source> trỏ vào uploads được đổi
 * sang WebP, ảnh thư viện Media chưa có srcset được thêm srcset/sizes, ảnh có bản
 * lớn hơn được gắn data-full cho lightbox, và url(...) trong style="" cũng đổi.
 *
 * Nhờ vậy không phải sửa template nào — đổi lại tốn thêm một lượt regex trên
 * HTML và tối đa hai truy vấn DB mỗi trang. Có LiteSpeed Cache thì HTML đã lọc
 * được cache lại, nên cái giá đó chỉ trả một lần cho mỗi trang.
 *
 * Buffer của LiteSpeed được mở sớm hơn (lúc nạp plugin), nên buffer này nằm
 * bên trong: HTML được lọc xong rồi LiteSpeed mới lưu cache.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có nên lọc trang này không.
 *
 * @return bool
 */
function wsl_buffer_wanted() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}

	if ( is_feed() || is_embed() || is_robots() || is_trackback() ) {
		return false;
	}

	return (bool) apply_filters( 'wsl_buffer_enabled', true );
}

/**
 * @param string $html
 * @return string
 */
function wsl_buffer_filter( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	// Chỉ HTML. Sitemap XML, JSON… đi qua nguyên vẹn.
	foreach ( headers_list() as $h ) {
		if ( 0 === stripos( $h, 'content-type:' ) && false === stripos( $h, 'html' ) ) {
			return $html;
		}
	}

	try {
		return wsl_rewrite_html( $html );
	} catch ( \Throwable $e ) {
		// Lỗi ở đây không được làm trắng trang: trả HTML gốc.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'webp-srcset-lightbox: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
		return $html;
	}
}

function wsl_buffer_start() {
	if ( wsl_buffer_wanted() ) {
		ob_start( 'wsl_buffer_filter' );
	}
}
add_action( 'template_redirect', 'wsl_buffer_start', 1 );

<?php
/**
 * Nạp lightbox ở frontend.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin. Xem convert.php.
 *
 * JS ~3 KB, CSS ~2 KB, nạp defer ở cuối trang nên không chặn render. Không cần
 * jQuery.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bộ chọn CSS của những vùng KHÔNG dùng lightbox này. Bản nào cần loại trừ thêm
 * (ví dụ lightbox riêng sẵn có của theme) thì thêm qua filter 'wsl_lightbox_exclude'.
 *
 * @return string
 */
function wsl_lightbox_exclude() {
	$list = (array) apply_filters( 'wsl_lightbox_exclude', array( '.no-lightbox', '[data-no-lightbox]' ) );

	return implode( ', ', array_filter( array_map( 'trim', $list ) ) );
}

function wsl_lightbox_enqueue() {
	if ( ! apply_filters( 'wsl_lightbox_enabled', true ) ) {
		return;
	}

	wp_enqueue_style( 'wsl-lightbox', WSL_URL . 'assets/lightbox.css', array(), WSL_VERSION );
	wp_enqueue_script(
		'wsl-lightbox',
		WSL_URL . 'assets/lightbox.js',
		array(),
		WSL_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'wsl-lightbox',
		'wslLightbox',
		array(
			'exclude'  => wsl_lightbox_exclude(),
			'minWidth' => (int) apply_filters( 'wsl_lightbox_min_display_width', 120 ),
			'i18n'     => array(
				'dialog' => __( 'Xem ảnh', 'wsl' ),
				'close'  => __( 'Đóng', 'wsl' ),
				'prev'   => __( 'Ảnh trước', 'wsl' ),
				'next'   => __( 'Ảnh sau', 'wsl' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wsl_lightbox_enqueue' );

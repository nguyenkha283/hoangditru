<?php
/**
 * Plugin Name: Snera YouTube Embed (TinyMCE)
 * Description: Thêm nút "YouTube" vào trình soạn thảo Classic Editor (TinyMCE). Người dùng dán link YouTube, plugin chèn shortcode [youtube_embed] và hiển thị video responsive trong nội dung.
 * Version: 1.0.0
 * Author: Snera
 * License: GPLv2 or later
 * Text Domain: snera-youtube-embed
 *
 * @package Snera_YouTube_Embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Chặn truy cập trực tiếp.
}

define( 'SNERA_YT_VERSION', '1.0.0' );
define( 'SNERA_YT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Tách video ID từ mọi định dạng link YouTube phổ biến.
 * Hỗ trợ: watch?v=, youtu.be/, /embed/, /shorts/, /v/, nocookie, hoặc ID thuần.
 *
 * @param string $url Link hoặc ID.
 * @return string Video ID 11 ký tự, hoặc '' nếu không hợp lệ.
 */
function snera_yt_extract_id( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	// Đã là ID thuần.
	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $url ) ) {
		return $url;
	}

	$patterns = array(
		'/youtu\.be\/([A-Za-z0-9_-]{11})/',
		'/youtube(?:-nocookie)?\.com\/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})/',
		'/youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{11})/',
		'/youtube(?:-nocookie)?\.com\/shorts\/([A-Za-z0-9_-]{11})/',
		'/youtube(?:-nocookie)?\.com\/v\/([A-Za-z0-9_-]{11})/',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $url, $m ) ) {
			return $m[1];
		}
	}

	return '';
}

/**
 * Đăng ký CSS frontend (chỉ nạp khi shortcode được dùng).
 */
function snera_yt_register_assets() {
	wp_register_style(
		'snera-youtube-embed',
		SNERA_YT_URL . 'css/frontend.css',
		array(),
		SNERA_YT_VERSION
	);
}
add_action( 'init', 'snera_yt_register_assets' );

/**
 * Shortcode [youtube_embed url="..."] → iframe responsive 16:9.
 *
 * @param array $atts Thuộc tính shortcode.
 * @return string HTML nhúng, hoặc '' nếu link không hợp lệ.
 */
function snera_yt_shortcode( $atts ) {
	$atts = shortcode_atts(
		array( 'url' => '' ),
		$atts,
		'youtube_embed'
	);

	$video_id = snera_yt_extract_id( $atts['url'] );
	if ( '' === $video_id ) {
		return '';
	}

	wp_enqueue_style( 'snera-youtube-embed' );

	$src = 'https://www.youtube-nocookie.com/embed/' . $video_id;

	return sprintf(
		'<div class="snera-yt-embed"><iframe src="%s" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen loading="lazy"></iframe></div>',
		esc_url( $src )
	);
}
add_shortcode( 'youtube_embed', 'snera_yt_shortcode' );

/**
 * Gắn nút + plugin JS vào TinyMCE (Classic Editor) cho user có quyền & bật rich editing.
 */
function snera_yt_init_tinymce() {
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	if ( 'true' !== get_user_option( 'rich_editing' ) ) {
		return;
	}

	add_filter( 'mce_external_plugins', 'snera_yt_mce_external_plugin' );
	add_filter( 'mce_buttons', 'snera_yt_mce_button' );
}
add_action( 'admin_head', 'snera_yt_init_tinymce' );

/**
 * Khai báo file JS của TinyMCE plugin.
 *
 * @param array $plugins Danh sách plugin ngoài.
 * @return array
 */
function snera_yt_mce_external_plugin( $plugins ) {
	$plugins['snera_yt_button'] = SNERA_YT_URL . 'js/tinymce-plugin.js?ver=' . SNERA_YT_VERSION;
	return $plugins;
}

/**
 * Thêm id nút vào thanh công cụ TinyMCE.
 *
 * @param array $buttons Danh sách nút.
 * @return array
 */
function snera_yt_mce_button( $buttons ) {
	$buttons[] = 'snera_yt_button';
	return $buttons;
}

<?php
/**
 * 301 từ slug cũ (có "khai-di-tru") sang slug mới.
 *
 * WordPress tự lo việc này qua _wp_old_slug, nhưng CHỈ với post type không phân
 * cấp — wp_check_for_changed_slugs() và wp_old_slug_redirect() đều bỏ qua Trang
 * và Bất động sản (hierarchical). Term thì WordPress không có gì cả. Hàm này lấp
 * hai chỗ đó, chạy sau wp_old_slug_redirect() (priority 10).
 *
 * Chỉ truy vấn khi đoạn cuối URL có dạng tên thương hiệu cũ, nên 404 bình
 * thường không tốn thêm truy vấn nào.
 *
 * Phần này phải giữ lại kể cả khi đã thay xong: link cũ ngoài internet vẫn còn.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'hdtth_chuyen_slug_cu', 20 );
function hdtth_chuyen_slug_cu() {
	if ( ! is_404() ) {
		return;
	}

	$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ); // phpcs:ignore
	$path = preg_replace( '~/page/\d+/?$~', '', trim( $path, '/' ) );
	$phan = explode( '/', $path );
	$slug = strtolower( (string) end( $phan ) );

	if ( '' === $slug ) {
		return;
	}

	$n = 0;
	hdtth_doi_ten( rawurldecode( $slug ), $n );
	if ( ! $n ) {
		return;
	}

	global $wpdb;

	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->postmeta} m
			   JOIN {$wpdb->posts} p ON p.ID = m.post_id
			  WHERE m.meta_key = '_wp_old_slug' AND m.meta_value = %s AND p.post_status = 'publish'
			  ORDER BY p.ID DESC LIMIT 1",
			$slug
		)
	);

	if ( $id ) {
		wp_safe_redirect( get_permalink( $id ), 301 );
		exit;
	}

	$term = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT m.term_id, tt.taxonomy FROM {$wpdb->termmeta} m
			   JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = m.term_id
			  WHERE m.meta_key = %s AND m.meta_value = %s LIMIT 1",
			HDTTH_TERM_OLD_SLUG,
			$slug
		)
	);

	if ( $term ) {
		$link = get_term_link( (int) $term->term_id, $term->taxonomy );
		if ( ! is_wp_error( $link ) ) {
			wp_safe_redirect( $link, 301 );
			exit;
		}
	}
}

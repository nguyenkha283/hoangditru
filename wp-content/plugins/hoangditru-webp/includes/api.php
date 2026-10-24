<?php
/**
 * Bản hoangditru: đổi sang WebP ngay trong API ảnh của WordPress.
 *
 * Template của theme và hoangditru-core đã được sửa tay để in ảnh bằng
 * wp_get_attachment_image() (có srcset/sizes sẵn của WordPress) thay vì
 * <img src="…"> tự viết. Nên ở đây chỉ cần bám vào ba chỗ WordPress dựng URL:
 *
 *   wp_get_attachment_image_src   src của mọi ảnh, kể cả get_the_post_thumbnail_url()
 *                                 và wp_get_attachment_image_url() dùng làm ảnh nền
 *   wp_calculate_image_srcset     từng ứng viên trong srcset
 *   wp_get_attachment_image_attributes  gắn data-full cho lightbox
 *
 * Ảnh trong nội dung bài viết thì đi qua wsl_rewrite_html() ở 'the_content'.
 * Không lọc cả trang bằng output buffer — đó là cách của bản dùng cho site khác.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có nên trả URL WebP ở request này không.
 *
 * Không: trong wp-admin (thư viện Media, trình soạn thảo — chèn ảnh vào bài phải
 * là URL gốc), REST API (trình soạn thảo khối), AJAX gọi từ trang admin, feed
 * RSS, và trong <head> (og:image, schema của Rank Math: một số trình đọc link
 * như Zalo/Facebook cũ không nhận WebP).
 *
 * @return bool
 */
function wsl_api_active() {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( wp_doing_ajax() ) {
		$ref = (string) wp_get_raw_referer();
		if ( '' === $ref || false !== strpos( $ref, '/wp-admin/' ) ) {
			return false;
		}
	} elseif ( is_admin() ) {
		return false;
	}

	if ( doing_action( 'wp_head' ) || ( function_exists( 'is_feed' ) && is_feed() ) ) {
		return false;
	}

	return (bool) apply_filters( 'wsl_api_enabled', true );
}

/**
 * @param array|false  $image [url, width, height, is_intermediate]
 * @param int          $id
 * @param string|int[] $size
 * @param bool         $icon
 * @return array|false
 */
function wsl_api_image_src( $image, $id, $size, $icon ) {
	if ( is_array( $image ) && ! $icon && ! empty( $image[0] ) && wsl_api_active() ) {
		$image[0] = wsl_webp_url( $image[0], (int) $id );
	}

	return $image;
}
add_filter( 'wp_get_attachment_image_src', 'wsl_api_image_src', 20, 4 );

/**
 * @param array  $sources width => [url, descriptor, value]
 * @param array  $size_array
 * @param string $image_src
 * @param array  $image_meta
 * @param int    $id
 * @return array
 */
function wsl_api_srcset( $sources, $size_array, $image_src, $image_meta, $id ) {
	if ( is_array( $sources ) && wsl_api_active() ) {
		foreach ( $sources as $w => $source ) {
			if ( ! empty( $source['url'] ) ) {
				$sources[ $w ]['url'] = wsl_webp_url( $source['url'], (int) $id );
			}
		}
	}

	return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'wsl_api_srcset', 20, 5 );

/**
 * Gắn data-full (bản lớn, WebP) để lightbox tải khi người dùng bấm.
 *
 * Gắn cho mọi ảnh có bản lớn hơn rõ rệt; lightbox.js tự bỏ qua ảnh nằm trong
 * link tới trang khác (thẻ bài, thẻ dự án) và ảnh hiển thị nhỏ (icon, avatar).
 *
 * @param array        $attr
 * @param WP_Post      $attachment
 * @param string|int[] $size
 * @return array
 */
function wsl_api_attributes( $attr, $attachment, $size ) {
	if ( isset( $attr['data-full'] ) || ! wsl_api_active() || ! apply_filters( 'wsl_lightbox_enabled', true ) ) {
		return $attr;
	}

	$shown = wp_get_attachment_image_src( $attachment->ID, $size );
	$full  = $shown ? wsl_full_image( $attachment->ID, wp_get_attachment_metadata( $attachment->ID ), (int) $shown[1] ) : '';

	if ( $full ) {
		$attr['data-full'] = $full;
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wsl_api_attributes', 20, 3 );

/**
 * Ảnh trong nội dung bài: chạy sau wp_filter_content_tags (priority 12), lúc
 * đó ảnh có class wp-image-N đã có srcset; ở đây đổi src sang WebP, thêm srcset
 * cho ảnh dán URL tay, và gắn data-full.
 *
 * @param string $html
 * @return string
 */
function wsl_api_content( $html ) {
	return wsl_api_active() ? wsl_rewrite_html( $html ) : $html;
}
add_filter( 'the_content', 'wsl_api_content', 20 );
add_filter( 'widget_text_content', 'wsl_api_content', 20 );
add_filter( 'widget_block_content', 'wsl_api_content', 20 );

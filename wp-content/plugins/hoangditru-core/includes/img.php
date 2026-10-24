<?php
/**
 * In <img> cho ảnh trong thư viện Media, có srcset/sizes/width/height.
 *
 * Trước đây các template tự viết <img src="<?php echo $url ?>"> — trình duyệt
 * chỉ có MỘT file để tải, thường là 'large' hoặc 'full', kể cả trên điện thoại
 * hay trong thẻ rộng 290px. Hàm này giao việc cho wp_get_attachment_image() của
 * WordPress, nên có srcset theo mọi size đã tạo, kèm width/height chống nhảy
 * layout.
 *
 * Không phụ thuộc plugin Hoàng Di Trú WebP. Plugin đó (nếu bật) bám vào chính
 * API này để đổi URL sang .webp và gắn data-full cho lightbox; tắt nó thì ảnh
 * vẫn ra đúng, chỉ là JPEG/PNG.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param int|string   $image ID attachment, hoặc URL (vài meta box cũ lưu URL
 *                            thay vì ID — lpb_benefits, lpb_hero_icon, …).
 * @param string|int[] $size  Size ảnh của WordPress. null với URL = đúng size mà
 *                            URL đó trỏ tới (URL của bản 150x150 thì in bản
 *                            150x150, không tự đổi sang size khác).
 * @param array        $attr  Thuộc tính thêm: class, alt, loading, sizes, …
 *                            'loading' => false để bỏ hẳn lazy-load (ảnh đầu trang).
 * @return string HTML, hoặc '' nếu không có ảnh.
 */
function hdtc_img( $image, $size = null, $attr = array() ) {
	if ( empty( $image ) ) {
		return '';
	}

	$id = is_numeric( $image ) ? (int) $image : hdtc_attachment_id( (string) $image );

	if ( $id ) {
		if ( null === $size ) {
			$size = is_numeric( $image ) ? 'large' : hdtc_size_of_url( $id, (string) $image );
		}
		$html = wp_get_attachment_image( $id, $size, false, $attr );
		if ( $html ) {
			return $html;
		}
	}

	if ( is_numeric( $image ) ) {
		return '';
	}

	// URL không thuộc thư viện Media (ảnh đã xoá, ảnh ngoài site): in như cũ.
	unset( $attr['sizes'], $attr['srcset'] );
	if ( array_key_exists( 'loading', $attr ) && ! $attr['loading'] ) {
		unset( $attr['loading'] );
	}

	$html = '<img src="' . esc_url( $image ) . '"';
	foreach ( array_merge( array( 'alt' => '' ), $attr ) as $name => $value ) {
		$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}

	return $html . '>';
}

/**
 * URL → ID attachment. Nhận cả URL của một size ("anh-150x150.png").
 *
 * @param string $url
 * @return int
 */
function hdtc_attachment_id( $url ) {
	static $cache = array();

	if ( ! isset( $cache[ $url ] ) ) {
		// "anh.jpg.webp" (URL WebP chép từ frontend vào meta box) → "anh.jpg".
		$clean = preg_replace( '/\.(jpe?g|png)\.webp$/i', '.$1', strtok( $url, '?#' ) );
		$id    = attachment_url_to_postid( $clean );

		if ( ! $id ) {
			$base = preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', $clean );
			if ( $base !== $clean ) {
				$id = attachment_url_to_postid( $base );
			}
		}

		$cache[ $url ] = (int) $id;
	}

	return $cache[ $url ];
}

/**
 * Size nào của attachment ứng với URL này. Không khớp size nào thì là 'full'.
 *
 * @param int    $id
 * @param string $url
 * @return string
 */
function hdtc_size_of_url( $id, $url ) {
	$meta = wp_get_attachment_metadata( $id );
	$name = preg_replace( '/\.(jpe?g|png)\.webp$/i', '.$1', wp_basename( strtok( $url, '?#' ) ) );

	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size => $info ) {
			if ( isset( $info['file'] ) && $info['file'] === $name ) {
				return $size;
			}
		}
	}

	return 'full';
}

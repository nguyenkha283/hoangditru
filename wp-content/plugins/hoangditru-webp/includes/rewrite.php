<?php
/**
 * Đổi URL ảnh sang WebP, thêm srcset còn thiếu, gắn data-full cho lightbox.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin. Xem convert.php.
 *
 * Bản hoangditru chỉ chạy wsl_rewrite_html() trên nội dung bài viết (ảnh trong
 * template đã được sửa tay để đi qua API ảnh của WordPress). Bản dùng cho site
 * khác chạy nó trên toàn bộ HTML của trang.
 *
 * Không đụng vào ảnh không thuộc thư viện Media: chỉ attachment mới có các size
 * để dựng srcset, và chỉ attachment mới được chuyển WebP.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thư mục uploads: URL gốc, phần path của URL đó, và host.
 *
 * @return array{baseurl:string, path:string, host:string}
 */
function wsl_uploads() {
	static $u = null;

	if ( null === $u ) {
		$dir = wp_upload_dir( null, false );
		$url = isset( $dir['baseurl'] ) ? (string) $dir['baseurl'] : '';
		$u   = array(
			'baseurl' => $url,
			'path'    => trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
			'host'    => strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ),
		);
	}

	return $u;
}

/**
 * Bỏ ".webp" đã gắn bởi plugin này: "anh.jpg.webp" → "anh.jpg".
 *
 * URL WebP có thể đã lọt vào nội dung bài (biên tập viên chép URL từ trang
 * ngoài vào trình soạn thảo). Phải đưa về tên gốc thì mới tra được attachment
 * và metadata.
 *
 * @param string $url
 * @return string
 */
function wsl_strip_webp( $url ) {
	return (string) preg_replace( '/\.(jpe?g|png)\.webp(?=$|[?#])/i', '.$1', (string) $url );
}

/**
 * URL trong uploads → đường dẫn tương đối ("2026/05/anh-300x200.jpg").
 * URL dạng "anh.jpg.webp" cũng trả về tên gốc "anh.jpg".
 *
 * Nhận cả URL tuyệt đối, URL bắt đầu bằng //, và URL tương đối với gốc site.
 * Host khác host của uploads (CDN, site khác) thì không nhận, trừ khi thêm vào
 * filter 'wsl_upload_hosts'.
 *
 * @param string $url Đã giải mã entity.
 * @return string|null
 */
function wsl_upload_rel( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url || 0 === strpos( $url, 'data:' ) ) {
		return null;
	}

	$u    = wsl_uploads();
	$url  = wsl_strip_webp( $url );
	$path = wp_parse_url( $url, PHP_URL_PATH );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

	if ( ! is_string( $path ) || '/' === $u['path'] ) {
		return null;
	}

	if ( '' !== $host && $host !== $u['host'] ) {
		$hosts = (array) apply_filters( 'wsl_upload_hosts', array() );
		if ( ! in_array( $host, array_map( 'strtolower', $hosts ), true ) ) {
			return null;
		}
	}

	if ( 0 !== strpos( $path, $u['path'] ) ) {
		return null;
	}

	return rawurldecode( substr( $path, strlen( $u['path'] ) ) );
}

/**
 * Tên file trong URL, đã giải mã %xx.
 *
 * @param string $url
 * @return string
 */
function wsl_url_name( $url ) {
	return rawurldecode( wp_basename( (string) wp_parse_url( wsl_strip_webp( $url ), PHP_URL_PATH ) ) );
}

/**
 * Gắn ".webp" vào sau tên file, giữ nguyên query và fragment.
 *
 * @param string $url
 * @return string
 */
function wsl_append_webp( $url ) {
	$cut  = strcspn( $url, '?#' );
	$path = substr( $url, 0, $cut );

	if ( '.webp' === strtolower( substr( $path, -5 ) ) ) {
		return $url;
	}

	return $path . '.webp' . substr( $url, $cut );
}

/**
 * URL WebP của một file thuộc attachment $id, hoặc URL gốc nếu file đó không có
 * WebP (chưa chuyển, hoặc WebP không nhỏ hơn bản gốc). $id = 0: trả nguyên.
 *
 * @param string $url
 * @param int    $id
 * @return string
 */
function wsl_webp_url( $url, $id ) {
	if ( ! $id || ! is_string( $url ) || '' === $url ) {
		return $url;
	}

	$state = wsl_state( $id );
	$orig  = wsl_strip_webp( $url );

	// URL đã là .webp mà file đó không có WebP (bị bỏ vì không nhỏ hơn) thì
	// trả về bản gốc, không để ảnh vỡ.
	return empty( $state['files'][ wsl_url_name( $orig ) ] ) ? $orig : wsl_append_webp( $orig );
}

/**
 * Tên mọi file thuộc attachment, để kiểm một URL có đúng là của nó không.
 *
 * @param int $id
 * @return array<string,true>
 */
function wsl_attachment_names( $id ) {
	$meta  = wp_get_attachment_metadata( $id );
	$names = array();

	if ( ! is_array( $meta ) || empty( $meta['file'] ) ) {
		return $names;
	}

	$names[ wp_basename( $meta['file'] ) ] = true;

	if ( ! empty( $meta['original_image'] ) ) {
		$names[ $meta['original_image'] ] = true;
	}

	foreach ( ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) ? $meta['sizes'] : array() as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$names[ $size['file'] ] = true;
		}
	}

	return $names;
}

/**
 * Đường dẫn tương đối → ID attachment, cho cả một loạt URL bằng MỘT truy vấn.
 *
 * URL của một size ("anh-300x200.jpg") không nằm trong _wp_attached_file, nên
 * với mỗi URL thử ba ứng viên: chính nó, bỏ "-300x200", và bản "-scaled". Ứng
 * viên khớp rồi còn phải kiểm tên file có thật thuộc attachment đó không — "a-1x1.jpg"
 * có thể là một ảnh riêng chứ không phải size của "a.jpg".
 *
 * @param string[] $rels
 * @return array<string,int> rel => ID (0 nếu không thuộc attachment nào)
 */
function wsl_resolve_ids( array $rels ) {
	global $wpdb;
	static $cache = array();

	$todo = array();

	foreach ( array_unique( $rels ) as $rel ) {
		if ( ! isset( $cache[ $rel ] ) ) {
			$todo[ $rel ] = wsl_rel_candidates( $rel );
		}
	}

	if ( $todo && isset( $wpdb ) ) {
		$all = array_values( array_unique( array_merge( ...array_values( $todo ) ) ) );
		$map = array();

		foreach ( array_chunk( $all, 200 ) as $chunk ) {
			$in   = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value IN ($in)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					$chunk
				)
			);
			foreach ( (array) $rows as $row ) {
				$map[ $row->meta_value ] = (int) $row->post_id;
			}
		}

		if ( $map && function_exists( 'update_meta_cache' ) ) {
			update_meta_cache( 'post', array_values( $map ) );
		}

		foreach ( $todo as $rel => $cands ) {
			$cache[ $rel ] = 0;
			$name          = wp_basename( $rel );

			foreach ( $cands as $cand ) {
				if ( isset( $map[ $cand ] ) && isset( wsl_attachment_names( $map[ $cand ] )[ $name ] ) ) {
					$cache[ $rel ] = $map[ $cand ];
					break;
				}
			}
		}
	}

	$out = array();
	foreach ( $rels as $rel ) {
		$out[ $rel ] = isset( $cache[ $rel ] ) ? $cache[ $rel ] : 0;
	}

	return $out;
}

/**
 * Các giá trị _wp_attached_file có thể ứng với một URL.
 *
 * @param string $rel
 * @return string[]
 */
function wsl_rel_candidates( $rel ) {
	$out = array( $rel );

	if ( preg_match( '/^(.*?)(-\d+x\d+)?(-scaled)?\.(jpe?g|png|gif|webp)$/i', $rel, $m ) ) {
		$out[] = $m[1] . '.' . $m[4];
		$out[] = $m[1] . '-scaled.' . $m[4];
	}

	return array_values( array_unique( $out ) );
}

/**
 * Thuộc tính của một thẻ, theo thứ tự xuất hiện.
 *
 * Tách bằng regex đọc lần lượt từng cặp tên=giá-trị, nên dấu > hay chữ "src="
 * nằm trong alt="..." không làm lệch.
 *
 * @param string $tag
 * @return array<string,array{raw:string, value:?string}> tên viết thường => ...
 */
function wsl_tag_attrs( $tag ) {
	$attrs = array();

	if ( ! preg_match( '/^<[a-z0-9]+/i', $tag, $m ) ) {
		return $attrs;
	}

	preg_match_all(
		'/([^\s=\/>"\']+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/',
		substr( $tag, strlen( $m[0] ) ),
		$mm,
		PREG_SET_ORDER
	);

	foreach ( $mm as $a ) {
		$name = strtolower( $a[1] );
		if ( isset( $attrs[ $name ] ) ) {
			continue; // HTML lấy thuộc tính xuất hiện trước.
		}
		$value = null;
		foreach ( array( 2, 3, 4 ) as $i ) {
			if ( isset( $a[ $i ] ) && '' !== $a[ $i ] ) {
				$value = $a[ $i ];
				break;
			}
		}
		if ( null === $value && strpos( $a[0], '=' ) !== false ) {
			$value = '';
		}
		$attrs[ $name ] = array(
			'raw'   => $a[0],
			'value' => null === $value ? null : html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}

	return $attrs;
}

/**
 * Dựng lại thẻ sau khi đổi thuộc tính. Thuộc tính không đổi giữ nguyên văn bản gốc.
 *
 * @param string               $tag     Thẻ gốc, để lấy tên thẻ và kiểu đóng.
 * @param array                $attrs   Từ wsl_tag_attrs().
 * @param array<string,string> $changes tên => giá trị mới (chưa escape).
 * @return string
 */
function wsl_build_tag( $tag, array $attrs, array $changes ) {
	if ( ! $changes ) {
		return $tag;
	}

	preg_match( '/^<([a-z0-9]+)/i', $tag, $m );
	$parts = array();

	foreach ( $attrs as $name => $a ) {
		if ( array_key_exists( $name, $changes ) ) {
			$parts[] = $name . '="' . esc_attr( $changes[ $name ] ) . '"';
			unset( $changes[ $name ] );
		} else {
			$parts[] = $a['raw'];
		}
	}

	foreach ( $changes as $name => $value ) {
		$parts[] = $name . '="' . esc_attr( $value ) . '"';
	}

	$close = preg_match( '#/\s*>$#', $tag ) ? ' />' : '>';

	return '<' . $m[1] . ( $parts ? ' ' . implode( ' ', $parts ) : '' ) . $close;
}

/**
 * Đổi từng URL trong một chuỗi srcset.
 *
 * @param string            $srcset
 * @param array<string,int> $ids    rel => ID
 * @param int               $fallback ID dùng khi URL không tra được.
 * @return string
 */
function wsl_rewrite_srcset( $srcset, array $ids, $fallback = 0 ) {
	$out = array();

	foreach ( explode( ',', $srcset ) as $cand ) {
		$cand = trim( $cand );
		if ( '' === $cand ) {
			continue;
		}
		$bits = preg_split( '/\s+/', $cand, 2 );
		$rel  = wsl_upload_rel( $bits[0] );
		$id   = ( null !== $rel && ! empty( $ids[ $rel ] ) ) ? $ids[ $rel ] : $fallback;

		$bits[0] = wsl_webp_url( $bits[0], $id );
		$out[]   = implode( ' ', $bits );
	}

	return implode( ', ', $out );
}

/**
 * Kích thước thật của file ảnh, tra theo tên file trong metadata; không tra được
 * thì lấy width/height của thẻ.
 *
 * File trước, thẻ sau: srcset chỉ gồm các size CÙNG TỈ LỆ với kích thước đưa
 * vào, mà template tự viết hay ghi width/height theo khung hiển thị (thẻ BĐS ghi
 * 400×200 cho ảnh 4:3) — lấy theo thẻ thì không size nào khớp, mất srcset.
 *
 * @return array{0:int,1:int}|null
 */
function wsl_img_dims( array $attrs, $src, $meta, $id ) {
	$dims = wp_image_src_get_dimensions( $src, $meta, $id );

	if ( $dims ) {
		return array( (int) $dims[0], (int) $dims[1] );
	}

	$w = isset( $attrs['width']['value'] ) ? (int) $attrs['width']['value'] : 0;
	$h = isset( $attrs['height']['value'] ) ? (int) $attrs['height']['value'] : 0;

	return ( $w > 0 && $h > 0 ) ? array( $w, $h ) : null;
}

/**
 * Viết lại MỘT thẻ <img> hoặc <source>.
 *
 * @param string            $tag
 * @param array<string,int> $ids  rel => ID, đã tra sẵn cho cả trang.
 * @param array             $opts Xem wsl_rewrite_html().
 * @return string
 */
function wsl_rewrite_tag( $tag, array $ids, array $opts ) {
	$attrs = wsl_tag_attrs( $tag );

	if ( isset( $attrs['data-no-webp'] ) ) {
		return $tag;
	}

	$is_img  = 0 === stripos( $tag, '<img' );
	$changes = array();

	// Ảnh lazy-load bằng JS để URL thật ở data-src, src chỉ là ảnh giữ chỗ.
	$src_key = 'src';
	foreach ( array( 'data-src', 'data-lazy-src' ) as $k ) {
		if ( isset( $attrs[ $k ]['value'] ) && null !== wsl_upload_rel( $attrs[ $k ]['value'] ) ) {
			$src_key = $k;
			break;
		}
	}
	$srcset_key = 'src' === $src_key ? 'srcset' : str_replace( 'src', 'srcset', $src_key );

	$src = isset( $attrs[ $src_key ]['value'] ) ? wsl_strip_webp( $attrs[ $src_key ]['value'] ) : '';
	$rel = '' !== $src ? wsl_upload_rel( $src ) : null;
	$id  = ( null !== $rel && ! empty( $ids[ $rel ] ) ) ? $ids[ $rel ] : 0;

	// class="wp-image-123" của trình soạn thảo cho ID ngay, kể cả khi tra theo
	// tên file thất bại — nhưng chỉ tin khi tên file đúng là của attachment đó.
	if ( ! $id && $is_img && null !== $rel && isset( $attrs['class']['value'] )
		&& preg_match( '/\bwp-image-(\d+)\b/', $attrs['class']['value'], $cm )
		&& isset( wsl_attachment_names( (int) $cm[1] )[ wp_basename( $rel ) ] ) ) {
		$id = (int) $cm[1];
	}

	$meta = $id ? wp_get_attachment_metadata( $id ) : null;
	$dims = ( $is_img && $id && is_array( $meta ) ) ? wsl_img_dims( $attrs, $src, $meta, $id ) : null;

	// srcset còn thiếu.
	if ( $is_img && ! empty( $opts['srcset'] ) && $dims && ! isset( $attrs[ $srcset_key ] ) && ! isset( $attrs['srcset'] ) ) {
		$srcset = wp_calculate_image_srcset( $dims, $src, $meta, $id );

		if ( $srcset ) {
			$changes[ $srcset_key ] = $srcset;

			if ( ! isset( $attrs['sizes'] ) && ! isset( $attrs['data-sizes'] ) ) {
				$sizes = wp_calculate_image_sizes( $dims, $src, $meta, $id );
				if ( $sizes ) {
					// "auto" để trình duyệt chọn theo bề rộng thật trên trang
					// (Chrome 126+), chỉ hợp lệ với ảnh loading="lazy".
					$lazy = isset( $attrs['loading']['value'] ) && 'lazy' === strtolower( $attrs['loading']['value'] );
					$changes[ 'src' === $src_key ? 'sizes' : 'data-sizes' ] = ( $lazy ? 'auto, ' : '' ) . $sizes;
				}
			}
		}
	}

	// Đổi URL sang WebP.
	foreach ( array( 'src', 'data-src', 'data-lazy-src' ) as $k ) {
		if ( empty( $attrs[ $k ]['value'] ) ) {
			continue;
		}
		$r   = wsl_upload_rel( $attrs[ $k ]['value'] );
		$kid = ( null !== $r && ! empty( $ids[ $r ] ) ) ? $ids[ $r ] : ( $k === $src_key ? $id : 0 );
		$new = wsl_webp_url( $attrs[ $k ]['value'], $kid );
		if ( $new !== $attrs[ $k ]['value'] ) {
			$changes[ $k ] = $new;
		}
	}

	foreach ( array( 'srcset', 'data-srcset', 'data-lazy-srcset' ) as $k ) {
		$value = isset( $changes[ $k ] ) ? $changes[ $k ] : ( isset( $attrs[ $k ]['value'] ) ? $attrs[ $k ]['value'] : '' );
		if ( '' === $value ) {
			continue;
		}
		$new = wsl_rewrite_srcset( $value, $ids, $id );
		if ( $new !== $value || isset( $changes[ $k ] ) ) {
			$changes[ $k ] = $new;
		}
	}

	// Lightbox: chỉ khi bản đầy đủ lớn hơn rõ rệt so với cỡ đang hiển thị.
	if ( $is_img && ! empty( $opts['lightbox'] ) && $dims && ! isset( $attrs['data-full'] ) ) {
		$shown = isset( $attrs['width']['value'] ) && (int) $attrs['width']['value'] > 0 ? (int) $attrs['width']['value'] : $dims[0];
		$full  = wsl_full_image( $id, $meta, min( $shown, $dims[0] ) );
		if ( $full ) {
			$changes['data-full'] = $full;
		}
	}

	return wsl_build_tag( $tag, $attrs, $changes );
}

/**
 * URL bản lớn cho lightbox (đã đổi WebP), hoặc '' nếu không đáng mở lightbox.
 *
 * Ngưỡng: bản lớn phải rộng hơn bản đang hiển thị ít nhất 25% VÀ rộng từ 600px.
 * Dưới mức đó mở lightbox chẳng cho thấy gì thêm, chỉ tốn một lần tải.
 *
 * @param int   $id
 * @param array $meta
 * @param int   $shown_w Bề rộng bản đang hiển thị.
 * @return string
 */
function wsl_full_image( $id, $meta, $shown_w ) {
	if ( ! is_array( $meta ) || empty( $meta['file'] ) || empty( $meta['width'] ) ) {
		return '';
	}

	$full_w = (int) $meta['width'];
	$min    = (int) apply_filters( 'wsl_lightbox_min_width', 600 );

	if ( $full_w < $min || $full_w < 1.25 * (int) $shown_w ) {
		return '';
	}

	$u = wsl_uploads();

	return wsl_webp_url( trailingslashit( $u['baseurl'] ) . $meta['file'], $id );
}

/**
 * Viết lại mọi ảnh trong một đoạn HTML.
 *
 * @param string $html
 * @param array  $opts {
 *     @type bool $srcset   Thêm srcset/sizes cho ảnh của thư viện Media chưa có. Mặc định true.
 *     @type bool $lightbox Gắn data-full cho ảnh có bản lớn hơn. Mặc định true.
 * }
 * @return string
 */
function wsl_rewrite_html( $html, array $opts = array() ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	$opts = array_merge(
		array(
			'srcset'   => true,
			'lightbox' => true,
		),
		$opts
	);

	$has_tag = false !== stripos( $html, '<img' ) || false !== stripos( $html, '<source' );
	$has_url = false !== stripos( $html, 'url(' );

	if ( ! $has_tag && ! $has_url ) {
		return $html;
	}

	$tag_re = '/<(?:img|source)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
	$url_re = '/url\(\s*(&quot;|&#0?39;|["\']?)([^"\')&]+?\.(?:jpe?g|png)(?:\?[^"\')&]*)?)\1\s*\)/i';

	// Lượt 1: gom mọi URL uploads trong trang để tra ID bằng một truy vấn.
	$rels = array();

	if ( $has_tag && preg_match_all( $tag_re, $html, $tags ) ) {
		foreach ( $tags[0] as $tag ) {
			foreach ( wsl_tag_attrs( $tag ) as $name => $a ) {
				if ( null === $a['value'] || ! preg_match( '/^(?:data-(?:lazy-)?)?src(?:set)?$/', $name ) ) {
					continue;
				}
				$urls = 'set' === substr( $name, -3 )
					? array_map( static function ( $c ) { return preg_split( '/\s+/', trim( $c ) )[0]; }, explode( ',', $a['value'] ) )
					: array( $a['value'] );
				foreach ( $urls as $url ) {
					$rel = wsl_upload_rel( $url );
					if ( null !== $rel ) {
						$rels[] = $rel;
					}
				}
			}
		}
	}

	if ( $has_url && preg_match_all( $url_re, $html, $urls ) ) {
		foreach ( $urls[2] as $url ) {
			$rel = wsl_upload_rel( $url );
			if ( null !== $rel ) {
				$rels[] = $rel;
			}
		}
	}

	if ( ! $rels ) {
		return $html;
	}

	$ids = wsl_resolve_ids( $rels );

	// Lượt 2: viết lại.
	if ( $has_tag ) {
		$html = preg_replace_callback(
			$tag_re,
			static function ( $m ) use ( $ids, $opts ) {
				return wsl_rewrite_tag( $m[0], $ids, $opts );
			},
			$html
		);
	}

	if ( $has_url ) {
		$html = preg_replace_callback(
			$url_re,
			static function ( $m ) use ( $ids ) {
				$rel = wsl_upload_rel( $m[2] );
				if ( null === $rel || empty( $ids[ $rel ] ) ) {
					return $m[0];
				}
				return 'url(' . $m[1] . wsl_webp_url( $m[2], $ids[ $rel ] ) . $m[1] . ')';
			},
			$html
		);
	}

	return $html;
}

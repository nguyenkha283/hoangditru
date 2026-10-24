<?php
/**
 * Chuyển ảnh sang WebP.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin (hoangditru-webp và
 * webp-srcset-lightbox). scripts/webp.test.php kiểm điều đó. Sửa ở một bản thì
 * chép sang bản kia.
 *
 * Quy ước tên: ảnh.jpg → ảnh.jpg.webp, nằm cạnh file gốc. File gốc GIỮ NGUYÊN.
 * Thêm đuôi chứ không thay đuôi vì hai lẽ:
 *   - ảnh.jpg và ảnh.png cùng thư mục không giẫm lên nhau;
 *   - wp_calculate_image_srcset() của WordPress so khớp src bằng cách tìm tên
 *     file gốc NẰM TRONG URL — "ảnh-300x200.jpg.webp" vẫn chứa
 *     "ảnh-300x200.jpg", nên srcset vẫn được dựng khi src đã là WebP.
 *
 * Trạng thái lưu ở post meta WSL_META của từng attachment:
 *   array( 'files' => array( 'tên-file.jpg' => 1|0 ) )
 *   1 = có .webp và nhỏ hơn bản gốc → phục vụ WebP
 *   0 = đã thử, WebP KHÔNG nhỏ hơn (hay gặp ở PNG ít màu) → phục vụ bản gốc,
 *       và không thử lại nữa trừ khi chạy lại với "ghi đè".
 * Frontend chỉ đọc meta này, không stat file trên đĩa cho từng ảnh.
 */

defined( 'ABSPATH' ) || exit;

const WSL_META = '_wsl_webp';

/**
 * Định dạng nguồn được chuyển. GIF bỏ qua vì ảnh động sẽ thành ảnh tĩnh.
 */
const WSL_MIMES = array( 'image/jpeg', 'image/png' );

/**
 * Máy chủ có ghi được WebP không (GD có imagewebp, hoặc Imagick có WEBP).
 *
 * @return bool
 */
function wsl_server_supports_webp() {
	static $ok = null;

	if ( null === $ok ) {
		$ok = function_exists( 'wp_image_editor_supports' )
			&& wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	}

	return $ok;
}

/**
 * Chất lượng WebP. 82 cho JPEG ảnh chụp giảm khoảng 25–35% dung lượng mà mắt
 * không phân biệt được.
 *
 * @return int
 */
function wsl_quality() {
	return (int) apply_filters( 'wsl_quality', 82 );
}

/**
 * Trạng thái WebP của một attachment.
 *
 * @param int $id
 * @return array{files: array<string,int>}
 */
function wsl_state( $id ) {
	$state = get_post_meta( (int) $id, WSL_META, true );

	if ( ! is_array( $state ) || ! isset( $state['files'] ) || ! is_array( $state['files'] ) ) {
		return array( 'files' => array() );
	}

	return $state;
}

/**
 * Mọi file ảnh hiển thị được của attachment: bản chính và các size.
 *
 * Bỏ 'original_image' (ảnh gốc trước khi WordPress thu về -scaled): nó không
 * bao giờ được hiển thị, chuyển nó chỉ tốn thời gian và đĩa.
 *
 * @param int        $id
 * @param array|null $meta Metadata, truyền vào khi đang ở giữa lúc WordPress lưu.
 * @return array<string,string> tên file => đường dẫn tuyệt đối
 */
function wsl_attachment_files( $id, $meta = null ) {
	if ( null === $meta ) {
		$meta = wp_get_attachment_metadata( $id );
	}

	if ( is_array( $meta ) && ! empty( $meta['file'] ) ) {
		$uploads = wp_upload_dir( null, false );
		$main    = path_join( $uploads['basedir'], $meta['file'] );
	} else {
		$main = get_attached_file( $id );
	}

	if ( ! $main ) {
		return array();
	}

	$dir   = dirname( $main );
	$files = array( wp_basename( $main ) => $main );

	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			if ( ! empty( $size['mime-type'] ) && ! in_array( $size['mime-type'], WSL_MIMES, true ) ) {
				continue;
			}
			$files[ $size['file'] ] = $dir . '/' . $size['file'];
		}
	}

	return $files;
}

/**
 * Chuyển một file. Bản gốc không bị đụng tới.
 *
 * @param string $src  Đường dẫn file JPEG/PNG.
 * @param string $dest Đường dẫn .webp sẽ ghi.
 * @return int|false Số byte tiết kiệm được (>0), 0 nếu WebP không nhỏ hơn
 *                   (file .webp đã bị xoá), false nếu lỗi.
 */
function wsl_convert_file( $src, $dest ) {
	$mime = wp_get_image_mime( $src );

	if ( ! in_array( $mime, WSL_MIMES, true ) ) {
		return false;
	}

	$editor = wp_get_image_editor(
		$src,
		array(
			'mime_type'        => $mime,
			'output_mime_type' => 'image/webp',
		)
	);

	if ( is_wp_error( $editor ) ) {
		return false;
	}

	$editor->set_quality( wsl_quality() );
	$saved = $editor->save( $dest, 'image/webp' );

	if ( is_wp_error( $saved ) || ! file_exists( $dest ) ) {
		return false;
	}

	clearstatcache( true, $dest );
	$before = (int) filesize( $src );
	$after  = (int) filesize( $dest );

	if ( $after <= 0 || $after >= $before ) {
		wp_delete_file( $dest );
		return 0;
	}

	return $before - $after;
}

/**
 * Chuyển mọi file của một attachment còn thiếu WebP.
 *
 * Gọi lại bao nhiêu lần cũng được: file đã có WebP (hoặc đã biết WebP không
 * nhỏ hơn) thì bỏ qua, nên lần gọi sau chỉ làm phần còn thiếu.
 *
 * @param int        $id
 * @param array|null $meta  Metadata mới khi đang ở giữa lúc WordPress lưu.
 * @param bool       $force Chuyển lại cả file đã có.
 * @return array{converted:int, skipped:int, failed:int, saved:int}
 */
function wsl_convert_attachment( $id, $meta = null, $force = false ) {
	$stats = array(
		'converted' => 0,
		'skipped'   => 0,
		'failed'    => 0,
		'saved'     => 0,
	);

	if ( ! in_array( get_post_mime_type( $id ), WSL_MIMES, true ) || ! wsl_server_supports_webp() ) {
		return $stats;
	}

	$files = wsl_attachment_files( $id, $meta );
	$state = wsl_state( $id );
	$new   = array();

	foreach ( $files as $name => $path ) {
		if ( ! file_exists( $path ) ) {
			continue;
		}

		$dest  = $path . '.webp';
		$known = isset( $state['files'][ $name ] ) ? (int) $state['files'][ $name ] : null;

		if ( ! $force && ( 0 === $known || ( 1 === $known && file_exists( $dest ) ) ) ) {
			$new[ $name ] = $known;
			$stats['skipped']++;
			continue;
		}

		$result = wsl_convert_file( $path, $dest );

		if ( false === $result ) {
			$stats['failed']++;
			continue;
		}

		$new[ $name ] = $result > 0 ? 1 : 0;
		$stats['converted']++;
		$stats['saved'] += $result;
	}

	// Ghi lại cả khi rỗng: đánh dấu attachment đã được xử lý, để trang chuyển
	// hàng loạt đếm đúng. Tên file không còn thuộc attachment (ảnh đã sửa, size
	// đã đổi) tự rơi khỏi danh sách.
	update_post_meta( $id, WSL_META, array( 'files' => $new ) );

	return $stats;
}

/**
 * Ảnh mới upload, ảnh vừa sửa trong trình chỉnh ảnh, ảnh tạo lại size.
 *
 * Bám vào 'wp_update_attachment_metadata' thay vì 'wp_generate_attachment_metadata'
 * vì WordPress gọi nó SAU MỖI size khi upload (để còn nối tiếp được nếu request
 * chết giữa chừng). Mỗi lần gọi chỉ chuyển phần còn thiếu, nên ta cũng nối tiếp
 * được, và cả trình chỉnh ảnh (không đi qua generate) cũng được bắt.
 *
 * @param mixed $data Metadata sắp lưu.
 * @param int   $id
 * @return mixed Trả nguyên $data.
 */
function wsl_on_update_metadata( $data, $id ) {
	if ( is_array( $data ) && apply_filters( 'wsl_convert_on_upload', true, $id ) ) {
		wsl_convert_attachment( (int) $id, $data );
	}

	return $data;
}
add_filter( 'wp_update_attachment_metadata', 'wsl_on_update_metadata', 20, 2 );

/**
 * Xoá attachment thì xoá luôn .webp đi kèm. WordPress gọi wp_delete_file() cho
 * từng file (bản chính, các size, bản gốc, bản sao lưu của trình chỉnh ảnh),
 * nên một filter ở đây bắt được hết.
 *
 * @param string $file
 * @return string
 */
function wsl_on_delete_file( $file ) {
	if ( is_string( $file ) && preg_match( '/\.(jpe?g|png)$/i', $file ) ) {
		$webp = $file . '.webp';
		if ( file_exists( $webp ) ) {
			@unlink( $webp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
	}

	return $file;
}
add_filter( 'wp_delete_file', 'wsl_on_delete_file' );

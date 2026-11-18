<?php
/**
 * Làm sạch mô tả ngắn: bỏ shortcode và link YouTube trần.
 *
 * Thẻ bài ở mục Chương trình / Tin tức của Đầu tư quốc tịch lấy mô tả bằng
 * wp_trim_words( post_excerpt ?: post_content ). wp_trim_words() bỏ thẻ HTML
 * nhưng KHÔNG bỏ shortcode, nên khách đọc được nguyên chuỗi
 * "[youtube_embed url=…]". Mô tả viết tay (post_excerpt) đi qua
 * get_the_excerpt() cũng vậy: wp_trim_excerpt() chỉ bỏ shortcode khi TỰ sinh
 * mô tả từ nội dung.
 *
 * Hàm gắn vào filter get_the_excerpt, nên mọi chỗ gọi get_the_excerpt() —
 * trang chủ, danh mục Tin tức/Kiến thức của theme, mô tả đầu trang Golden Visa
 * và Đầu tư quốc tịch — đều được làm sạch, Golden Visa, Đầu tư quốc tịch, Bất
 * động sản hay bài viết đều như nhau. Chỗ nào đọc thẳng post_excerpt/
 * post_content thì gọi hdtc_clean_excerpt() trực tiếp.
 *
 * Nội dung bài (the_content) không bị đụng: video vẫn hiện trong bài.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode bỏ CẢ phần bọc bên trong: nội dung của chúng là URL hay ảnh, không
 * phải chữ để đọc. Các shortcode khác chỉ bỏ thẻ, giữ chữ bên trong —
 * [ux_text]Chữ[/ux_text] còn lại "Chữ".
 */
const HDTC_EXCERPT_BO_HAN = array( 'youtube_embed', 'embed', 'video', 'audio', 'playlist', 'gallery', 'caption', 'wp_caption' );

/**
 * @param string $text Mô tả, có thể có HTML.
 * @return string
 */
function hdtc_clean_excerpt( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}

	$truoc = $text;

	if ( false !== strpos( $text, '[' ) ) {
		$ten  = implode( '|', array_map( 'preg_quote', HDTC_EXCERPT_BO_HAN ) );

		// [tag …] kèm phần bọc tới [/tag] — phần bọc không được chứa một [tag
		// khác, để một shortcode tự đóng không nuốt chữ tới tận thẻ đóng của cái
		// sau nó.
		$text = preg_replace(
			'~\[(' . $ten . ')(?![\w-])[^\]]*\](?:((?:(?!\[\1(?![\w-])).)*?)\[/\1\])?~isu',
			'',
			$text
		) ?? $text;

		// Thẻ còn lại: bỏ thẻ, giữ chữ. Chỉ bỏ cái chắc là shortcode — đã đăng
		// ký, có thuộc tính, hoặc là thẻ đóng — để "[Hot]" trong tiêu đề còn
		// nguyên.
		$text = preg_replace_callback(
			'~\[(/?)([a-zA-Z][\w-]*)((?:\s[^\]]*)?)/?\]~u',
			static function ( $m ) {
				if ( '/' === $m[1] || false !== strpos( $m[3], '=' ) ) {
					return '';
				}
				if ( function_exists( 'shortcode_exists' ) && shortcode_exists( $m[2] ) ) {
					return '';
				}
				return $m[0];
			},
			$text
		) ?? $text;
	}

	// Link YouTube dán trần (WordPress tự nhúng thành video trong bài). Không
	// đụng link nằm trong thuộc tính href="…".
	$text = preg_replace(
		'~(?<![="\':/\w])(?:https?:)?//(?:www\.|m\.)?(?:youtube\.com|youtu\.be|youtube-nocookie\.com)/[^\s<>"\']*~iu',
		'',
		$text
	) ?? $text;

	if ( $text === $truoc ) {
		return $text;
	}

	return trim( preg_replace( '/[ \t]{2,}/', ' ', $text ) ?? $text );
}

add_filter( 'get_the_excerpt', 'hdtc_clean_excerpt', 20 );

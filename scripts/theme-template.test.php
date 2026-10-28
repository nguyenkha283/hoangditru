<?php
/**
 * Test cho template của theme: thẻ bài và màn lưu trữ.
 *
 *   php scripts/theme-template.test.php
 *
 * Hai thứ ở đây hỏng mà không có lỗi PHP nào, nên không ai thấy cho tới khi mở
 * đúng trang đó bằng mắt:
 *
 *   - Thẻ bài Golden Visa / Đầu tư quốc tịch hiện lại ngày đăng. Trang chương
 *     trình mà đề ngày là nói với khách rằng nội dung cũ từ bấy đến giờ.
 *   - Tiêu đề màn lưu trữ bị bọc esc_html lần nữa, in ra nguyên chuỗi "<span>"
 *     cho khách đọc. Đúng lỗi đã có thật trên /golden-visa/.
 *
 * Không có WordPress chạy được trên máy nên test giả lập các hàm mà template
 * gọi, rồi render thẳng file template và đọc HTML nhận được.
 */

define( 'ABSPATH', __DIR__ . '/../' );

$GLOBALS['post_type'] = 'post';

// ── Hàm WordPress mà functions.php và template gọi ──────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function add_filter( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function add_shortcode( $tag, $cb = null ) {}
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_url( $v ) { return (string) $v; }

function get_post_type( $post = null ) { return $GLOBALS['post_type']; }
function has_post_thumbnail( $post = null ) { return true; }
function the_permalink() { echo 'https://hoangditru.com/bai-viet/'; }
function the_post_thumbnail( $size = null ) { echo '<img src="anh.jpg" alt="">'; }
function the_title() { echo 'Định cư Malta'; }
function get_the_date( $format = '' ) { return '27/04/2026'; }

require __DIR__ . '/../wp-content/themes/hoangditru/functions.php';

$pass = 0;
$fail = 0;

function t( $name, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		printf( "  ok    %s\n", $name );
		$pass++;

		return;
	}

	printf( "  LỖI   %s\n          nhận %s\n          cần  %s\n", $name, var_export( $got, true ), var_export( $want, true ) );
	$fail++;
}

/**
 * Render thẻ bài với một post type rồi trả về HTML.
 *
 * @param string $post_type Post type của bài đang lặp.
 * @return string
 */
function the_card( $post_type ) {
	$GLOBALS['post_type'] = $post_type;

	ob_start();
	require __DIR__ . '/../wp-content/themes/hoangditru/template-parts/content-card.php';

	return (string) ob_get_clean();
}

echo "── Post type nào là trang chương trình ─────────────────────────────\n";

t( 'golden_visa', hdt_is_program_post_type( 'golden_visa' ), true );
t( 'citizenship_invest', hdt_is_program_post_type( 'citizenship_invest' ), true );

/*
 * Bất động sản KHÔNG nằm trong nhóm này. Nó cũng là trang dự án, nhưng vào menu
 * qua danh mục và có template riêng của plugin — gộp vào đây là sửa nhầm chỗ.
 */
t( 'bat-dong-san không tính', hdt_is_program_post_type( 'bat-dong-san' ), false );
t( 'post không tính', hdt_is_program_post_type( 'post' ), false );
t( 'page không tính', hdt_is_program_post_type( 'page' ), false );

// Bỏ trống tham số thì lấy post type của bài đang lặp.
$GLOBALS['post_type'] = 'golden_visa';
t( 'không truyền tham số thì tự lấy', hdt_is_program_post_type(), true );

echo "\n── Thẻ bài: ngày đăng ──────────────────────────────────────────────\n";

$html = the_card( 'golden_visa' );
t( 'Golden Visa không có ngày', false === strpos( $html, '27/04/2026' ), true );
t( 'Golden Visa không có ô meta', false === strpos( $html, 'hdt-card__meta' ), true );

// Phần còn lại của thẻ phải nguyên vẹn, không phải ẩn nhầm cả thẻ.
t( 'Golden Visa vẫn có tiêu đề', false !== strpos( $html, 'Định cư Malta' ), true );
t( 'Golden Visa vẫn có ảnh', false !== strpos( $html, '<img' ), true );
t( 'Golden Visa vẫn có link', false !== strpos( $html, 'hoangditru.com/bai-viet/' ), true );

$html = the_card( 'citizenship_invest' );
t( 'Đầu tư quốc tịch không có ngày', false === strpos( $html, '27/04/2026' ), true );

/*
 * Tin tức và Kiến thức vẫn phải có ngày. Ẩn luôn ở đây là lặng lẽ đổi cả trang
 * blog và trang tìm kiếm, trong khi người dùng chỉ xin sửa hai màn.
 */
$html = the_card( 'post' );
t( 'Bài viết thường VẪN có ngày', false !== strpos( $html, '27/04/2026' ), true );
t( 'Bài viết thường vẫn có ô meta', false !== strpos( $html, 'hdt-card__meta' ), true );

echo "\n── Màn lưu trữ ─────────────────────────────────────────────────────\n";

$archive = file_get_contents( __DIR__ . '/../wp-content/themes/hoangditru/archive.php' );

/*
 * get_the_archive_title() trả về HTML có sẵn và đã tự escape phần chữ. Bọc
 * esc_html lần nữa là in "<span>" ra cho khách đọc — lỗi đã có thật trên site.
 * Kiểm bằng chuỗi vì render cả archive.php thì phải giả lập nguyên vòng lặp
 * WordPress, mà cái cần canh chỉ là một lời gọi hàm.
 */
t(
	'không bọc esc_html quanh tiêu đề lưu trữ',
	false === strpos( $archive, 'esc_html( get_the_archive_title() )' ),
	true
);
t(
	'dùng the_archive_title để in',
	false !== strpos( $archive, 'the_archive_title(' ),
	true
);
t(
	'có chặn tiêu đề ở màn chương trình',
	false !== strpos( $archive, 'is_post_type_archive( HDT_PROGRAM_POST_TYPES )' ),
	true
);

printf( "\n  %d ok, %d lỗi\n", $pass, $fail );

if ( $fail ) {
	exit( 1 );
}

echo "\nOK — thẻ bài và màn lưu trữ in đúng thứ cần in.\n";

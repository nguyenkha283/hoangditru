<?php
/**
 * Test cho module menu-tu-dong.
 *
 *   php scripts/menu-tu-dong.test.php
 *
 * Module này ghi vào menu của site thật, nên sai là menu sai — mục mới nằm sai
 * cụm, nằm hai lần, hay nằm ở menu chân trang. Không có WordPress chạy được
 * trên máy nên test giả lập các hàm nav menu, dựng lại đúng cấu trúc menu đang
 * chạy ngoài hoangditru.com, rồi xem module gọi wp_update_nav_menu_item() với
 * tham số gì.
 *
 * Việc mục có thật sự hiện ra đúng chỗ thì phải xem trên site thật.
 */

define( 'ABSPATH', __DIR__ . '/../' );

// ── Hàm WordPress mà module gọi ─────────────────────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function current_time( $type ) { return '2026-10-26 00:00:00'; }

class WP_Post {
	public $ID;
	public $post_type;

	public function __construct( $id, $post_type ) {
		$this->ID        = $id;
		$this->post_type = $post_type;
	}
}

/**
 * Menu giả lập: id menu => danh sách mục.
 *
 * Dựng theo menu chính thật của site: ba mục cha dạng custom link, mỗi mục chở
 * một loại nội dung. Menu 2 là menu chân trang, không chở loại nào.
 *
 * Test đổi được biến này để dựng tình huống khác.
 */
$GLOBALS['menus'] = array();

/** Mọi lượt gọi wp_update_nav_menu_item() trong một ca test. */
$GLOBALS['inserted'] = array();

/** Post meta giả lập: "post_id:key" => value. */
$GLOBALS['meta'] = array();

/** Vị trí theme => id menu. */
$GLOBALS['locations'] = array( 'primary' => 1 );

function item( $id, $type, $object, $object_id, $parent ) {
	return (object) array(
		'db_id'            => $id,
		'type'             => $type,
		'object'           => $object,
		'object_id'        => $object_id,
		'menu_item_parent' => $parent,
	);
}

/** Menu chính giống ngoài site thật. */
function menu_that_site_has() {
	$items = array(
		item( 10, 'post_type', 'page', 5, 0 ),
		item( 20, 'custom', 'custom', 20, 0 ),   // "Golden Visa"
		item( 30, 'custom', 'custom', 30, 0 ),   // "Đầu tư quốc tịch"
		item( 40, 'custom', 'custom', 40, 0 ),   // "Bất động sản"
		item( 50, 'taxonomy', 'category', 7, 0 ),
	);

	foreach ( range( 21, 26 ) as $i ) {
		$items[] = item( $i, 'post_type', 'golden_visa', 100 + $i, 20 );
	}
	foreach ( range( 31, 33 ) as $i ) {
		$items[] = item( $i, 'post_type', 'citizenship_invest', 200 + $i, 30 );
	}
	foreach ( range( 41, 43 ) as $i ) {
		$items[] = item( $i, 'taxonomy', 'bds_category', 300 + $i, 40 );
	}

	return $items;
}

function wp_get_nav_menus( $args = array() ) {
	$out = array();

	foreach ( array_keys( $GLOBALS['menus'] ) as $id ) {
		$out[] = (object) array( 'term_id' => $id );
	}

	return $out;
}

function wp_get_nav_menu_items( $menu_id, $args = array() ) {
	return $GLOBALS['menus'][ (int) $menu_id ] ?? false;
}

function get_nav_menu_locations() { return $GLOBALS['locations']; }

function wp_update_nav_menu_item( $menu_id, $item_id, $args ) {
	$GLOBALS['inserted'][] = array( 'menu' => (int) $menu_id ) + $args;

	return 900 + count( $GLOBALS['inserted'] );
}

function get_post_meta( $id, $key, $single = false ) {
	return $GLOBALS['meta'][ "$id:$key" ] ?? '';
}

function update_post_meta( $id, $key, $value ) {
	$GLOBALS['meta'][ "$id:$key" ] = $value;

	return true;
}

require __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/menu-tu-dong/menu-tu-dong.php';

$pass = 0;
$fail = 0;

function t( $name, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		printf( "  ok    %s\n", $name );
		$pass++;

		return;
	}

	printf( "  LỖI   %s\n          nhận %s, cần %s\n", $name, var_export( $got, true ), var_export( $want, true ) );
	$fail++;
}

/** Dựng lại trạng thái đầu cho một ca test. */
function reset_state( $menus = null ) {
	$GLOBALS['menus']     = null === $menus
		? array( 1 => menu_that_site_has(), 2 => array( item( 60, 'custom', 'custom', 60, 0 ) ) )
		: $menus;
	$GLOBALS['inserted']  = array();
	$GLOBALS['meta']      = array();
	$GLOBALS['locations'] = array( 'primary' => 1 );
}

echo "── Tìm mục cha ─────────────────────────────────────────────────────\n";

$items = menu_that_site_has();

t( 'golden_visa nằm dưới mục 20', hdtmenu_find_parent( $items, 'post_type', 'golden_visa' ), 20 );
t( 'citizenship_invest nằm dưới mục 30', hdtmenu_find_parent( $items, 'post_type', 'citizenship_invest' ), 30 );
t( 'bds_category nằm dưới mục 40', hdtmenu_find_parent( $items, 'taxonomy', 'bds_category' ), 40 );
t( 'category nằm ở cấp 1', hdtmenu_find_parent( $items, 'taxonomy', 'category' ), 0 );
t( 'menu không chở post → false', hdtmenu_find_parent( $items, 'post_type', 'post' ), false );

/*
 * 0 và false là hai câu trả lời khác nhau: 0 là "cấp 1", false là "menu này
 * không chở loại đó". Lẫn hai cái là mỗi bài mới đều rơi ra cấp 1.
 */
t( '0 không phải false', 0 === hdtmenu_find_parent( $items, 'taxonomy', 'category' ), true );

// Một mục bị kéo ra ngoài cụm không được lái cả nhóm theo.
$lech   = $items;
$lech[] = item( 27, 'post_type', 'golden_visa', 999, 0 );
t( 'số đông vẫn thắng mục lẻ', hdtmenu_find_parent( $lech, 'post_type', 'golden_visa' ), 20 );

echo "\n── Đã có trong menu chưa ───────────────────────────────────────────\n";

t( 'bài 121 đã có', hdtmenu_has_item( $items, 'post_type', 'golden_visa', 121 ), true );
t( 'bài 777 chưa có', hdtmenu_has_item( $items, 'post_type', 'golden_visa', 777 ), false );
t( 'term 341 đã có', hdtmenu_has_item( $items, 'taxonomy', 'bds_category', 341 ), true );

// Cùng ID nhưng khác loại là đối tượng khác — bài số 7 không phải danh mục số 7.
t( 'cùng id khác loại không tính', hdtmenu_has_item( $items, 'post_type', 'golden_visa', 7 ), false );

echo "\n── Xuất bản bài Golden Visa mới ────────────────────────────────────\n";

reset_state();
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'golden_visa' ) );

t( 'thêm đúng 1 mục', count( $GLOBALS['inserted'] ), 1 );
$new = $GLOBALS['inserted'][0] ?? array();
t( 'vào menu chính', $new['menu'] ?? null, 1 );
t( 'dưới mục cha Golden Visa', $new['menu-item-parent-id'] ?? null, 20 );
t( 'trỏ tới bài 777', $new['menu-item-object-id'] ?? null, 777 );
t( 'loại post_type', $new['menu-item-type'] ?? null, 'post_type' );
t( 'object golden_visa', $new['menu-item-object'] ?? null, 'golden_visa' );

// Nhãn để rỗng thì WordPress lấy tiêu đề bài, nên đổi tên bài là menu đổi theo.
t( 'nhãn để rỗng', $new['menu-item-title'] ?? null, '' );
t( 'đã đánh dấu đã thêm', '' !== get_post_meta( 777, HDTMENU_DONE_META, true ), true );

echo "\n── Các trường hợp KHÔNG được thêm ──────────────────────────────────\n";

reset_state();
hdtmenu_on_post( 'publish', 'publish', new WP_Post( 777, 'golden_visa' ) );
t( 'sửa bài đã xuất bản', count( $GLOBALS['inserted'] ), 0 );

reset_state();
hdtmenu_on_post( 'draft', 'publish', new WP_Post( 777, 'golden_visa' ) );
t( 'hạ xuống nháp', count( $GLOBALS['inserted'] ), 0 );

reset_state();
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'post' ) );
t( 'bài viết thường', count( $GLOBALS['inserted'] ), 0 );

reset_state();
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'bat-dong-san' ) );
t( 'bất động sản (vào menu qua danh mục)', count( $GLOBALS['inserted'] ), 0 );

reset_state();
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 121, 'golden_visa' ) );
t( 'bài vốn đã có trong menu', count( $GLOBALS['inserted'] ), 0 );

reset_state();
$GLOBALS['meta']['777:' . HDTMENU_DONE_META] = '2026-01-01 00:00:00';
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'golden_visa' ) );
t( 'đã có dấu đã thêm (mục bị xoá tay)', count( $GLOBALS['inserted'] ), 0 );

echo "\n── Term danh mục bất động sản mới ──────────────────────────────────\n";

reset_state();
hdtmenu_on_term( 888, 0, 'bds_category' );
t( 'thêm đúng 1 mục', count( $GLOBALS['inserted'] ), 1 );
$new = $GLOBALS['inserted'][0] ?? array();
t( 'dưới mục cha Bất động sản', $new['menu-item-parent-id'] ?? null, 40 );
t( 'loại taxonomy', $new['menu-item-type'] ?? null, 'taxonomy' );
t( 'trỏ tới term 888', $new['menu-item-object-id'] ?? null, 888 );

reset_state();
hdtmenu_on_term( 888, 0, 'category' );
t( 'taxonomy ngoài danh sách', count( $GLOBALS['inserted'] ), 0 );

echo "\n── Menu chưa chở loại nào ──────────────────────────────────────────\n";

// Menu mới dựng, chỉ có Trang chủ: không có mục cùng loại để dựa vào.
reset_state( array( 1 => array( item( 10, 'post_type', 'page', 5, 0 ) ) ) );
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'golden_visa' ) );

t( 'vẫn thêm vào menu chính', count( $GLOBALS['inserted'] ), 1 );
t( 'ở cấp 1', $GLOBALS['inserted'][0]['menu-item-parent-id'] ?? null, 0 );

// Không có menu nào gán vào vị trí primary thì không có chỗ nào để thêm.
reset_state( array( 1 => array( item( 10, 'post_type', 'page', 5, 0 ) ) ) );
$GLOBALS['locations'] = array();
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'golden_visa' ) );

t( 'chưa gán vị trí menu → không thêm', count( $GLOBALS['inserted'] ), 0 );
t( 'và không đánh dấu đã thêm', get_post_meta( 777, HDTMENU_DONE_META, true ), '' );

echo "\n── Menu chân trang cũng chở loại đó ────────────────────────────────\n";

reset_state(
	array(
		1 => menu_that_site_has(),
		2 => array(
			item( 60, 'custom', 'custom', 60, 0 ),
			item( 61, 'post_type', 'golden_visa', 121, 60 ),
		),
	)
);
hdtmenu_on_post( 'publish', 'draft', new WP_Post( 777, 'golden_visa' ) );

t( 'thêm vào cả hai menu', count( $GLOBALS['inserted'] ), 2 );
t( 'menu 1 dưới mục 20', $GLOBALS['inserted'][0]['menu-item-parent-id'] ?? null, 20 );
t( 'menu 2 dưới mục 60', $GLOBALS['inserted'][1]['menu-item-parent-id'] ?? null, 60 );

printf( "\n  %d ok, %d lỗi\n", $pass, $fail );

if ( $fail ) {
	exit( 1 );
}

echo "\nOK — mục mới vào đúng cụm trong menu.\n";

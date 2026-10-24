<?php
/**
 * Tắt WebP của LiteSpeed Cache.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin. Xem convert.php.
 *
 * Để cả hai cùng chạy thì LiteSpeed sẽ thay URL ảnh lần nữa (sang file .webp do
 * máy chủ QUIC.cloud trả về), thêm biến thể cache theo trình duyệt có/không hỗ
 * trợ WebP, và hai bộ .webp nằm cạnh nhau trong uploads. Plugin này đã lo WebP
 * nên phần đó của LiteSpeed phải tắt; phần cache trang của LiteSpeed giữ nguyên.
 *
 * Hai lớp:
 *   - Khi plugin đang bật: filter pre_option_* ép các tuỳ chọn về 0 mỗi lần
 *     LiteSpeed đọc, nên ai bật lại trong trang cài đặt của LiteSpeed cũng
 *     không có tác dụng.
 *   - Lúc kích hoạt: ghi hẳn 0 vào database, để tắt plugin này rồi LiteSpeed
 *     cũng không tự bật lại WebP; và purge toàn bộ cache để trang mới được dựng
 *     lại với URL WebP của plugin này.
 *
 * LiteSpeed lưu mỗi tuỳ chọn thành một option riêng tên "litespeed.conf.<id>".
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các tuỳ chọn WebP của LiteSpeed Cache qua các đời:
 *   img_optm-webp                 "Image WebP Replacement" (v3+; từ v7 giá trị 2 là AVIF)
 *   img_optm-webp_replace         tên cũ của tuỳ chọn trên (v2.x)
 *   img_optm-webp_replace_srcset  "WebP For Extra srcset"
 */
const WSL_LSCWP_OFF = array(
	'img_optm-webp',
	'img_optm-webp_replace',
	'img_optm-webp_replace_srcset',
);

/**
 * @return int
 */
function wsl_lscwp_zero() {
	return 0;
}

foreach ( WSL_LSCWP_OFF as $wsl_id ) {
	add_filter( 'pre_option_litespeed.conf.' . $wsl_id, 'wsl_lscwp_zero' );
}
unset( $wsl_id );

/**
 * Ép tắt qua API của LiteSpeed nữa, phòng khi đời nào đó nó thôi đọc bằng
 * get_option. Action không tồn tại thì do_action không làm gì.
 */
function wsl_lscwp_force_off() {
	foreach ( WSL_LSCWP_OFF as $id ) {
		do_action( 'litespeed_conf_force', $id, 0 );
	}
}
add_action( 'litespeed_init', 'wsl_lscwp_force_off' );

/**
 * Gọi lúc kích hoạt plugin.
 */
function wsl_lscwp_disable_persist() {
	foreach ( WSL_LSCWP_OFF as $id ) {
		$name = 'litespeed.conf.' . $id;
		// Đọc thẳng DB, vì pre_option_* của chính ta đang ép giá trị về 0.
		remove_filter( 'pre_option_' . $name, 'wsl_lscwp_zero' );
		if ( false !== get_option( $name, false ) ) {
			update_option( $name, 0 );
		}
		add_filter( 'pre_option_' . $name, 'wsl_lscwp_zero' );
	}

	wsl_lscwp_purge();
}

/**
 * Xoá cache trang của LiteSpeed, để HTML được dựng lại với URL mới.
 */
function wsl_lscwp_purge() {
	do_action( 'litespeed_purge_all' );
}

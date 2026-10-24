<?php
/**
 * Plugin Name: Hoàng Di Trú loader
 * Description: Nạp theme và plugin lõi từ wp-content/app — thư mục do hPanel Git cài. Đặt file này ở wp-content/mu-plugins/ nên nó nằm ngoài tầm với của Git và không bị deploy ghi đè.
 * Version:     1.0.0
 *
 * ── CÀI MỘT LẦN ─────────────────────────────────────────────────────────────
 * Chép file này thành:  wp-content/mu-plugins/hoangditru-loader.php
 *
 * mu-plugins luôn chạy, không bật/tắt được, và nằm NGOÀI wp-content/app nên
 * hPanel Git không đụng tới. Đó là lý do nó phải ở đây chứ không nằm trong repo
 * theo nghĩa được deploy.
 *
 * ── VÌ SAO CẦN LOADER ───────────────────────────────────────────────────────
 * hPanel Git chỉ cài được 1 kho / 1 nhánh / 1 thư mục, và XOÁ SẠCH thư mục đích
 * trước mỗi lần kéo code. Nên thư mục đích phải riêng và rỗng: wp-content/app.
 * Nhưng WordPress chỉ tìm theme ở wp-content/themes và plugin ở
 * wp-content/plugins. File này bắc cầu:
 *
 *   theme   register_theme_directory( app/themes )
 *   plugin  require trực tiếp app/plugins/hoangditru-core
 *   URL     filter 'plugins_url' để asset của plugin trỏ đúng
 *
 * ── CHỐT TỰ BẢO VỆ ──────────────────────────────────────────────────────────
 * Nếu hoangditru-core vẫn đang bật như plugin thường trong wp-content/plugins,
 * loader KHÔNG nạp bản trong app — nạp cả hai là "cannot redeclare function" và
 * trắng trang. Nhờ vậy việc chuyển đổi làm được từng bước và lùi được: chỉ cần
 * bật lại plugin thường là loader tự đứng sang một bên.
 */

defined( 'ABSPATH' ) || exit;

define( 'HDT_APP_DIR', WP_CONTENT_DIR . '/app' );

/**
 * Sửa URL asset cho plugin nằm ngoài wp-content/plugins.
 *
 * plugins_url() của WordPress dựng URL bằng cách bỏ tiền tố WP_PLUGIN_DIR khỏi
 * đường dẫn file. File nằm trong app/ không khớp tiền tố đó, nên kết quả thành
 * https://site/wp-content/plugins/home/u.../wp-content/app/plugins/... — sai
 * hoàn toàn. Filter này dựng lại cho đúng.
 *
 * Trả về đường dẫn KHÔNG có dấu / ở cuối khi $path rỗng, giống plugins_url gốc:
 * plugin_dir_url() là trailingslashit( plugins_url( '', $file ) ), nên thêm dấu
 * / ở đây sẽ thành hai dấu.
 *
 * @param string $url    URL mà WordPress vừa dựng (sai, với file trong app).
 * @param string $path   Đường dẫn tương đối được yêu cầu.
 * @param string $plugin Đường dẫn tuyệt đối của file plugin.
 * @return string
 */
function hdt_app_plugins_url( $url, $path, $plugin ) {
	if ( empty( $plugin ) || ! is_string( $plugin ) ) {
		return $url;
	}

	$base = wp_normalize_path( HDT_APP_DIR . '/plugins/' );
	$file = wp_normalize_path( $plugin );

	if ( 0 !== strpos( $file, $base ) ) {
		return $url;
	}

	$dir = dirname( substr( $file, strlen( $base ) ) );
	$out = content_url( '/app/plugins' . ( '.' === $dir ? '' : '/' . $dir ) );

	if ( '' !== $path && is_string( $path ) ) {
		$out .= '/' . ltrim( $path, '/' );
	}

	return $out;
}

add_filter( 'plugins_url', 'hdt_app_plugins_url', 10, 3 );

// ── Theme ───────────────────────────────────────────────────────────────────
// WordPress tự suy ra URL của theme từ thư mục gốc đã đăng ký, miễn là thư mục
// đó nằm trong wp-content — app/themes thoả, nên theme không cần filter.
if ( is_dir( HDT_APP_DIR . '/themes' ) ) {
	register_theme_directory( HDT_APP_DIR . '/themes' );
}

// ── Plugin lõi ──────────────────────────────────────────────────────────────
$hdt_core = HDT_APP_DIR . '/plugins/hoangditru-core/hoangditru-core.php';

if ( ! file_exists( $hdt_core ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>Hoàng Di Trú loader:</strong> không thấy <code>wp-content/app/plugins/hoangditru-core/hoangditru-core.php</code>. Kiểm tra lại hPanel → Advanced → GIT (nhánh <code>deploy</code>, install path <code>wp-content/app</code>).</p></div>';
		}
	);
} elseif ( in_array( 'hoangditru-core/hoangditru-core.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	// Bản trong wp-content/plugins vẫn đang bật. Để nó chạy, đừng nạp bản app —
	// nạp cả hai là trắng trang.
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-warning"><p><strong>Hoàng Di Trú loader đang đứng ngoài.</strong> Plugin <em>Hoàng Di Trú Core</em> vẫn bật trong mục Plugin, nên site đang chạy bản ở <code>wp-content/plugins</code> chứ không phải bản deploy qua Git. Tắt plugin đó để loader tiếp quản.</p></div>';
		}
	);
} else {
	require_once $hdt_core;
}

unset( $hdt_core );

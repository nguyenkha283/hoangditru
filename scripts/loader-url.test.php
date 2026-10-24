<?php
/**
 * Test cho filter plugins_url trong deploy/mu-plugin.php.
 *
 *   php scripts/loader-url.test.php
 *
 * Khi plugin được nạp từ wp-content/app thay vì wp-content/plugins, hàm
 * plugins_url() của WordPress dựng URL sai vì nó bỏ tiền tố WP_PLUGIN_DIR khỏi
 * đường dẫn file mà file lại không nằm dưới tiền tố đó. Filter trong loader
 * dựng lại. Sai một dấu gạch chéo ở đây là vỡ toàn bộ CSS/JS của plugin, nên
 * nó xứng đáng có test.
 */

define( 'ABSPATH', __DIR__ . '/../' );
define( 'WP_CONTENT_DIR', '/home/u133340861/public_html/wp-content' );

function wp_normalize_path( $path ) {
	$path = str_replace( '\\', '/', $path );
	return preg_replace( '|(?<=.)/+|', '/', $path );
}
function content_url( $path = '' ) {
	return 'https://hoangditru.com/wp-content' . $path;
}
function add_filter( $h, $cb = null, $p = 10, $a = 1 ) {}
function add_action( $h, $cb = null, $p = 10, $a = 1 ) {}
function register_theme_directory( $dir ) {}
function get_option( $k, $d = false ) { return $d; }
function is_dir_stub() {}

require __DIR__ . '/../deploy/mu-plugin.php';

$APP = WP_CONTENT_DIR . '/app/plugins/';
$pass = 0;
$fail = 0;

function t( $name, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) {
		printf( "  ok    %s\n", $name );
		$pass++;
	} else {
		printf( "  LỖI   %s\n        mong đợi: %s\n        nhận    : %s\n", $name, $want, $got );
		$fail++;
	}
}

echo "filter plugins_url của loader\n";

// plugin_dir_url() = trailingslashit( plugins_url( '', $file ) ), nên với $path
// rỗng filter PHẢI trả về chuỗi KHÔNG có / ở cuối, không thì thành hai dấu.
t(
	'module, $path rỗng (không có / cuối)',
	hdt_app_plugins_url( 'SAI', '', $APP . 'hoangditru-core/modules/trang-chu/trang-chu.php' ),
	'https://hoangditru.com/wp-content/app/plugins/hoangditru-core/modules/trang-chu'
);

t(
	'module, có $path',
	hdt_app_plugins_url( 'SAI', 'assets/nav-global.css', $APP . 'hoangditru-core/modules/trang-chu/trang-chu.php' ),
	'https://hoangditru.com/wp-content/app/plugins/hoangditru-core/modules/trang-chu/assets/nav-global.css'
);

t(
	'module lồng sâu, $path có / ở đầu',
	hdt_app_plugins_url( 'SAI', '/assets/css/style.css', $APP . 'hoangditru-core/modules/bat-dong-san/bat-dong-san.php' ),
	'https://hoangditru.com/wp-content/app/plugins/hoangditru-core/modules/bat-dong-san/assets/css/style.css'
);

t(
	'file chính của plugin lõi',
	hdt_app_plugins_url( 'SAI', '', $APP . 'hoangditru-core/hoangditru-core.php' ),
	'https://hoangditru.com/wp-content/app/plugins/hoangditru-core'
);

t(
	'đường dẫn kiểu Windows cũng chuẩn hoá được',
	hdt_app_plugins_url( 'SAI', 'a.css', str_replace( '/', '\\', $APP . 'hoangditru-core/modules/cim-footer/cim-footer.php' ) ),
	'https://hoangditru.com/wp-content/app/plugins/hoangditru-core/modules/cim-footer/a.css'
);

// Plugin bên thứ ba vẫn nằm ở wp-content/plugins — KHÔNG được đụng vào.
t(
	'plugin ngoài app giữ nguyên URL',
	hdt_app_plugins_url(
		'https://hoangditru.com/wp-content/plugins/contact-form-7',
		'',
		WP_CONTENT_DIR . '/plugins/contact-form-7/wp-contact-form-7.php'
	),
	'https://hoangditru.com/wp-content/plugins/contact-form-7'
);

t(
	'$plugin rỗng giữ nguyên URL',
	hdt_app_plugins_url( 'https://hoangditru.com/wp-content/plugins', '', '' ),
	'https://hoangditru.com/wp-content/plugins'
);

printf( "\n%d ok, %d lỗi\n", $pass, $fail );
exit( $fail ? 1 : 0 );

<?php
/**
 * Bắt lỗi vòng lặp ghi đè biến trong file template.
 *
 *   php scripts/template-scope.test.php
 *
 * File template chạy ở phạm vi file, không có hàm bao, nên một foreach dùng lại
 * tên biến đã gán ở đầu file sẽ ghi đè biến đó vĩnh viễn. Dùng lại biến sau
 * vòng lặp là lấy phải giá trị của lần lặp cuối.
 *
 * Đã xảy ra thật: template-parts/sections/carousel.php của Đầu tư quốc tịch gán
 * $att_id = ảnh "Hero — Icon góc dưới trái" ở đầu file, rồi vòng lặp bullets
 * viết `foreach ( $slides as $i => $att_id )`. Overlay bên dưới hiện ảnh slide
 * cuối cùng thay vì icon Hero. Không có lỗi PHP nào, chỉ sai ảnh — phải có người
 * nhìn thấy mới biết.
 *
 * Chỉ quét template-parts/ và page-templates/. File có khai báo function thì bỏ
 * qua, vì PHP cô lập phạm vi trong hàm nên cùng tên biến không ghi đè nhau.
 */

$roots = array( __DIR__ . '/../wp-content/plugins', __DIR__ . '/../wp-content/themes' );

$files = array();

foreach ( $roots as $root ) {
	if ( ! is_dir( $root ) ) {
		continue;
	}

	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $it as $f ) {
		$path = str_replace( '\\', '/', $f->getPathname() );

		if ( substr( $path, -4 ) !== '.php' ) {
			continue;
		}

		if ( false === strpos( $path, '/template-parts/' ) && false === strpos( $path, '/page-templates/' ) ) {
			continue;
		}

		$files[] = $path;
	}
}

sort( $files );

$problems = 0;
$checked  = 0;
$skipped  = 0;

foreach ( $files as $path ) {
	$lines = explode( "\n", (string) file_get_contents( $path ) );

	// Có hàm thì phạm vi phức tạp hơn nhiều, không đoán mò.
	$has_function = false;

	foreach ( $lines as $l ) {
		if ( preg_match( '/^\s*(?:private |public |protected |static )*function\s+\w/', $l ) ) {
			$has_function = true;
			break;
		}
	}

	if ( $has_function ) {
		$skipped++;
		continue;
	}

	$checked++;

	// Biến gán ở phạm vi file, ngoài foreach.
	$assigned = array();

	foreach ( $lines as $i => $l ) {
		if ( false !== strpos( $l, 'foreach' ) ) {
			continue;
		}

		if ( preg_match( '/^\s*(\$[a-zA-Z_]\w*)\s*=[^=]/', $l, $m ) ) {
			if ( ! isset( $assigned[ $m[1] ] ) ) {
				$assigned[ $m[1] ] = $i;
			}
		}
	}

	foreach ( $lines as $i => $l ) {
		if ( ! preg_match( '/foreach\s*\(.*?\bas\b\s*(?:\$\w+\s*=>\s*)?(\$[a-zA-Z_]\w*)\s*\)/', $l, $m ) ) {
			continue;
		}

		$var = $m[1];

		if ( ! isset( $assigned[ $var ] ) || $assigned[ $var ] >= $i ) {
			continue;
		}

		// Còn dùng lại sau vòng lặp không?
		$used_after = false;

		for ( $j = $i + 1; $j < count( $lines ); $j++ ) {
			$after = $lines[ $j ];

			if ( false !== strpos( $after, 'foreach' ) || '' === trim( $after ) ) {
				continue;
			}

			if ( false !== strpos( $after, $var ) ) {
				$used_after = true;
				break;
			}
		}

		if ( ! $used_after ) {
			continue;
		}

		$problems++;
		$rel = substr( $path, strpos( $path, '/wp-content/' ) + 1 );
		printf( "  LỖI   %s\n", $rel );
		printf( "        dòng %-4d gán %s\n", $assigned[ $var ] + 1, trim( $lines[ $assigned[ $var ] ] ) );
		printf( "        dòng %-4d foreach dùng lại chính %s, ghi đè giá trị trên\n", $i + 1, $var );
	}
}

printf( "\n  Đã kiểm %d file template (bỏ qua %d file có hàm), %d lỗi\n", $checked, $skipped, $problems );

if ( $problems ) {
	exit( 1 );
}

echo "\nOK — không template nào bị vòng lặp ghi đè biến.\n";

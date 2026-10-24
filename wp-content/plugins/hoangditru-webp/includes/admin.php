<?php
/**
 * Trang Media → Chuyển WebP: chuyển hàng loạt ảnh đang có trong thư viện.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin. Xem convert.php.
 *
 * Gói hosting không có SSH nên không trông vào WP-CLI được: trang này chạy từng
 * mẻ nhỏ qua admin-ajax, mỗi mẻ tối đa ~15 giây, trình duyệt gọi mẻ tiếp theo
 * khi mẻ trước xong. Đóng tab giữa chừng không sao — chạy lại thì ảnh đã có
 * WebP được bỏ qua rất nhanh. Có WP-CLI thì dùng: wp wsl convert [--force]
 */

defined( 'ABSPATH' ) || exit;

/**
 * ID attachment JPEG/PNG kế tiếp sau $after.
 *
 * @param int $after
 * @param int $limit
 * @return int[]
 */
function wsl_next_ids( $after, $limit ) {
	global $wpdb;

	$in = implode( ',', array_fill( 0, count( WSL_MIMES ), '%s' ) );

	return array_map(
		'intval',
		$wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ($in) AND ID > %d ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				array_merge( WSL_MIMES, array( (int) $after, (int) $limit ) )
			)
		)
	);
}

/**
 * Số ảnh JPEG/PNG trong thư viện và số ảnh đã được xử lý.
 *
 * @return array{total:int, done:int}
 */
function wsl_counts() {
	global $wpdb;

	$in    = implode( ',', array_fill( 0, count( WSL_MIMES ), '%s' ) );
	$total = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ($in)", WSL_MIMES ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
	);
	$done  = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = 'attachment' AND p.post_mime_type IN ($in)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			array_merge( array( WSL_META ), WSL_MIMES )
		)
	);

	return array(
		'total' => $total,
		'done'  => $done,
	);
}

/**
 * Một mẻ chuyển đổi.
 *
 * @param int  $after  ID cuối cùng của mẻ trước.
 * @param bool $force  Chuyển lại cả ảnh đã có WebP.
 * @param int  $budget Số giây tối đa.
 * @return array
 */
function wsl_run_batch( $after, $force = false, $budget = 15 ) {
	$start = microtime( true );
	$out   = array(
		'last'      => (int) $after,
		'finished'  => false,
		'images'    => 0,
		'converted' => 0,
		'failed'    => 0,
		'saved'     => 0,
	);

	while ( microtime( true ) - $start < $budget ) {
		$ids = wsl_next_ids( $out['last'], 10 );

		if ( ! $ids ) {
			$out['finished'] = true;
			break;
		}

		foreach ( $ids as $id ) {
			$s = wsl_convert_attachment( $id, null, $force );

			$out['last']       = $id;
			$out['images']    += 1;
			$out['converted'] += $s['converted'];
			$out['failed']    += $s['failed'];
			$out['saved']     += $s['saved'];

			if ( microtime( true ) - $start >= $budget ) {
				break 2;
			}
		}
	}

	return $out;
}

function wsl_admin_menu() {
	add_media_page(
		__( 'Chuyển ảnh sang WebP', 'wsl' ),
		__( 'Chuyển WebP', 'wsl' ),
		'manage_options',
		'wsl-webp',
		'wsl_admin_page'
	);
}
add_action( 'admin_menu', 'wsl_admin_menu' );

function wsl_admin_page() {
	$c       = wsl_counts();
	$editor  = function_exists( '_wp_image_editor_choose' ) ? _wp_image_editor_choose( array( 'mime_type' => 'image/webp' ) ) : '';
	$support = wsl_server_supports_webp();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Chuyển ảnh sang WebP', 'wsl' ); ?></h1>

		<?php if ( ! $support ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Máy chủ không ghi được WebP (thiếu GD có imagewebp hoặc Imagick có định dạng WEBP). Ảnh vẫn hiển thị bình thường bằng bản gốc.', 'wsl' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Mỗi ảnh và mọi kích thước của nó được lưu thêm một bản .webp nằm cạnh (ảnh.jpg → ảnh.jpg.webp). Ảnh gốc giữ nguyên. Bản WebP nào không nhỏ hơn ảnh gốc thì bị bỏ, trang tiếp tục dùng ảnh gốc.', 'wsl' ); ?></p>
		<p><?php esc_html_e( 'Ảnh upload từ giờ trở đi được chuyển tự động, không cần vào đây.', 'wsl' ); ?></p>

		<table class="widefat striped" style="max-width:640px">
			<tbody>
				<tr><th><?php esc_html_e( 'Trình xử lý ảnh', 'wsl' ); ?></th><td><code><?php echo esc_html( $editor ? $editor : '—' ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Chất lượng WebP', 'wsl' ); ?></th><td><?php echo (int) wsl_quality(); ?></td></tr>
				<tr><th><?php esc_html_e( 'Ảnh JPEG/PNG trong thư viện', 'wsl' ); ?></th><td id="wsl-total"><?php echo (int) $c['total']; ?></td></tr>
				<tr><th><?php esc_html_e( 'Đã xử lý', 'wsl' ); ?></th><td id="wsl-done"><?php echo (int) $c['done']; ?></td></tr>
			</tbody>
		</table>

		<p style="margin-top:16px">
			<label><input type="checkbox" id="wsl-force"> <?php esc_html_e( 'Chuyển lại cả ảnh đã có WebP (sau khi đổi chất lượng)', 'wsl' ); ?></label>
		</p>
		<p>
			<button type="button" class="button button-primary" id="wsl-start" <?php disabled( ! $support ); ?>><?php esc_html_e( 'Chuyển tất cả', 'wsl' ); ?></button>
			<button type="button" class="button" id="wsl-stop" disabled><?php esc_html_e( 'Dừng', 'wsl' ); ?></button>
		</p>

		<progress id="wsl-bar" max="<?php echo (int) max( 1, $c['total'] ); ?>" value="0" style="width:100%;max-width:640px;height:18px"></progress>
		<p id="wsl-log" aria-live="polite"></p>
	</div>
	<?php
}

function wsl_admin_assets( $hook ) {
	if ( 'media_page_wsl-webp' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'wsl-admin', WSL_URL . 'assets/admin.js', array(), WSL_VERSION, true );
	wp_localize_script(
		'wsl-admin',
		'wslAdmin',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wsl_bulk' ),
			'i18n'  => array(
				'running' => __( 'Đang chuyển… %1$d ảnh, %2$d file WebP, tiết kiệm %3$s.', 'wsl' ),
				'done'    => __( 'Xong. %1$d ảnh, %2$d file WebP mới, tiết kiệm %3$s. Nhớ Purge All trong LiteSpeed Cache.', 'wsl' ),
				'stopped' => __( 'Đã dừng. Bấm "Chuyển tất cả" để chạy tiếp — ảnh đã xong sẽ được bỏ qua.', 'wsl' ),
				'error'   => __( 'Lỗi: %s. Bấm "Chuyển tất cả" để thử lại.', 'wsl' ),
				'failed'  => __( '%d file không chuyển được (ảnh hỏng hoặc thiếu file).', 'wsl' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'wsl_admin_assets' );

function wsl_ajax_bulk() {
	check_ajax_referer( 'wsl_bulk', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	wp_raise_memory_limit( 'image' );

	$after = isset( $_POST['after'] ) ? absint( $_POST['after'] ) : 0;
	$force = ! empty( $_POST['force'] );
	$out   = wsl_run_batch( $after, $force, 15 );

	if ( $out['finished'] ) {
		wsl_lscwp_purge();
	}

	$out['counts'] = wsl_counts();
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_wsl_bulk', 'wsl_ajax_bulk' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Chuyển toàn bộ thư viện Media sang WebP.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Chuyển lại cả ảnh đã có WebP.
	 */
	WP_CLI::add_command(
		'wsl convert',
		static function ( $args, $assoc ) {
			$after = 0;
			$total = array( 'images' => 0, 'converted' => 0, 'failed' => 0, 'saved' => 0 );
			do {
				$out = wsl_run_batch( $after, ! empty( $assoc['force'] ), 30 );
				foreach ( $total as $k => $v ) {
					$total[ $k ] += $out[ $k ];
				}
				$after = $out['last'];
				WP_CLI::log( sprintf( '… tới ID %d: %d ảnh, %d file WebP', $after, $total['images'], $total['converted'] ) );
			} while ( ! $out['finished'] );
			wsl_lscwp_purge();
			WP_CLI::success( sprintf( '%d ảnh, %d file WebP mới, %d lỗi, tiết kiệm %s.', $total['images'], $total['converted'], $total['failed'], size_format( $total['saved'] ) ) );
		}
	);
}

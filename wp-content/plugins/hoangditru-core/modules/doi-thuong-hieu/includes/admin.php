<?php
/**
 * Trang Công cụ → Đổi tên thương hiệu: quét database, xem trước, rồi thay.
 *
 * Chỉ administrator (`manage_options`). Biên tập viên không vào được Công cụ.
 *
 * Quét những gì:
 *
 *   wp_posts      post_title, post_content, post_excerpt — mọi post type trừ
 *                 revision; post_name (slug) — trừ revision, attachment,
 *                 nav_menu_item. Slug cũ ghi vào _wp_old_slug để link cũ 301.
 *   wp_postmeta   mọi khoá trừ HDTTH_BO_META (đường dẫn file ảnh, dấu nội bộ).
 *   wp_options    trừ transient, cron, rewrite_rules, siteurl, home.
 *   wp_terms      name, slug (slug cũ ghi vào term meta _hdt_old_slug).
 *   wp_term_taxonomy description.
 *   wp_termmeta   mọi khoá trừ _hdt_old_slug.
 *
 * Không đụng: wp_users, wp_usermeta, wp_comments, guid.
 */

defined( 'ABSPATH' ) || exit;

const HDTTH_PAGE   = 'hdt-doi-thuong-hieu';
const HDTTH_ACTION = 'hdt_doi_thuong_hieu';

/** Term meta lưu slug cũ của term, để chuyen-huong.php 301 về slug mới. */
const HDTTH_TERM_OLD_SLUG = '_hdt_old_slug';

/** Meta không đụng: đường dẫn file thật trên đĩa, và dấu nội bộ của WordPress. */
const HDTTH_BO_META = array(
	'_wp_attached_file',
	'_wp_attachment_metadata',
	'_wp_attachment_backup_sizes',
	'_wsl_webp',
	'_wp_old_slug',
	'_wp_old_date',
	'_wp_page_template',
	'_edit_lock',
	'_edit_last',
);

/** Option không đụng. Transient lọc riêng bằng tiền tố. */
const HDTTH_BO_OPTION = array( 'cron', 'rewrite_rules', 'siteurl', 'home', 'active_plugins', 'recently_activated' );

/** Giới hạn số dòng hiện trong bảng xem trước. Tổng số vẫn đếm đủ. */
const HDTTH_HIEN_TOI_DA = 400;

add_action( 'admin_menu', 'hdtth_menu' );
function hdtth_menu() {
	add_management_page( 'Đổi tên thương hiệu', 'Đổi tên thương hiệu', 'manage_options', HDTTH_PAGE, 'hdtth_render' );
}

add_action( 'admin_post_' . HDTTH_ACTION, 'hdtth_xu_ly' );

/**
 * Số mới đúng như ô Số điện thoại trong Thông tin liên hệ.
 *
 * @return string
 */
function hdtth_sdt_moi() {
	return function_exists( 'cim_get' ) ? trim( (string) cim_get( 'phone' ) ) : '';
}

/**
 * Lý do chưa chạy được, hoặc '' nếu chạy được.
 *
 * @param string $sdt Số mới.
 * @return string
 */
function hdtth_loi_sdt( $sdt ) {
	if ( '' === $sdt ) {
		return 'Chưa có số điện thoại trong Thông tin liên hệ. Nhập số trước rồi quay lại trang này.';
	}

	$so = ltrim( hdtth_chu_so( $sdt ), '+' );
	if ( strlen( $so ) < 9 ) {
		return 'Số trong Thông tin liên hệ ("' . $sdt . '") không giống số điện thoại.';
	}

	if ( preg_match( '/^(?:84)?0?906225610$/', $so ) ) {
		return 'Số trong Thông tin liên hệ vẫn là số cũ 0906.225.610. Đổi sang số mới trước.';
	}

	return '';
}

/**
 * Điều kiện LIKE lọc thô ở tầng SQL. Lỏng hơn regex nhiều — chỉ để khỏi kéo cả
 * bảng về PHP; regex mới là thứ quyết định.
 *
 * Collation utf8mb4_unicode_ci không phân biệt dấu và hoa thường, nên
 * '%kh%i%d%i%tr%' bắt được mọi dạng "Khải di trú".
 *
 * @param string $col Tên cột.
 * @return string
 */
function hdtth_like( $col ) {
	return "( {$col} LIKE '%kh%i%d%i%tr%' OR {$col} LIKE '%9%0%6%2%2%5%6%1%0%' )";
}

/**
 * Quét (và nếu $ghi thì thay) toàn bộ database.
 *
 * @param string $sdt Số mới.
 * @param bool   $ghi true để ghi thật.
 * @return array{muc: array, bo_qua: array, tong: array}
 */
function hdtth_quet( $sdt, $ghi = false ) {
	global $wpdb;

	$kq = array(
		'muc'    => array(),
		'bo_qua' => array(),
		'tong'   => array( 'ten' => 0, 'sdt' => 0, 'slug' => 0, 'dong' => 0 ),
	);

	$ghi_nhan = static function ( $noi, $cu, $moi, array $dem, $link = '' ) use ( &$kq ) {
		$kq['tong']['ten']  += $dem['ten'] ?? 0;
		$kq['tong']['sdt']  += $dem['sdt'] ?? 0;
		$kq['tong']['slug'] += $dem['slug'] ?? 0;
		$kq['tong']['dong']++;

		if ( count( $kq['muc'] ) < HDTTH_HIEN_TOI_DA ) {
			list( $truoc, $sau ) = hdtth_trich( $cu, $moi );
			$kq['muc'][] = compact( 'noi', 'truoc', 'sau', 'link' ) + array( 'dem' => $dem );
		}
	};

	$bo_qua = static function ( $noi, $ly_do ) use ( &$kq ) {
		$kq['bo_qua'][] = array( 'noi' => $noi, 'ly_do' => $ly_do );
	};

	// ── wp_posts: chữ ───────────────────────────────────────────────────────
	$cols = array( 'post_title', 'post_content', 'post_excerpt' );
	$where = implode( ' OR ', array_map( 'hdtth_like', $cols ) );
	$rows  = $wpdb->get_results(
		"SELECT ID, post_type, post_status, post_title, post_content, post_excerpt
		   FROM {$wpdb->posts}
		  WHERE post_type <> 'revision' AND post_status <> 'auto-draft' AND ( {$where} )"
	);

	foreach ( $rows as $r ) {
		$doi = array();

		foreach ( $cols as $c ) {
			$dem   = array();
			$ly_do = null;
			$moi   = hdtth_thay_gia_tri( $r->$c, $sdt, $dem, $ly_do );
			$noi   = sprintf( 'Bài #%d (%s) · %s', $r->ID, $r->post_type, $c );

			if ( $ly_do ) {
				$bo_qua( $noi, $ly_do );
			}
			if ( null === $moi ) {
				continue;
			}

			$doi[ $c ] = $moi;
			$ghi_nhan( $noi, $r->$c, $moi, $dem, get_edit_post_link( $r->ID, 'raw' ) );
		}

		if ( $ghi && $doi ) {
			$wpdb->update( $wpdb->posts, $doi, array( 'ID' => $r->ID ) );
			clean_post_cache( $r->ID );
		}
	}

	// ── wp_posts: slug ──────────────────────────────────────────────────────
	$rows = $wpdb->get_results(
		"SELECT ID, post_type, post_status, post_parent, post_name
		   FROM {$wpdb->posts}
		  WHERE post_type NOT IN ( 'revision', 'attachment', 'nav_menu_item' )
		    AND post_status NOT IN ( 'auto-draft', 'trash' )
		    AND post_name LIKE '%kh%i%d%i%tr%'"
	);

	foreach ( $rows as $r ) {
		$moi = hdtth_slug_moi( $r->post_name );
		if ( null === $moi ) {
			continue;
		}

		$moi = wp_unique_post_slug( $moi, $r->ID, $r->post_status, $r->post_type, $r->post_parent );
		$ghi_nhan(
			sprintf( 'Bài #%d (%s) · slug', $r->ID, $r->post_type ),
			$r->post_name,
			$moi,
			array( 'slug' => 1 ),
			get_edit_post_link( $r->ID, 'raw' )
		);

		if ( $ghi ) {
			$wpdb->update( $wpdb->posts, array( 'post_name' => $moi ), array( 'ID' => $r->ID ) );

			// Như wp_check_for_changed_slugs(): chỉ bài đã xuất bản mới có link
			// cũ đáng giữ. Khác ở chỗ ghi cả post type phân cấp (Trang, BĐS) —
			// chuyen-huong.php lo phần redirect mà WordPress bỏ qua cho chúng.
			if ( 'publish' === $r->post_status ) {
				$cu = get_post_meta( $r->ID, '_wp_old_slug' );
				if ( ! in_array( $r->post_name, (array) $cu, true ) ) {
					add_post_meta( $r->ID, '_wp_old_slug', $r->post_name );
				}
			}
			clean_post_cache( $r->ID );
		}
	}

	// ── wp_postmeta ─────────────────────────────────────────────────────────
	$bo   = "'" . implode( "','", array_map( 'esc_sql', HDTTH_BO_META ) ) . "'";
	$rows = $wpdb->get_results(
		"SELECT meta_id, post_id, meta_key, meta_value
		   FROM {$wpdb->postmeta}
		  WHERE meta_key NOT IN ( {$bo} ) AND " . hdtth_like( 'meta_value' )
	);

	foreach ( $rows as $r ) {
		$dem   = array();
		$ly_do = null;
		$moi   = hdtth_thay_gia_tri( $r->meta_value, $sdt, $dem, $ly_do );
		$noi   = sprintf( 'Meta bài #%d · %s', $r->post_id, $r->meta_key );

		if ( $ly_do ) {
			$bo_qua( $noi, $ly_do );
		}
		if ( null === $moi ) {
			continue;
		}

		$ghi_nhan( $noi, $r->meta_value, $moi, $dem, get_edit_post_link( $r->post_id, 'raw' ) );

		if ( $ghi ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $moi ), array( 'meta_id' => $r->meta_id ) );
			wp_cache_delete( $r->post_id, 'post_meta' );
		}
	}

	// ── wp_options ──────────────────────────────────────────────────────────
	$bo   = "'" . implode( "','", array_map( 'esc_sql', HDTTH_BO_OPTION ) ) . "'";
	$rows = $wpdb->get_results(
		"SELECT option_id, option_name, option_value
		   FROM {$wpdb->options}
		  WHERE option_name NOT IN ( {$bo} )
		    AND option_name NOT LIKE '\\_transient\\_%'
		    AND option_name NOT LIKE '\\_site\\_transient\\_%'
		    AND " . hdtth_like( 'option_value' )
	);

	foreach ( $rows as $r ) {
		$dem   = array();
		$ly_do = null;
		$moi   = hdtth_thay_gia_tri( $r->option_value, $sdt, $dem, $ly_do );
		$noi   = 'Option · ' . $r->option_name;

		if ( $ly_do ) {
			$bo_qua( $noi, $ly_do );
		}
		if ( null === $moi ) {
			continue;
		}

		$ghi_nhan( $noi, $r->option_value, $moi, $dem );

		if ( $ghi ) {
			$wpdb->update( $wpdb->options, array( 'option_value' => $moi ), array( 'option_id' => $r->option_id ) );
			wp_cache_delete( $r->option_name, 'options' );
		}
	}

	if ( $ghi ) {
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	// ── wp_terms + wp_term_taxonomy ─────────────────────────────────────────
	$rows = $wpdb->get_results(
		"SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.taxonomy, tt.description
		   FROM {$wpdb->terms} t
		   JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
		  WHERE " . hdtth_like( 't.name' ) . " OR t.slug LIKE '%kh%i%d%i%tr%' OR " . hdtth_like( 'tt.description' )
	);

	foreach ( $rows as $r ) {
		$noi_goc = sprintf( 'Term #%d (%s)', $r->term_id, $r->taxonomy );
		$link    = get_edit_term_link( (int) $r->term_id, $r->taxonomy );
		$terms   = array();

		$dem = array();
		$moi = hdtth_thay_gia_tri( $r->name, $sdt, $dem );
		if ( null !== $moi ) {
			$terms['name'] = $moi;
			$ghi_nhan( $noi_goc . ' · name', $r->name, $moi, $dem, $link );
		}

		$slug = hdtth_slug_moi( $r->slug );
		if ( null !== $slug ) {
			$goc = $slug;
			for ( $n = 2; ( $khac = get_term_by( 'slug', $slug, $r->taxonomy ) ) && (int) $khac->term_id !== (int) $r->term_id; $n++ ) {
				$slug = $goc . '-' . $n;
			}
			$terms['slug'] = $slug;
			$ghi_nhan( $noi_goc . ' · slug', $r->slug, $slug, array( 'slug' => 1 ), $link );
		}

		$dem  = array();
		$mota = hdtth_thay_gia_tri( $r->description, $sdt, $dem );
		if ( null !== $mota ) {
			$ghi_nhan( $noi_goc . ' · description', $r->description, $mota, $dem, $link );
		}

		if ( $ghi ) {
			if ( $terms ) {
				$wpdb->update( $wpdb->terms, $terms, array( 'term_id' => $r->term_id ) );
			}
			if ( isset( $terms['slug'] ) ) {
				add_term_meta( (int) $r->term_id, HDTTH_TERM_OLD_SLUG, $r->slug );
			}
			if ( null !== $mota ) {
				$wpdb->update( $wpdb->term_taxonomy, array( 'description' => $mota ), array( 'term_taxonomy_id' => $r->term_taxonomy_id ) );
			}
			if ( $terms || null !== $mota ) {
				clean_term_cache( (int) $r->term_id, $r->taxonomy );
			}
		}
	}

	// ── wp_termmeta ─────────────────────────────────────────────────────────
	// Không dùng $wpdb->prepare(): mẫu LIKE có '%k', '%9'… mà prepare() sẽ
	// hiểu nhầm là placeholder. Giá trị ở đây đều là hằng của code.
	$rows = $wpdb->get_results(
		"SELECT meta_id, term_id, meta_key, meta_value
		   FROM {$wpdb->termmeta}
		  WHERE meta_key <> '" . esc_sql( HDTTH_TERM_OLD_SLUG ) . "' AND " . hdtth_like( 'meta_value' )
	);

	foreach ( $rows as $r ) {
		$dem   = array();
		$ly_do = null;
		$moi   = hdtth_thay_gia_tri( $r->meta_value, $sdt, $dem, $ly_do );
		$noi   = sprintf( 'Meta term #%d · %s', $r->term_id, $r->meta_key );

		if ( $ly_do ) {
			$bo_qua( $noi, $ly_do );
		}
		if ( null === $moi ) {
			continue;
		}

		$ghi_nhan( $noi, $r->meta_value, $moi, $dem );

		if ( $ghi ) {
			$wpdb->update( $wpdb->termmeta, array( 'meta_value' => $moi ), array( 'meta_id' => $r->meta_id ) );
			wp_cache_delete( $r->term_id, 'term_meta' );
		}
	}

	return $kq;
}

/**
 * Nút "Thay thế": kiểm quyền, nonce, ô xác nhận backup, rồi ghi.
 */
function hdtth_xu_ly() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.', 403 );
	}

	check_admin_referer( HDTTH_ACTION );

	$quay_lai = admin_url( 'tools.php?page=' . HDTTH_PAGE );
	$sdt      = hdtth_sdt_moi();

	if ( hdtth_loi_sdt( $sdt ) || empty( $_POST['da_backup'] ) ) {
		wp_safe_redirect( add_query_arg( 'hdtth', 'chua', $quay_lai ) );
		exit;
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore
	}

	$kq = hdtth_quet( $sdt, true );

	wp_cache_flush();
	do_action( 'litespeed_purge_all' );

	set_transient( 'hdtth_ket_qua_' . get_current_user_id(), $kq['tong'], 15 * MINUTE_IN_SECONDS );

	wp_safe_redirect( add_query_arg( 'hdtth', 'xong', $quay_lai ) );
	exit;
}

/**
 * Trang xem trước.
 */
function hdtth_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$sdt = hdtth_sdt_moi();
	$loi = hdtth_loi_sdt( $sdt );
	$kq  = hdtth_quet( $sdt, false );
	$t   = $kq['tong'];
	?>
	<div class="wrap">
		<h1>Đổi tên thương hiệu</h1>
		<p>Thay <strong>Khải Di Trú</strong> (mọi kiểu hoa thường, có dấu, không dấu, viết liền như
			<code>khaiditru</code>) thành <strong>Hoàng Di Trú</strong>, và số <strong>0906.225.610</strong>
			(mọi cách viết) thành số trong <a href="<?php echo esc_url( admin_url( 'admin.php?page=contact-info-manager' ) ); ?>">Thông tin liên hệ</a>.</p>

		<?php if ( isset( $_GET['hdtth'] ) && 'xong' === $_GET['hdtth'] ) : // phpcs:ignore
			$xong = get_transient( 'hdtth_ket_qua_' . get_current_user_id() );
			delete_transient( 'hdtth_ket_qua_' . get_current_user_id() );
			if ( $xong ) : ?>
			<div class="notice notice-success"><p>Đã thay <?php echo (int) $xong['dong']; ?> chỗ:
				<?php echo (int) $xong['ten']; ?> tên, <?php echo (int) $xong['sdt']; ?> số điện thoại,
				<?php echo (int) $xong['slug']; ?> slug. Cache đã được xoá.</p></div>
		<?php endif; endif; ?>

		<?php if ( isset( $_GET['hdtth'] ) && 'chua' === $_GET['hdtth'] ) : // phpcs:ignore ?>
			<div class="notice notice-error"><p>Chưa chạy: phải tick ô xác nhận đã backup database.</p></div>
		<?php endif; ?>

		<div class="notice notice-warning inline"><p>
			<strong>Backup database trước</strong> (hPanel → Databases → phpMyAdmin → Export). Việc thay
			ghi thẳng vào database và <strong>không có nút hoàn tác</strong>.<br>
			Số mới được ghi cứng vào nội dung: sau này đổi số trong Thông tin liên hệ thì các chỗ đã thay
			<strong>không</strong> tự đổi theo.<br>
			Tên file ảnh trong uploads giữ nguyên (đổi là vỡ ảnh). Slug đổi thì link cũ tự chuyển 301 sang slug mới.
		</p></div>

		<p>Số mới: <strong><?php echo $sdt ? esc_html( $sdt ) : '—'; ?></strong>
			<?php if ( $sdt && ! $loi ) : ?>(trong link tel: ghi là <code><?php echo esc_html( hdtth_chu_so( $sdt ) ); ?></code>)<?php endif; ?></p>

		<?php if ( $loi ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $loi ); ?></p></div>
		<?php endif; ?>

		<h2>Xem trước: <?php echo (int) $t['dong']; ?> chỗ cần thay</h2>
		<p><?php echo (int) $t['ten']; ?> tên · <?php echo (int) $t['sdt']; ?> số điện thoại · <?php echo (int) $t['slug']; ?> slug</p>

		<?php if ( $kq['muc'] ) : ?>
			<?php if ( $t['dong'] > count( $kq['muc'] ) ) : ?>
				<p><em>Chỉ hiện <?php echo count( $kq['muc'] ); ?> dòng đầu; nút bên dưới thay đủ <?php echo (int) $t['dong']; ?> chỗ.</em></p>
			<?php endif; ?>
			<table class="widefat striped" style="table-layout:fixed">
				<thead><tr><th style="width:22%">Nơi</th><th>Trước</th><th>Sau</th></tr></thead>
				<tbody>
				<?php foreach ( $kq['muc'] as $m ) : ?>
					<tr>
						<td><?php echo $m['link'] ? '<a href="' . esc_url( $m['link'] ) . '">' . esc_html( $m['noi'] ) . '</a>' : esc_html( $m['noi'] ); ?></td>
						<td style="word-break:break-word"><code><?php echo esc_html( $m['truoc'] ); ?></code></td>
						<td style="word-break:break-word"><code><?php echo esc_html( $m['sau'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( $kq['bo_qua'] ) : ?>
			<h2>Bỏ qua — cần sửa tay (<?php echo count( $kq['bo_qua'] ); ?>)</h2>
			<ul style="list-style:disc;padding-left:2em">
				<?php foreach ( $kq['bo_qua'] as $b ) : ?>
					<li><?php echo esc_html( $b['noi'] . ': ' . $b['ly_do'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $t['dong'] && ! $loi ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( HDTTH_ACTION ); ?>">
				<?php wp_nonce_field( HDTTH_ACTION ); ?>
				<p><label><input type="checkbox" name="da_backup" value="1" required> Tôi đã backup database</label></p>
				<?php submit_button( 'Thay thế ' . (int) $t['dong'] . ' chỗ', 'primary', 'submit', false ); ?>
			</form>
		<?php elseif ( ! $t['dong'] ) : ?>
			<p><strong>Không còn chỗ nào cần thay.</strong></p>
		<?php endif; ?>
	</div>
	<?php
}

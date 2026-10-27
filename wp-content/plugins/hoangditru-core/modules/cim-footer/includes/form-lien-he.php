<?php
/**
 * Form "Yêu Cầu Gọi Lại" ở chân trang, thay cho Contact Form 7.
 *
 * Giữ nguyên bốn trường và nguyên dáng của form CF7 cũ (id cc8d593 / post 542):
 * cùng nhãn, cùng thứ tự, cùng cấu trúc p > label > br > span > input nên
 * khoảng cách dòng không đổi. Chỉ tên class đổi từ wpcf7-* sang cimf-*, và CSS
 * đổi selector theo — từng khai báo giữ y nguyên.
 *
 * Mail nhận là admin_email của site, đọc lúc gửi chứ không chép cứng, cộng
 * thêm ô Email ở trang Thông tin liên hệ nếu có đặt — cùng ô đang hiện cho
 * khách xem ở chân trang.
 *
 * ── Vì sao KHÔNG có nonce ───────────────────────────────────────────────────
 * Trang chân trang nằm trong cache toàn trang của LiteSpeed. Nonce của
 * WordPress hết hạn sau 12–24 giờ, nên một trang cache lâu hơn thế sẽ mang
 * nonce chết và form lặng lẽ không gửi được — không có lỗi nào để lần ra. Mà
 * nonce ở đây chẳng bảo vệ gì: kịch bản CSRF tệ nhất là dụ trình duyệt ai đó
 * gửi cho chính chúng ta một cái email. CF7 bỏ nonce với khách chưa đăng nhập
 * cũng vì đúng lý do này.
 *
 * Chống spam thay thế: trường bẫy, thời gian điền tối thiểu, giới hạn theo IP.
 */

defined( 'ABSPATH' ) || exit;

/** Tên action của admin-post.php. */
const CIMF_FORM_ACTION = 'cimf_lien_he';

/** Trường bẫy. Bot điền hết mọi input nên trường này có giá trị là bot. */
const CIMF_FORM_HONEYPOT = 'website';

/** Số giây tối thiểu từ lúc mở trang đến lúc bấm gửi. */
const CIMF_FORM_MIN_SECONDS = 3;

/** Số lần gửi tối đa của một IP trong CIMF_FORM_WINDOW giây. */
const CIMF_FORM_MAX_TRIES = 5;

/** Độ dài cửa sổ giới hạn, giây. */
const CIMF_FORM_WINDOW = 600;

/**
 * Bốn trường của form, theo đúng thứ tự hiển thị.
 *
 * Một nguồn dùng cho cả ba việc: dựng HTML, kiểm dữ liệu gửi lên, và soạn nội
 * dung mail. Thêm trường thì chỉ sửa ở đây.
 *
 * Tên trường đổi sang tiếng Việt không dấu thay cho tên CF7 cũ (your-name,
 * tel-781…): tên đó không nằm trong database nên đổi không mất gì, mà `tel-781`
 * thì không ai đọc ra là gì.
 *
 * @var array<string,array<string,mixed>>
 */
const CIMF_FORM_FIELDS = array(
	'ten'         => array(
		'label'        => 'Tên của bạn',
		'type'         => 'text',
		'required'     => true,
		'maxlength'    => 400,
		'autocomplete' => 'name',
		'mail_label'   => 'Tên',
	),
	'dien_thoai'  => array(
		'label'        => 'Số điện thoại của bạn',
		'type'         => 'tel',
		'required'     => true,
		'maxlength'    => 400,
		'autocomplete' => 'tel',
		'mail_label'   => 'Số điện thoại',
	),
	'tieu_de'     => array(
		'label'      => 'Tiêu đề:',
		'type'       => 'text',
		'required'   => true,
		'maxlength'  => 400,
		'mail_label' => 'Tiêu đề',
	),
	'tin_nhan'    => array(
		'label'      => 'Tin nhắn của bạn (không bắt buộc)',
		'type'       => 'textarea',
		'required'   => false,
		'maxlength'  => 2000,
		'rows'       => 10,
		'mail_label' => 'Tin nhắn',
	),
);

// ═══════════════════════════════════════════════════════════════
// 1. DỰNG FORM
// ═══════════════════════════════════════════════════════════════

/**
 * In form ra chân trang.
 */
function cimf_form_render() {
	$notice = cimf_form_notice();
	?>
	<div class="cimf-form-wrap">
		<form class="cimf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="<?php echo esc_attr( CIMF_FORM_ACTION ); ?>">

			<?php
			/*
			 * Số giây người dùng ngồi điền, do JS đặt lúc bấm gửi. Để rỗng ở
			 * HTML là có ý: in sẵn time() của máy chủ thì trang cache mang theo
			 * thời điểm nạp cache, và phép trừ ra một con số vô nghĩa.
			 */
			?>
			<input type="hidden" name="cimf_giay" value="">
			<input type="hidden" name="cimf_tu" value="<?php echo esc_url( cimf_form_current_url() ); ?>">

			<p class="cimf-honeypot" aria-hidden="true">
				<label>Website
					<input type="text" name="<?php echo esc_attr( CIMF_FORM_HONEYPOT ); ?>" value="" tabindex="-1" autocomplete="off">
				</label>
			</p>

			<?php foreach ( CIMF_FORM_FIELDS as $name => $field ) : ?>
				<p><label> <?php echo esc_html( $field['label'] ); ?><br />
					<span class="cimf-field" data-name="<?php echo esc_attr( $name ); ?>">
						<?php cimf_form_control( $name, $field ); ?>
					</span> </label>
				</p>
			<?php endforeach; ?>

			<p><input class="cimf-submit" type="submit" value="Gửi" />
			</p>
			<?php
			/*
			 * Không được có khoảng trắng giữa hai thẻ: CSS ẩn ô này bằng :empty,
			 * mà một dấu cách cũng là nội dung nên khung rỗng sẽ hiện ra.
			 */
			?>
			<div class="<?php echo esc_attr( trim( 'cimf-response ' . $notice['class'] ) ); ?>" role="status" aria-live="polite"><?php echo esc_html( $notice['text'] ); ?></div>
		</form>
	</div>
	<?php
}

/**
 * In một ô nhập.
 *
 * @param string $name  Tên trường.
 * @param array  $field Khai báo trường trong CIMF_FORM_FIELDS.
 */
function cimf_form_control( $name, array $field ) {
	$attrs = array(
		'name'      => $name,
		'maxlength' => (string) $field['maxlength'],
	);

	if ( ! empty( $field['required'] ) ) {
		$attrs['required']      = 'required';
		$attrs['aria-required'] = 'true';
	}

	if ( ! empty( $field['autocomplete'] ) ) {
		$attrs['autocomplete'] = $field['autocomplete'];
	}

	if ( 'textarea' === $field['type'] ) {
		$attrs['rows'] = (string) $field['rows'];

		printf( '<textarea %s></textarea>', cimf_form_attrs( $attrs ) ); // phpcs:ignore WordPress.Security.EscapeOutput

		return;
	}

	$attrs['type']  = $field['type'];
	$attrs['value'] = '';

	printf( '<input %s />', cimf_form_attrs( $attrs ) ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Nối mảng thuộc tính thành chuỗi HTML đã escape.
 *
 * @param array<string,string> $attrs Thuộc tính.
 * @return string
 */
function cimf_form_attrs( array $attrs ) {
	$out = array();

	foreach ( $attrs as $key => $value ) {
		$out[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	return implode( ' ', $out );
}

/**
 * URL trang đang xem, để ghi vào mail.
 *
 * @return string
 */
function cimf_form_current_url() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

	return home_url( $path );
}

// ═══════════════════════════════════════════════════════════════
// 2. NHẬN VÀ KIỂM DỮ LIỆU
// ═══════════════════════════════════════════════════════════════

add_action( 'admin_post_nopriv_' . CIMF_FORM_ACTION, 'cimf_form_handle' );
add_action( 'admin_post_' . CIMF_FORM_ACTION, 'cimf_form_handle' );
/**
 * Xử lý một lượt gửi form.
 *
 * Hai đường vào cùng một hàm: JS gửi kèm cimf_ajax=1 và nhận JSON; không có JS
 * thì form POST như thường rồi được chuyển về đúng trang cũ kèm kết quả ở query
 * string. Một đường kiểm dữ liệu duy nhất cho cả hai.
 */
function cimf_form_handle() {
	$ajax = ! empty( $_POST['cimf_ajax'] );

	$spam = cimf_form_spam_reason( $_POST );

	if ( '' !== $spam ) {
		cimf_form_respond( $ajax, false, $spam, array() );
	}

	$values = cimf_form_clean( $_POST );
	$errors = cimf_form_errors( $values );

	if ( $errors ) {
		cimf_form_respond( $ajax, false, 'Vui lòng kiểm tra lại các trường còn thiếu.', $errors );
	}

	cimf_form_count_try();

	$sent = cimf_form_send_mail( $values, cimf_form_source_url() );

	if ( ! $sent ) {
		cimf_form_respond(
			$ajax,
			false,
			'Hệ thống chưa gửi được yêu cầu. Vui lòng gọi trực tiếp số hotline ở chân trang.',
			array()
		);
	}

	cimf_form_respond( $ajax, true, 'Đã nhận yêu cầu. Chúng tôi sẽ gọi lại cho bạn trong thời gian sớm nhất.', array() );
}

/**
 * Lượt gửi này có dấu hiệu spam không.
 *
 * @param array $post Dữ liệu $_POST.
 * @return string Thông báo cho người gửi, rỗng nghĩa là không có dấu hiệu gì.
 */
function cimf_form_spam_reason( array $post ) {
	if ( ! empty( $post[ CIMF_FORM_HONEYPOT ] ) ) {
		return 'Không gửi được yêu cầu.';
	}

	/*
	 * Chỉ kiểm khi JS có đặt số giây. Bot bỏ trường này thì vẫn còn trường bẫy
	 * và giới hạn theo IP chặn — không đáng vì một lớp mà chặn oan người tắt JS.
	 */
	if ( isset( $post['cimf_giay'] ) && '' !== $post['cimf_giay']
		&& absint( $post['cimf_giay'] ) < CIMF_FORM_MIN_SECONDS ) {
		return 'Bạn gửi quá nhanh. Vui lòng kiểm tra lại thông tin rồi gửi lần nữa.';
	}

	if ( cimf_form_tries() >= CIMF_FORM_MAX_TRIES ) {
		return 'Bạn vừa gửi nhiều yêu cầu liên tiếp. Vui lòng thử lại sau vài phút.';
	}

	return '';
}

/**
 * Lọc dữ liệu thô thành giá trị sạch theo từng trường.
 *
 * @param array $post Dữ liệu $_POST.
 * @return array<string,string>
 */
function cimf_form_clean( array $post ) {
	$values = array();

	foreach ( CIMF_FORM_FIELDS as $name => $field ) {
		$raw = isset( $post[ $name ] ) ? wp_unslash( $post[ $name ] ) : '';

		// Mảng gửi lên thay cho chuỗi: bỏ, không cố đoán ý.
		if ( ! is_string( $raw ) ) {
			$values[ $name ] = '';
			continue;
		}

		$clean = 'textarea' === $field['type']
			? sanitize_textarea_field( $raw )
			: sanitize_text_field( $raw );

		$values[ $name ] = cimf_form_truncate( $clean, (int) $field['maxlength'] );
	}

	return $values;
}

/**
 * Cắt chuỗi theo số KÝ TỰ, chặn đúng mức maxlength của ô nhập.
 *
 * Không dùng mb_substr: WordPress có polyfill cho nó, nhưng test chạy không có
 * WordPress và máy dev không bật mbstring. Không dùng substr: nó cắt theo byte
 * nên "Nguyễn" bị chặt giữa một ký tự và ra ký tự lỗi. PCRE với cờ /u thì có ở
 * mọi bản PHP bình thường.
 *
 * @param string $text Chuỗi vào.
 * @param int    $max  Số ký tự tối đa.
 * @return string
 */
function cimf_form_truncate( $text, $max ) {
	$chars = preg_split( '//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY );

	if ( ! is_array( $chars ) || count( $chars ) <= $max ) {
		return (string) $text;
	}

	return implode( '', array_slice( $chars, 0, $max ) );
}

/**
 * Lỗi của từng trường.
 *
 * @param array<string,string> $values Giá trị đã lọc.
 * @return array<string,string> tên trường => thông báo.
 */
function cimf_form_errors( array $values ) {
	$errors = array();

	foreach ( CIMF_FORM_FIELDS as $name => $field ) {
		if ( ! empty( $field['required'] ) && '' === ( $values[ $name ] ?? '' ) ) {
			$errors[ $name ] = 'Vui lòng điền trường này.';
		}
	}

	if ( empty( $errors['dien_thoai'] ) && ! cimf_form_phone_ok( $values['dien_thoai'] ?? '' ) ) {
		$errors['dien_thoai'] = 'Số điện thoại chưa đúng.';
	}

	return $errors;
}

/**
 * Số điện thoại có dùng được không.
 *
 * Cố ý KHÔNG dùng mẫu số Việt Nam `^(\+?84|0)\d{9,10}$`: khách của site là
 * người đi định cư, một phần đang ở nước ngoài và để số nước đó. Mẫu VN sẽ từ
 * chối đúng nhóm khách đáng giá nhất. Nên chỉ đếm chữ số sau khi bỏ dấu phân
 * cách — đủ để loại "abc" và "123", không loại oan ai.
 *
 * @param string $phone Số đã lọc.
 * @return bool
 */
function cimf_form_phone_ok( $phone ) {
	$digits = preg_replace( '/\D/', '', $phone );

	return strlen( (string) $digits ) >= 9 && strlen( (string) $digits ) <= 15;
}

// ═══════════════════════════════════════════════════════════════
// 3. GIỚI HẠN THEO IP
// ═══════════════════════════════════════════════════════════════

/**
 * Khoá transient đếm lượt gửi của IP hiện tại.
 *
 * Chỉ dùng REMOTE_ADDR, không đọc X-Forwarded-For: header đó khách tự đặt được
 * nên tin vào nó là tự mở cửa cho người muốn vượt giới hạn.
 *
 * @return string
 */
function cimf_form_try_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'khong-ro';

	return 'cimf_gui_' . md5( $ip );
}

/**
 * Số lần IP này đã gửi trong cửa sổ hiện tại.
 *
 * @return int
 */
function cimf_form_tries() {
	return absint( get_transient( cimf_form_try_key() ) );
}

/**
 * Đếm thêm một lượt gửi.
 *
 * Chỉ đếm lượt đã qua kiểm tra: người điền sai rồi sửa lại không bị tính là
 * năm lần gửi.
 */
function cimf_form_count_try() {
	set_transient( cimf_form_try_key(), cimf_form_tries() + 1, CIMF_FORM_WINDOW );
}

// ═══════════════════════════════════════════════════════════════
// 4. GỬI MAIL
// ═══════════════════════════════════════════════════════════════

/**
 * Gửi nội dung form về mail admin.
 *
 * @param array<string,string> $values Giá trị đã lọc.
 * @param string               $from_url Trang khách gửi từ đó.
 * @return bool
 */
function cimf_form_send_mail( array $values, $from_url ) {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf(
		'[%s] Yêu cầu gọi lại: %s',
		$site,
		$values['tieu_de'] ?? ''
	);

	$lines = array();

	foreach ( CIMF_FORM_FIELDS as $name => $field ) {
		$lines[] = sprintf( '%s: %s', $field['mail_label'], $values[ $name ] ?? '' );
	}

	$lines[] = '';
	$lines[] = '---';
	$lines[] = 'Gửi từ trang: ' . ( cimf_form_safe_url( $from_url ) ?: home_url( '/' ) );
	$lines[] = 'Thời gian: ' . current_time( 'd/m/Y H:i' );
	$lines[] = 'IP: ' . ( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'không rõ' );

	/*
	 * Không đặt header From. wp_mail để mặc định là wordpress@<domain>, đúng
	 * domain đang gửi nên qua được SPF. Đặt From thành mail khách — mail mà
	 * form này còn chẳng hỏi — là cách nhanh nhất để thư rơi vào spam.
	 */
	return (bool) wp_mail(
		cimf_form_recipients(),
		$subject,
		implode( "\n", $lines )
	);
}

/**
 * Danh sách người nhận.
 *
 * Email quản trị luôn có, đọc lúc gửi nên đổi ở Cài đặt → Tổng quan là theo
 * ngay. Địa chỉ thứ hai là ô Email ở trang Thông tin liên hệ, để trống cũng
 * được.
 *
 * Trả mảng chứ không phải chuỗi nối dấu phẩy: wp_mail() nhận cả hai, nhưng
 * mảng thì không phải lo địa chỉ nào có dấu phẩy trong phần tên.
 *
 * @return string[]
 */
function cimf_form_recipients() {
	$admin = (string) get_option( 'admin_email' );
	$to    = array( $admin );
	$them  = cimf_form_extra_recipient();

	/*
	 * Trùng email quản trị thì bỏ. Không bỏ thì cùng một người nhận hai bản
	 * giống hệt nhau cho mỗi yêu cầu, và nghĩ là form gửi trùng.
	 */
	if ( '' !== $them && strtolower( $them ) !== strtolower( $admin ) ) {
		$to[] = $them;
	}

	return $to;
}

/**
 * Địa chỉ nhận thêm: ô Email ở trang Thông tin liên hệ.
 *
 * Dùng thẳng ô đó chứ không có ô riêng cho form. Đổi lại là một địa chỉ làm
 * hai việc — vừa hiện cho khách xem ở chân trang, vừa nhận yêu cầu — và trang
 * cài đặt KHÔNG ghi gì về việc đó. Nên ai sửa địa chỉ hiển thị cho khách xem là
 * đang chuyển cả luồng yêu cầu, mà nhìn ô nhập thì không đoán ra.
 *
 * Kiểm lại dù contact-info-manager đã lọc bằng sanitize_email lúc lưu: giá trị
 * cũ trong database có từ trước khi có lớp lọc đó.
 *
 * @return string Rỗng nếu chưa đặt hoặc đặt sai.
 */
function cimf_form_extra_recipient() {
	// Module contact-info-manager nạp trước cim-footer, nhưng test thì không.
	if ( ! function_exists( 'cim_get' ) ) {
		return '';
	}

	$email = sanitize_email( (string) cim_get( 'email' ) );

	return is_email( $email ) ? $email : '';
}

/**
 * Trang khách gửi form từ đó.
 *
 * Referer trước, cimf_tu sau. Lý do đúng thứ tự đó: cimf_tu in sẵn trong HTML,
 * mà chân trang nằm trong cache toàn trang — giá trị in ra là URL của người
 * tình cờ nạp cache, không phải trang khách đang xem. Referer thì đi theo từng
 * request nên luôn đúng. JS cũng ghi lại cimf_tu bằng location.href lúc gửi,
 * nên đường dự phòng chỉ còn đụng tới khi vừa không có JS vừa không có referer.
 *
 * @return string Rỗng nếu không biết chắc.
 */
function cimf_form_source_url() {
	$referer = wp_get_referer();

	if ( $referer ) {
		return (string) $referer;
	}

	return cimf_form_safe_url( isset( $_POST['cimf_tu'] ) ? wp_unslash( $_POST['cimf_tu'] ) : '' );
}

/**
 * URL chỉ dùng khi nó thuộc chính site này.
 *
 * @param string $url URL khách gửi lên.
 * @return string Rỗng nếu không dùng được.
 */
function cimf_form_safe_url( $url ) {
	$url = esc_url_raw( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );

	return $host && $host === wp_parse_url( home_url(), PHP_URL_HOST ) ? $url : '';
}

// ═══════════════════════════════════════════════════════════════
// 5. TRẢ KẾT QUẢ
// ═══════════════════════════════════════════════════════════════

/**
 * Trả kết quả rồi kết thúc request.
 *
 * @param bool                 $ajax    Gửi bằng JS hay không.
 * @param bool                 $ok      Thành công hay không.
 * @param string               $message Thông báo cho người gửi.
 * @param array<string,string> $fields  Lỗi theo từng trường.
 */
function cimf_form_respond( $ajax, $ok, $message, array $fields ) {
	if ( $ajax ) {
		$payload = array( 'message' => $message );

		if ( $fields ) {
			$payload['fields'] = $fields;
		}

		if ( $ok ) {
			wp_send_json_success( $payload );
		}

		wp_send_json_error( $payload );
	}

	/*
	 * Không có JS: về đúng trang cũ, kèm kết quả ở query string để in lại thông
	 * báo. Neo #cim-footer để trang không nhảy về đầu. Lỗi từng trường thì bỏ —
	 * mang qua redirect phải gói vào URL hoặc transient, mà thông báo chung đã
	 * đủ cho đường dự phòng này.
	 */
	$back = cimf_form_source_url();

	if ( '' === $back ) {
		$back = home_url( '/' );
	}

	wp_safe_redirect(
		add_query_arg( 'cimf', $ok ? 'ok' : 'loi', $back ) . '#cim-footer'
	);
	exit;
}

/**
 * Thông báo in sẵn vào form khi quay lại từ đường không có JS.
 *
 * @return array{text:string,class:string} Rỗng text nghĩa là không có gì để in.
 */
function cimf_form_notice() {
	$flag = isset( $_GET['cimf'] ) ? sanitize_key( wp_unslash( $_GET['cimf'] ) ) : '';

	if ( 'ok' === $flag ) {
		return array(
			'text'  => 'Đã nhận yêu cầu. Chúng tôi sẽ gọi lại cho bạn trong thời gian sớm nhất.',
			'class' => 'cimf-response--ok',
		);
	}

	if ( 'loi' === $flag ) {
		return array(
			'text'  => 'Chưa gửi được. Vui lòng kiểm tra lại các trường rồi gửi lần nữa.',
			'class' => 'cimf-response--loi',
		);
	}

	return array( 'text' => '', 'class' => '' );
}

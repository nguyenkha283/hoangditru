<?php
/**
 * Test cho form liên hệ ở chân trang.
 *
 *   php scripts/form-lien-he.test.php
 *
 * Form này nhận dữ liệu từ người lạ trên internet và gửi mail về hộp thư
 * admin. Sai phần lọc là mail mang theo thứ người gửi muốn; sai phần kiểm là
 * hoặc spam tràn vào, hoặc khách thật bị chặn oan mà không ai biết. Máy không
 * có WordPress chạy được nên test giả lập các hàm WordPress mà module gọi, rồi
 * gọi thẳng từng hàm quyết định.
 *
 * Giới hạn: không chứng minh mail đến được hộp thư — phần đó phụ thuộc cấu hình
 * gửi mail của hosting, phải thử trên site thật.
 */

define( 'ABSPATH', __DIR__ . '/../' );

// ── Hàm WordPress mà module gọi ─────────────────────────────────────────────
function add_action( $hook, $cb = null, $pri = 10, $args = 1 ) {}
function absint( $v ) { return abs( (int) $v ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_url( $v ) { return (string) $v; }
function esc_url_raw( $v ) { return (string) $v; }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '/' ) { return 'https://hoangditru.com' . $path; }
function admin_url( $path = '' ) { return 'https://hoangditru.com/wp-admin/' . $path; }
function current_time( $format ) { return '27/10/2026 10:00'; }
function get_bloginfo( $what ) { return 'Hoàng Di Trú'; }
function wp_specialchars_decode( $v, $flags = null ) { return html_entity_decode( (string) $v, ENT_QUOTES ); }
function get_option( $key, $default = false ) { return 'admin' === substr( $key, 0, 5 ) ? 'chu@hoangditru.com' : $default; }

/**
 * sanitize_text_field giả lập: bỏ thẻ, bỏ ký tự điều khiển, gộp khoảng trắng.
 *
 * Không phải bản thật từng chữ, nhưng giữ đúng ba việc mà test cần tới: thẻ
 * HTML bị gỡ, xuống dòng bị gộp, hai đầu bị trim.
 */
function sanitize_text_field( $str ) {
	$str = strip_tags( (string) $str );
	$str = preg_replace( '/[\r\n\t]+/', ' ', $str );
	$str = preg_replace( '/\s{2,}/', ' ', $str );

	return trim( $str );
}

/** sanitize_textarea_field giả lập: như trên nhưng giữ xuống dòng. */
function sanitize_textarea_field( $str ) {
	return trim( preg_replace( '/[ \t]{2,}/', ' ', strip_tags( (string) $str ) ) );
}

// ── Trạng thái giả lập ──────────────────────────────────────────────────────
$GLOBALS['transients'] = array();
$GLOBALS['mails']      = array();
$GLOBALS['referer']    = '';

/** Dữ liệu của module contact-info-manager, nơi lưu địa chỉ nhận thêm. */
$GLOBALS['cim'] = array();

function cim_get( $key ) { return $GLOBALS['cim'][ $key ] ?? ''; }

/**
 * sanitize_email() giả lập.
 *
 * Bản thật trả CHUỖI RỖNG khi địa chỉ không dùng được, chứ không trả nguyên
 * chuỗi vào. Stub phải giữ đúng chỗ đó: toàn bộ cách xử lý địa chỉ sai của
 * module dựa vào tính chất này.
 */
function sanitize_email( $email ) {
	$email = trim( (string) $email );

	return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
}

function is_email( $email ) {
	return (bool) filter_var( (string) $email, FILTER_VALIDATE_EMAIL );
}

/**
 * wp_get_referer() giả lập.
 *
 * Bản thật trả về referer đã qua wp_validate_redirect(), tức chỉ nhận URL cùng
 * host. Stub giữ đúng tính chất đó, không thì test bỏ lọt trường hợp referer
 * trỏ sang site khác — mà đó chính là trường hợp đáng kiểm.
 */
function wp_get_referer() {
	$ref = (string) $GLOBALS['referer'];

	if ( '' === $ref ) {
		return false;
	}

	return parse_url( $ref, PHP_URL_HOST ) === 'hoangditru.com' ? $ref : false;
}

function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['transients'][ $key ] = $value; return true; }

function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	$GLOBALS['mails'][] = compact( 'to', 'subject', 'message', 'headers' );

	return true;
}

$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
$_SERVER['REQUEST_URI'] = '/golden-visa/dinh-cu-malta/';

require __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/cim-footer/includes/form-lien-he.php';

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

/** Bộ dữ liệu hợp lệ, dùng làm gốc rồi sửa từng trường cho mỗi ca. */
function post_hop_le( array $thay_doi = array() ) {
	return array_merge(
		array(
			'ten'        => 'Nguyễn Văn A',
			'dien_thoai' => '0912345678',
			'tieu_de'    => 'Tư vấn định cư Malta',
			'tin_nhan'   => 'Tôi muốn được gọi lại buổi tối.',
			'website'    => '',
			'cimf_giay'  => '12',
			'cimf_tu'    => 'https://hoangditru.com/golden-visa/dinh-cu-malta/',
		),
		$thay_doi
	);
}

echo "── Bốn trường đúng như form CF7 cũ ─────────────────────────────────\n";

t( 'đúng 4 trường', count( CIMF_FORM_FIELDS ), 4 );
t( 'thứ tự trường', array_keys( CIMF_FORM_FIELDS ), array( 'ten', 'dien_thoai', 'tieu_de', 'tin_nhan' ) );

/*
 * Nhãn phải khớp từng chữ với form cũ. Người dùng nói "các trường thông tin và
 * hình thức không thay đổi gì" nên nhãn là phần hợp đồng, không phải tuỳ ý.
 */
$nhan = array(
	'ten'        => 'Tên của bạn',
	'dien_thoai' => 'Số điện thoại của bạn',
	'tieu_de'    => 'Tiêu đề:',
	'tin_nhan'   => 'Tin nhắn của bạn (không bắt buộc)',
);

foreach ( $nhan as $name => $label ) {
	t( "nhãn $name", CIMF_FORM_FIELDS[ $name ]['label'], $label );
}

t( 'chỉ Tin nhắn là không bắt buộc', empty( CIMF_FORM_FIELDS['tin_nhan']['required'] ), true );
t( 'Tên bắt buộc', ! empty( CIMF_FORM_FIELDS['ten']['required'] ), true );
t( 'maxlength tin nhắn', CIMF_FORM_FIELDS['tin_nhan']['maxlength'], 2000 );

echo "\n── HTML dựng ra ────────────────────────────────────────────────────\n";

ob_start();
cimf_form_render();
$html = ob_get_clean();

t( 'có form', false !== strpos( $html, 'class="cimf-form"' ), true );
t( 'post về admin-post.php', false !== strpos( $html, 'wp-admin/admin-post.php' ), true );
t( 'mang action đúng', false !== strpos( $html, 'value="cimf_lien_he"' ), true );
t( 'có trường bẫy', false !== strpos( $html, 'name="website"' ), true );
t( 'nút Gửi', false !== strpos( $html, 'value="Gửi"' ), true );

/*
 * novalidate để trình duyệt không bật bong bóng mặc định — lỗi hiện dưới từng
 * trường bằng JS, hai thứ cùng chạy thì khách thấy hai thông báo khác nhau.
 */
t( 'form novalidate', false !== strpos( $html, 'novalidate' ), true );

// Không được còn dấu vết CF7 nào trong HTML.
t( 'không còn class wpcf7', false === strpos( $html, 'wpcf7' ), true );

foreach ( array_keys( CIMF_FORM_FIELDS ) as $name ) {
	t( "HTML có trường $name", false !== strpos( $html, 'name="' . $name . '"' ), true );
	t( "HTML có ô lỗi cho $name", false !== strpos( $html, 'data-name="' . $name . '"' ), true );
}

t( 'tin nhắn là textarea', false !== strpos( $html, '<textarea' ), true );

echo "\n── Lọc dữ liệu ─────────────────────────────────────────────────────\n";

$clean = cimf_form_clean( post_hop_le() );
t( 'giữ nguyên tên có dấu', $clean['ten'], 'Nguyễn Văn A' );

$clean = cimf_form_clean( post_hop_le( array( 'ten' => '<script>alert(1)</script>Lan' ) ) );
t( 'gỡ thẻ script', $clean['ten'], 'alert(1)Lan' );

// Trường không gửi lên phải thành chuỗi rỗng, không phải lỗi PHP.
$clean = cimf_form_clean( array() );
t( 'thiếu hết trường', $clean, array( 'ten' => '', 'dien_thoai' => '', 'tieu_de' => '', 'tin_nhan' => '' ) );

/*
 * Gửi mảng thay cho chuỗi là cách cổ điển để làm vỡ hàm lọc. Phải ra rỗng, chứ
 * không được để sanitize_text_field() nhận mảng rồi cảnh báo.
 */
$clean = cimf_form_clean( post_hop_le( array( 'ten' => array( 'a', 'b' ) ) ) );
t( 'gửi mảng thay cho chuỗi', $clean['ten'], '' );

// Cắt theo ký tự, không theo byte: 400 chữ "ê" phải còn đủ 400 chữ.
$dai   = str_repeat( 'ê', 500 );
$clean = cimf_form_clean( post_hop_le( array( 'ten' => $dai ) ) );
t( 'cắt đúng 400 ký tự', cimf_form_truncate( $clean['ten'], 9999 ), str_repeat( 'ê', 400 ) );
t( 'không cắt giữa ký tự', $clean['ten'], str_repeat( 'ê', 400 ) );

t( 'chuỗi ngắn hơn mức thì không đụng', cimf_form_truncate( 'Lan', 400 ), 'Lan' );

echo "\n── Kiểm từng trường ────────────────────────────────────────────────\n";

t( 'bộ hợp lệ không lỗi', cimf_form_errors( cimf_form_clean( post_hop_le() ) ), array() );

$errors = cimf_form_errors( cimf_form_clean( post_hop_le( array( 'ten' => '' ) ) ) );
t( 'thiếu tên', array_keys( $errors ), array( 'ten' ) );

$errors = cimf_form_errors( cimf_form_clean( post_hop_le( array( 'tieu_de' => '   ' ) ) ) );
t( 'tiêu đề chỉ có khoảng trắng', array_keys( $errors ), array( 'tieu_de' ) );

// Tin nhắn trống là hợp lệ: nhãn ghi rõ "không bắt buộc".
t( 'tin nhắn trống vẫn hợp lệ', cimf_form_errors( cimf_form_clean( post_hop_le( array( 'tin_nhan' => '' ) ) ) ), array() );

$errors = cimf_form_errors( cimf_form_clean( post_hop_le( array( 'ten' => '', 'dien_thoai' => '' ) ) ) );
t( 'thiếu hai trường thì báo cả hai', array_keys( $errors ), array( 'ten', 'dien_thoai' ) );

/*
 * Trường trống thì chỉ báo "vui lòng điền", không báo thêm "số chưa đúng" —
 * hai thông báo cho một ô làm khách tưởng điền rồi vẫn sai.
 */
$errors = cimf_form_errors( cimf_form_clean( post_hop_le( array( 'dien_thoai' => '' ) ) ) );
t( 'trống chỉ báo một lỗi', $errors['dien_thoai'], 'Vui lòng điền trường này.' );

echo "\n── Số điện thoại ───────────────────────────────────────────────────\n";

$phones = array(
	'0912345678'        => true,  // di động VN
	'84912345678'       => true,
	'+84 912 345 678'   => true,
	'02812345678'       => true,  // cố định VN 11 số
	'0912.345.678'      => true,
	'(028) 1234 5678'   => true,
	'+1 415 555 2671'   => true,  // khách đang ở nước ngoài
	'+351 912 345 678'  => true,  // Bồ Đào Nha, đúng nhóm khách của site
	'123'               => false,
	''                  => false,
	'gọi cho tôi'       => false,
	'0912345678901234567' => false,
);

foreach ( $phones as $phone => $want ) {
	t( "số '$phone'", cimf_form_phone_ok( (string) $phone ), $want );
}

echo "\n── Chống spam ──────────────────────────────────────────────────────\n";

$GLOBALS['transients'] = array();
t( 'bộ hợp lệ qua được', cimf_form_spam_reason( post_hop_le() ), '' );

$ly_do = cimf_form_spam_reason( post_hop_le( array( 'website' => 'http://spam.example' ) ) );
t( 'trường bẫy có giá trị thì chặn', '' !== $ly_do, true );

$ly_do = cimf_form_spam_reason( post_hop_le( array( 'cimf_giay' => '1' ) ) );
t( 'gửi sau 1 giây thì chặn', '' !== $ly_do, true );

t( 'gửi sau 3 giây thì cho qua', cimf_form_spam_reason( post_hop_le( array( 'cimf_giay' => '3' ) ) ), '' );

/*
 * Không có JS thì không có cimf_giay. Phải cho qua: chặn ở đây là chặn đúng
 * những người dùng trình duyệt cũ hay tắt JS, còn bot thì vẫn còn hai lớp kia.
 */
t( 'thiếu cimf_giay thì cho qua', cimf_form_spam_reason( post_hop_le( array( 'cimf_giay' => '' ) ) ), '' );

$GLOBALS['transients'] = array();
for ( $i = 0; $i < CIMF_FORM_MAX_TRIES; $i++ ) {
	cimf_form_count_try();
}
t( 'đủ ' . CIMF_FORM_MAX_TRIES . ' lần thì chặn', '' !== cimf_form_spam_reason( post_hop_le() ), true );

// IP khác không chịu ảnh hưởng của IP vừa bị chặn.
$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
t( 'IP khác vẫn gửi được', cimf_form_spam_reason( post_hop_le() ), '' );
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

echo "\n── URL quay lại ────────────────────────────────────────────────────\n";

/*
 * cimf_tu do khách gửi lên nên phải coi là dữ liệu bẩn: dùng thẳng là mở
 * redirect sang site khác, và là một dòng URL lạ in vào mail của admin.
 */
t( 'URL cùng site', cimf_form_safe_url( 'https://hoangditru.com/lien-he/' ), 'https://hoangditru.com/lien-he/' );
t( 'URL site khác bị bỏ', cimf_form_safe_url( 'https://evil.example/x' ), '' );
t( 'URL rỗng', cimf_form_safe_url( '' ), '' );
t( 'không phải URL', cimf_form_safe_url( 'javascript:alert(1)' ), '' );

echo "\n── Trang khách gửi từ đó ───────────────────────────────────────────\n";

/*
 * Referer đi trước cimf_tu. cimf_tu in sẵn trong HTML, mà chân trang nằm trong
 * cache toàn trang — giá trị in ra là URL của người tình cờ nạp cache, không
 * phải trang khách đang xem.
 */
$GLOBALS['referer'] = 'https://hoangditru.com/dau-tu-quoc-tich/quoc-tich-grenada/';
$_POST              = array( 'cimf_tu' => 'https://hoangditru.com/trang-cache-cu/' );
t( 'referer thắng cimf_tu', cimf_form_source_url(), 'https://hoangditru.com/dau-tu-quoc-tich/quoc-tich-grenada/' );

$GLOBALS['referer'] = '';
t( 'không referer thì dùng cimf_tu', cimf_form_source_url(), 'https://hoangditru.com/trang-cache-cu/' );

$_POST = array( 'cimf_tu' => 'https://evil.example/x' );
t( 'cimf_tu site khác bị bỏ', cimf_form_source_url(), '' );

$GLOBALS['referer'] = 'https://evil.example/x';
t( 'referer site khác bị bỏ', cimf_form_source_url(), '' );

$GLOBALS['referer'] = '';
$_POST              = array();
t( 'không biết gì thì rỗng', cimf_form_source_url(), '' );

echo "\n── Mail gửi đi ─────────────────────────────────────────────────────\n";

$GLOBALS['mails'] = array();
$values           = cimf_form_clean( post_hop_le() );
$sent             = cimf_form_send_mail( $values, 'https://hoangditru.com/golden-visa/dinh-cu-malta/' );

t( 'gửi thành công', $sent, true );
t( 'gửi đúng 1 mail', count( $GLOBALS['mails'] ), 1 );

$mail = $GLOBALS['mails'][0] ?? array();

// Mail nhận là admin_email, đọc lúc gửi — không chép cứng địa chỉ nào.
t( 'người nhận là admin_email', $mail['to'] ?? null, array( 'chu@hoangditru.com' ) );
t( 'tiêu đề có tên site', false !== strpos( $mail['subject'] ?? '', 'Hoàng Di Trú' ), true );
t( 'tiêu đề có tiêu đề khách nhập', false !== strpos( $mail['subject'] ?? '', 'Tư vấn định cư Malta' ), true );

foreach ( CIMF_FORM_FIELDS as $name => $field ) {
	t( "mail có nhãn {$field['mail_label']}", false !== strpos( $mail['message'] ?? '', $field['mail_label'] . ':' ), true );
}

t( 'mail có số điện thoại', false !== strpos( $mail['message'] ?? '', '0912345678' ), true );
t( 'mail ghi trang gửi từ đó', false !== strpos( $mail['message'] ?? '', '/golden-visa/dinh-cu-malta/' ), true );
t( 'mail ghi thời gian', false !== strpos( $mail['message'] ?? '', '27/10/2026' ), true );
t( 'mail ghi IP', false !== strpos( $mail['message'] ?? '', '203.0.113.7' ), true );

/*
 * Không đặt header From. Đặt From thành domain khác domain đang gửi là thư rơi
 * vào spam vì không qua được SPF.
 */
t( 'không đặt header From', '' === ( $mail['headers'] ?? '' ), true );

// Trang gửi từ site khác thì không in vào mail, thay bằng trang chủ.
$GLOBALS['mails'] = array();
cimf_form_send_mail( $values, 'https://evil.example/x' );
t( 'URL lạ không vào mail', false === strpos( $GLOBALS['mails'][0]['message'], 'evil.example' ), true );

echo "\n── JS gửi form ────────────────────────────────────────────────────\n";

/*
 * Form có <input name="action"> vì admin-post.php bắt buộc tham số tên đúng
 * như vậy. Mà HTMLFormElement khai báo [LegacyOverrideBuiltIns], nghĩa là
 * control trùng tên ĐÈ LÊN thuộc tính dựng sẵn: form.action trả về chính cái
 * input đó chứ không phải URL. fetch() ép nó thành "[object HTMLInputElement]"
 * rồi POST vào URL đó và nhận 404 — đã xảy ra thật trên site, mà khách chỉ
 * thấy "Không gửi được, vui lòng thử lại".
 *
 * Test PHP đọc file JS bằng chuỗi thì thô, nhưng đây là lỗi im lặng, chỉ lộ ra
 * khi bấm thử trên trình duyệt thật. Một dòng grep đáng giá hơn là không có gì.
 */
$js = file_get_contents( __DIR__ . '/../wp-content/plugins/hoangditru-core/modules/cim-footer/assets/form-lien-he.js' );

/*
 * Bỏ comment trước khi tìm. Chính cái bẫy trên được giải thích bằng chữ
 * "form.action" ngay trong file JS, nên tìm thẳng trong file là test đỏ vì một
 * lời giải thích đúng — kiểu test dạy người ta xoá chú thích cho qua bài.
 */
$js_code = preg_replace( '~/\*.*?\*/~s', '', $js );
$js_code = preg_replace( '~^\s*//.*$~m', '', $js_code );

t( 'JS đọc action bằng getAttribute', false !== strpos( $js_code, "getAttribute( 'action' )" ), true );
t( 'JS KHÔNG dùng form.action', false === strpos( $js_code, 'form.action' ), true );
t( 'JS gửi kèm cờ cimf_ajax', false !== strpos( $js_code, 'cimf_ajax' ), true );
t( 'JS ghi lại cimf_tu theo location', false !== strpos( $js_code, 'window.location.href' ), true );

echo "\n── Địa chỉ nhận thêm ───────────────────────────────────────────────\n";

/*
 * Email quản trị luôn có. Địa chỉ thứ hai là ô Email ở trang Thông tin liên hệ
 * — cùng ô đang hiện cho khách xem ở chân trang. Sai một chỗ là hoặc khách gửi
 * mà không ai nhận được bản thứ hai, hoặc có người lạ nhận mọi yêu cầu của site.
 */
$GLOBALS['cim'] = array();
t( 'chưa đặt thì chỉ email quản trị', cimf_form_recipients(), array( 'chu@hoangditru.com' ) );

$GLOBALS['cim']['email'] = 'tuvan@gmail.com';
t( 'đặt rồi thì gửi cả hai', cimf_form_recipients(), array( 'chu@hoangditru.com', 'tuvan@gmail.com' ) );

$GLOBALS['cim']['email'] = 'khong-phai-email';
t( 'địa chỉ sai thì bỏ', cimf_form_recipients(), array( 'chu@hoangditru.com' ) );

$GLOBALS['cim']['email'] = '';
t( 'để trống thì bỏ', cimf_form_recipients(), array( 'chu@hoangditru.com' ) );

/*
 * Điền đúng email quản trị vào ô thứ hai là chuyện sẽ xảy ra. Không lọc thì
 * cùng một người nhận hai bản giống hệt cho mỗi yêu cầu, rồi tưởng form gửi
 * trùng. So sánh không phân biệt hoa thường vì email không phân biệt tên miền.
 */
$GLOBALS['cim']['email'] = 'chu@hoangditru.com';
t( 'trùng email quản trị thì bỏ', cimf_form_recipients(), array( 'chu@hoangditru.com' ) );

$GLOBALS['cim']['email'] = 'CHU@HoangDiTru.com';
t( 'trùng kiểu hoa thường cũng bỏ', cimf_form_recipients(), array( 'chu@hoangditru.com' ) );

// Người nhận phải đi vào đúng lượt wp_mail, không chỉ đúng ở hàm tính.
$GLOBALS['cim']['email'] = 'tuvan@gmail.com';
$GLOBALS['mails']             = array();
cimf_form_send_mail( cimf_form_clean( post_hop_le() ), 'https://hoangditru.com/' );
t( 'wp_mail nhận đủ hai địa chỉ', $GLOBALS['mails'][0]['to'] ?? null, array( 'chu@hoangditru.com', 'tuvan@gmail.com' ) );
t( 'vẫn chỉ gửi 1 lượt', count( $GLOBALS['mails'] ), 1 );

$GLOBALS['cim'] = array();

printf( "\n  %d ok, %d lỗi\n", $pass, $fail );

if ( $fail ) {
	exit( 1 );
}

echo "\nOK — form liên hệ lọc đúng, chặn đúng, mail về đúng hộp thư.\n";

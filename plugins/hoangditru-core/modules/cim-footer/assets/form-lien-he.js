/**
 * Form "Yêu Cầu Gọi Lại" ở chân trang.
 *
 * Form vẫn là form POST thật, chạy được khi không có JS. File này chỉ thêm phần
 * gửi bằng fetch để khỏi tải lại trang — đúng như CF7 cũ làm — và in lỗi ngay
 * dưới từng trường.
 */
( function () {
	'use strict';

	var forms = document.querySelectorAll( '.cimf-form' );

	if ( ! forms.length ) {
		return;
	}

	/*
	 * Mốc để đo người dùng ngồi điền bao lâu. Tính từ lúc script chạy, không
	 * lấy giờ máy chủ in sẵn trong HTML: trang nằm trong cache toàn trang nên
	 * giờ đó là giờ nạp cache, trừ ra một con số vô nghĩa.
	 */
	var moLuc = Date.now();

	Array.prototype.forEach.call( forms, function ( form ) {
		var out = form.querySelector( '.cimf-response' );
		var btn = form.querySelector( '.cimf-submit' );
		var nhan = btn ? btn.value : 'Gửi';

		/*
		 * PHẢI đọc bằng getAttribute, không được dùng form.action.
		 *
		 * Form có <input name="action"> — admin-post.php bắt buộc tham số tên
		 * đúng như vậy. Mà HTMLFormElement khai báo [LegacyOverrideBuiltIns],
		 * nghĩa là control trùng tên ĐÈ LÊN thuộc tính dựng sẵn: form.action
		 * trả về chính cái input đó chứ không phải URL. fetch() ép nó thành
		 * chuỗi "[object HTMLInputElement]" rồi POST vào URL đó, nhận 404, và
		 * khách chỉ thấy "Không gửi được, vui lòng thử lại".
		 */
		var url = form.getAttribute( 'action' ) || window.location.href;

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			xoaLoi( form );
			hienThongBao( out, '', '' );

			var data = new FormData( form );
			data.append( 'cimf_ajax', '1' );
			data.set( 'cimf_giay', String( Math.round( ( Date.now() - moLuc ) / 1000 ) ) );

			/*
			 * Ghi lại URL trang: giá trị in trong HTML là URL của người nạp
			 * cache toàn trang, không phải trang đang xem.
			 */
			data.set( 'cimf_tu', window.location.href );

			if ( btn ) {
				btn.disabled = true;
				btn.value = 'Đang gửi…';
			}

			fetch( url, {
				method: 'POST',
				body: data,
				credentials: 'same-origin'
			} )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( res ) {
					var payload = ( res && res.data ) || {};

					if ( res && res.success ) {
						form.reset();
						hienThongBao( out, payload.message || 'Đã nhận yêu cầu.', 'cimf-response--ok' );

						return;
					}

					hienThongBao( out, payload.message || 'Chưa gửi được.', 'cimf-response--loi' );
					hienLoiTruong( form, payload.fields || {} );
				} )
				.catch( function () {
					hienThongBao( out, 'Không gửi được, vui lòng thử lại.', 'cimf-response--loi' );
				} )
				.then( function () {
					if ( btn ) {
						btn.disabled = false;
						btn.value = nhan;
					}
				} );
		} );
	} );

	/**
	 * In thông báo chung. Để trống text là ẩn (CSS ẩn khi rỗng).
	 *
	 * @param {Element} out   Ô thông báo.
	 * @param {string}  text  Nội dung.
	 * @param {string}  class_ Class trạng thái.
	 */
	function hienThongBao( out, text, class_ ) {
		if ( ! out ) {
			return;
		}

		out.className = 'cimf-response' + ( class_ ? ' ' + class_ : '' );
		out.textContent = text;
	}

	/**
	 * Bỏ các dòng lỗi của lượt gửi trước.
	 *
	 * @param {Element} form Form.
	 */
	function xoaLoi( form ) {
		Array.prototype.forEach.call( form.querySelectorAll( '.cimf-loi' ), function ( el ) {
			el.parentNode.removeChild( el );
		} );
	}

	/**
	 * In lỗi dưới từng trường.
	 *
	 * @param {Element} form   Form.
	 * @param {Object}  fields tên trường => thông báo.
	 */
	function hienLoiTruong( form, fields ) {
		Object.keys( fields ).forEach( function ( name ) {
			var wrap = form.querySelector( '.cimf-field[data-name="' + name + '"]' );

			if ( ! wrap ) {
				return;
			}

			var loi = document.createElement( 'span' );
			loi.className = 'cimf-loi';
			loi.textContent = fields[ name ];
			wrap.appendChild( loi );
		} );
	}
} )();

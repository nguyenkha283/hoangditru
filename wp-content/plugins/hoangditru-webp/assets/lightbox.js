/*!
 * Lightbox tải ảnh lớn khi bấm.
 *
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin. Xem includes/convert.php.
 *
 * Trang chỉ tải bản vừa khung (src/srcset). Bản lớn nằm ở data-full và CHỈ được
 * tải khi người dùng bấm vào ảnh; trong lúc chờ, lightbox hiện ngay bản nhỏ đã
 * có sẵn trong cache để không có khoảng trống.
 *
 * Ảnh mở được lightbox khi:
 *   - có data-full, hoặc nằm trong <a href="…jpg|png|webp…"> (kiểu "Liên kết tới
 *     tệp media" của WordPress); ảnh nằm trong link tới TRANG khác thì không;
 *   - đang hiển thị rộng từ 120px (bỏ icon, avatar, logo);
 *   - không nằm trong vùng loại trừ (cfg.exclude), ví dụ lightbox riêng của BĐS.
 *
 * Không phụ thuộc jQuery. DOM của lightbox chỉ được dựng ở lần bấm đầu tiên.
 */
(function () {
	'use strict';

	var cfg = window.wslLightbox || {};
	var t = cfg.i18n || {};
	var EXCLUDE = cfg.exclude || '.no-lightbox, [data-no-lightbox]';
	var GROUP = '[data-lightbox-group], .wp-block-gallery, .gallery, .entry-content, article, section, main';
	var IMG_HREF = /\.(jpe?g|png|gif|webp|avif)(?:[?#].*)?$/i;
	var MIN_W = cfg.minWidth || 120;

	var lb, lbImg, lbCap, lbCount, items = [], index = 0, lastFocus = null, token = 0;

	if ( ! Element.prototype.closest ) {
		return; // Trình duyệt quá cũ: để ảnh/link hoạt động như bình thường.
	}

	/** URL bản lớn của một ảnh, hoặc null nếu ảnh không thuộc diện mở lightbox. */
	function fullOf( img ) {
		var a = img.closest( 'a' );
		if ( a ) {
			var href = a.getAttribute( 'href' ) || '';
			if ( ! IMG_HREF.test( href ) ) {
				return null;
			}
			return img.getAttribute( 'data-full' ) || href;
		}
		return img.getAttribute( 'data-full' );
	}

	function eligible( img ) {
		if ( ! img || 'IMG' !== img.tagName ) {
			return false;
		}
		// .slick-cloned: bản sao slide do slick tạo, để nó vào bộ sưu tập là mỗi
		// ảnh hiện hai lần.
		if ( img.closest( EXCLUDE ) || img.closest( '.wsl-lb, .slick-cloned' ) ) {
			return false;
		}
		if ( ! fullOf( img ) ) {
			return false;
		}
		return img.getBoundingClientRect().width >= MIN_W;
	}

	/** Ảnh mà cú bấm nhắm tới: bấm vào ảnh, hoặc Enter trên link bọc ảnh. */
	function imgFromEvent( e ) {
		var el = e.target;
		if ( ! el || ! el.closest ) {
			return null;
		}
		if ( 'IMG' === el.tagName ) {
			return el;
		}
		var a = el.closest( 'a' );
		return a ? a.querySelector( 'img' ) : null;
	}

	function captionOf( img ) {
		var fig = img.closest( 'figure' );
		var cap = fig ? fig.querySelector( 'figcaption' ) : null;
		return ( cap && cap.textContent.trim() ) || img.getAttribute( 'alt' ) || '';
	}

	function button( cls, label, svg ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'wsl-lb__btn ' + cls;
		b.setAttribute( 'aria-label', label );
		b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' + svg + '"/></svg>';
		return b;
	}

	function build() {
		lb = document.createElement( 'div' );
		lb.className = 'wsl-lb';
		lb.setAttribute( 'role', 'dialog' );
		lb.setAttribute( 'aria-modal', 'true' );
		lb.setAttribute( 'aria-label', t.dialog || 'Xem ảnh' );

		var close = button( 'wsl-lb__close', t.close || 'Đóng', 'M6 6l12 12M18 6L6 18' );
		var prev = button( 'wsl-lb__prev', t.prev || 'Ảnh trước', 'M15 5l-7 7 7 7' );
		var next = button( 'wsl-lb__next', t.next || 'Ảnh sau', 'M9 5l7 7-7 7' );

		var fig = document.createElement( 'figure' );
		fig.className = 'wsl-lb__stage';
		lbImg = document.createElement( 'img' );
		lbImg.className = 'wsl-lb__img';
		lbImg.alt = '';
		lbImg.decoding = 'async';
		lbCap = document.createElement( 'figcaption' );
		lbCap.className = 'wsl-lb__cap';
		fig.appendChild( lbImg );
		fig.appendChild( lbCap );

		lbCount = document.createElement( 'div' );
		lbCount.className = 'wsl-lb__count';

		var spin = document.createElement( 'div' );
		spin.className = 'wsl-lb__spin';
		spin.setAttribute( 'aria-hidden', 'true' );

		lb.appendChild( fig );
		lb.appendChild( spin );
		lb.appendChild( lbCount );
		lb.appendChild( prev );
		lb.appendChild( next );
		lb.appendChild( close );
		document.body.appendChild( lb );

		close.addEventListener( 'click', hide );
		prev.addEventListener( 'click', function () { show( index - 1 ); } );
		next.addEventListener( 'click', function () { show( index + 1 ); } );
		lb.addEventListener( 'click', function ( e ) {
			if ( e.target === lb || e.target === fig ) {
				hide();
			}
		} );

		var x0 = null;
		lb.addEventListener( 'touchstart', function ( e ) {
			x0 = e.touches[0].clientX;
		}, { passive: true } );
		lb.addEventListener( 'touchend', function ( e ) {
			if ( null === x0 ) {
				return;
			}
			var dx = e.changedTouches[0].clientX - x0;
			x0 = null;
			if ( Math.abs( dx ) > 40 && items.length > 1 ) {
				show( index + ( dx < 0 ? 1 : -1 ) );
			}
		}, { passive: true } );
	}

	function show( i ) {
		index = ( i + items.length ) % items.length;
		var item = items[ index ];
		var mine = ++token;

		// Hiện ngay bản nhỏ đang có trong cache, rồi thay bằng bản lớn khi tải xong.
		lbImg.src = item.thumb || item.full;
		lbImg.alt = item.caption;
		lbCap.textContent = item.caption;
		lbCount.textContent = items.length > 1 ? ( index + 1 ) + ' / ' + items.length : '';

		if ( item.thumb === item.full ) {
			lb.classList.remove( 'is-loading' );
			return;
		}

		lb.classList.add( 'is-loading' );
		var big = new Image();
		big.decoding = 'async';
		big.onload = function () {
			if ( mine === token ) {
				lbImg.src = item.full;
				lb.classList.remove( 'is-loading' );
			}
		};
		big.onerror = function () {
			if ( mine === token ) {
				lb.classList.remove( 'is-loading' );
			}
		};
		big.src = item.full;
	}

	function open( img ) {
		var scope = img.closest( GROUP ) || document;
		var list = Array.prototype.filter.call( scope.querySelectorAll( 'img' ), eligible );
		if ( list.indexOf( img ) < 0 ) {
			list = [ img ];
		}

		items = list.map( function ( el ) {
			return {
				full: fullOf( el ),
				thumb: el.currentSrc || el.src,
				caption: captionOf( el )
			};
		} );

		if ( ! lb ) {
			build();
		}

		lastFocus = document.activeElement;
		lb.classList.toggle( 'is-single', items.length < 2 );
		lb.classList.add( 'is-open' );
		document.documentElement.classList.add( 'wsl-lb-lock' );
		show( list.indexOf( img ) );
		lb.querySelector( '.wsl-lb__close' ).focus();
	}

	function hide() {
		token++;
		lb.classList.remove( 'is-open', 'is-loading' );
		document.documentElement.classList.remove( 'wsl-lb-lock' );
		lbImg.removeAttribute( 'src' );
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	document.addEventListener( 'click', function ( e ) {
		if ( e.defaultPrevented || e.button > 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) {
			return; // Ctrl/⌘-click vẫn mở tab mới như thường.
		}
		var img = imgFromEvent( e );
		if ( eligible( img ) ) {
			e.preventDefault();
			open( img );
		}
	} );

	// Con trỏ kính lúp: tính lúc rê chuột thay vì quét cả trang lúc tải, nên ảnh
	// nạp sau bằng AJAX cũng được.
	document.addEventListener( 'mouseover', function ( e ) {
		var img = e.target;
		if ( img && 'IMG' === img.tagName && ! img.hasAttribute( 'data-wsl' ) ) {
			img.setAttribute( 'data-wsl', '' );
			if ( eligible( img ) ) {
				img.classList.add( 'wsl-zoom' );
			}
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! lb || ! lb.classList.contains( 'is-open' ) ) {
			return;
		}
		if ( 'Escape' === e.key ) {
			hide();
		} else if ( 'ArrowLeft' === e.key && items.length > 1 ) {
			show( index - 1 );
		} else if ( 'ArrowRight' === e.key && items.length > 1 ) {
			show( index + 1 );
		} else if ( 'Tab' === e.key ) {
			// Giữ focus trong hộp thoại.
			var btns = Array.prototype.filter.call( lb.querySelectorAll( 'button' ), function ( b ) {
				return b.offsetParent !== null;
			} );
			var at = btns.indexOf( document.activeElement );
			e.preventDefault();
			btns[ ( at + ( e.shiftKey ? -1 : 1 ) + btns.length ) % btns.length ].focus();
		}
	} );
}());

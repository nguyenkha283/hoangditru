/*!
 * Media → Chuyển WebP: gọi từng mẻ cho tới khi hết ảnh.
 * FILE DÙNG CHUNG — giống hệt từng byte giữa hai bản plugin.
 */
(function () {
	'use strict';

	var cfg = window.wslAdmin;
	var $ = function ( id ) { return document.getElementById( id ); };
	var start = $( 'wsl-start' ), stop = $( 'wsl-stop' ), log = $( 'wsl-log' ), bar = $( 'wsl-bar' );
	if ( ! cfg || ! start ) {
		return;
	}

	var running = false, sum;

	function fmt( s ) {
		var a = Array.prototype.slice.call( arguments, 1 );
		return s.replace( /%(\d)\$[ds]|%[ds]/g, function ( m, n ) {
			return String( n ? a[ n - 1 ] : a.shift() );
		} );
	}

	function size( b ) {
		var u = [ 'B', 'KB', 'MB', 'GB' ], i = 0;
		while ( b >= 1024 && i < u.length - 1 ) { b /= 1024; i++; }
		return ( i ? b.toFixed( 1 ) : b ) + ' ' + u[ i ];
	}

	function ui( on ) {
		running = on;
		start.disabled = on;
		stop.disabled = ! on;
	}

	function batch( after ) {
		var body = new FormData();
		body.append( 'action', 'wsl_bulk' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'after', after );
		if ( $( 'wsl-force' ).checked ) {
			body.append( 'force', '1' );
		}

		fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'HTTP ' + r.status ); }
				return r.json();
			} )
			.then( function ( res ) {
				if ( ! res || ! res.success ) { throw new Error( ( res && res.data ) || 'phản hồi không hợp lệ' ); }
				var d = res.data;
				sum.images += d.images; sum.converted += d.converted; sum.failed += d.failed; sum.saved += d.saved;
				$( 'wsl-done' ).textContent = d.counts.done;
				$( 'wsl-total' ).textContent = d.counts.total;
				bar.max = Math.max( 1, d.counts.total );
				bar.value = Math.min( bar.max, sum.images );

				var failed = sum.failed ? ' ' + fmt( cfg.i18n.failed, sum.failed ) : '';
				if ( d.finished ) {
					bar.value = bar.max;
					log.textContent = fmt( cfg.i18n.done, sum.images, sum.converted, size( sum.saved ) ) + failed;
					ui( false );
				} else if ( ! running ) {
					log.textContent = cfg.i18n.stopped;
				} else {
					log.textContent = fmt( cfg.i18n.running, sum.images, sum.converted, size( sum.saved ) ) + failed;
					batch( d.last );
				}
			} )
			.catch( function ( e ) {
				log.textContent = fmt( cfg.i18n.error, e.message );
				ui( false );
			} );
	}

	start.addEventListener( 'click', function () {
		sum = { images: 0, converted: 0, failed: 0, saved: 0 };
		bar.value = 0;
		ui( true );
		batch( 0 );
	} );

	stop.addEventListener( 'click', function () {
		ui( false );
	} );
}());

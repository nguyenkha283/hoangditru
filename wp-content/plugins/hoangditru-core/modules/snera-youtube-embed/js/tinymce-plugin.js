( function () {
	'use strict';

	tinymce.PluginManager.add( 'snera_yt_button', function ( editor ) {
		editor.addButton( 'snera_yt_button', {
			title: 'Chèn video YouTube',
			text: 'YouTube',
			icon: false,
			onclick: function () {
				editor.windowManager.open( {
					title: 'Chèn video YouTube',
					width: 460,
					height: 120,
					body: [
						{
							type: 'textbox',
							name: 'youtube_url',
							label: 'Link YouTube',
							placeholder: 'https://www.youtube.com/watch?v=...'
						}
					],
					buttons: [
						{
							text: 'Chèn',
							subtype: 'primary',
							onclick: 'submit'
						},
						{
							text: 'Huỷ',
							onclick: 'close'
						}
					],
					onsubmit: function ( e ) {
						var link = ( e.data.youtube_url || '' ).replace( /"/g, '' ).trim();
						if ( '' === link ) {
							return;
						}
						editor.insertContent( '[youtube_embed url="' + link + '"]' );
					}
				} );
			}
		} );
	} );
} )();

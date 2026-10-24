/**
 * Contact Info Manager — TinyMCE Plugin
 * Thêm nút "Chèn Chữ Ký" vào toolbar của WordPress editor.
 */
(function () {
    'use strict';

    tinymce.PluginManager.add('cim_signature', function (editor) {

        var config = (typeof CIM_Signature !== 'undefined') ? CIM_Signature : {
            shortcode : '[cim_signature]',
            preview   : '',
            label     : 'Chèn Chữ Ký',
        };

        // ── Đăng ký nút trên toolbar ──
        editor.addButton('cim_signature_btn', {
            title   : config.label,
            tooltip : 'Chèn shortcode chữ ký [cim_signature] vào nội dung',
            icon    : false,
            text    : '✍ Chữ ký',
            onclick : function () {
                // Nếu chưa có dữ liệu chữ ký, chèn thẳng shortcode
                if ( ! config.preview ) {
                    editor.insertContent(config.shortcode);
                    return;
                }

                // Mở popup xác nhận với preview nội dung chữ ký
                editor.windowManager.open({
                    title  : 'Chèn Chữ Ký',
                    width  : 420,
                    height : 200,
                    body   : [
                        {
                            type : 'container',
                            html : '<div style="'
                                 + 'padding:12px 4px 8px;'
                                 + 'font-size:13px;'
                                 + 'color:#444;'
                                 + 'line-height:1.6;'
                                 + '">'
                                 + '<p style="margin:0 0 10px;font-weight:600;color:#1f2937;">Nội dung chữ ký sẽ được chèn:</p>'
                                 + '<div style="'
                                 + 'background:#f9f9f9;'
                                 + 'border-left:4px solid #6366f1;'
                                 + 'border-radius:4px;'
                                 + 'padding:10px 14px;'
                                 + 'font-size:12px;'
                                 + 'color:#374151;'
                                 + 'max-height:80px;'
                                 + 'overflow:hidden;'
                                 + 'white-space:pre-wrap;'
                                 + 'word-break:break-word;'
                                 + '">'
                                 + tinymce.DOM.encode(config.preview).substring(0, 200)
                                 + (config.preview.length > 200 ? '…' : '')
                                 + '</div>'
                                 + '<p style="margin:10px 0 0;font-size:11px;color:#9ca3af;">'
                                 + 'Shortcode: <code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;">'
                                 + '[cim_signature]'
                                 + '</code></p>'
                                 + '</div>',
                        }
                    ],
                    buttons: [
                        {
                            text    : 'Huỷ',
                            onclick : 'close',
                        },
                        {
                            text    : 'Chèn vào bài viết',
                            subtype : 'primary',
                            onclick : function () {
                                editor.insertContent(config.shortcode);
                                editor.windowManager.close();
                            },
                        }
                    ],
                });
            },
        });
    });
}());

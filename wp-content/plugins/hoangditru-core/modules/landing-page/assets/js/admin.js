/**
 * Landing Page Builder — Admin JS
 * Repeater add/remove/sort + WP Media uploader
 */
(function ($) {
    'use strict';

    /* ── Templates cho từng loại row ─────────────────────── */
    var templates = {

        advantage: function (i) {
            return '<div class="lpb-row">' +
                '<div class="lpb-row-handle">☰</div>' +
                '<div class="lpb-row-fields">' +
                    '<div class="lpb-image-picker lpb-inline">' +
                        '<input type="hidden" name="lpb_advantages[' + i + '][icon]" value="" class="lpb-img-input">' +
                        '<div class="lpb-img-preview lpb-sm"></div>' +
                        '<button type="button" class="button button-secondary lpb-upload-btn"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>' +
                    '</div>' +
                    '<input type="text" name="lpb_advantages[' + i + '][text]" value="" placeholder="Nội dung ưu điểm" class="widefat">' +
                '</div>' +
                '<button type="button" class="button lpb-remove-row">✕</button>' +
            '</div>';
        },

        benefit: function (i) {
            return '<div class="lpb-row lpb-row-block">' +
                '<div class="lpb-row-top">' +
                    '<div class="lpb-row-handle">☰</div>' +
                    '<span style="flex:1;font-size:13px;color:#555">Quyền lợi ' + (i + 1) + '</span>' +
                    '<button type="button" class="button lpb-remove-row">✕</button>' +
                '</div>' +
                '<div class="lpb-row-body">' +
                    '<div class="lpb-image-picker" style="margin-bottom:8px">' +
                        '<input type="hidden" name="lpb_benefits[' + i + '][image]" value="" class="lpb-img-input">' +
                        '<div class="lpb-img-preview"></div>' +
                        '<button type="button" class="button button-secondary lpb-upload-btn"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>' +
                    '</div>' +
                    '<textarea name="lpb_benefits[' + i + '][desc]" rows="2" placeholder="Mô tả quyền lợi" class="widefat"></textarea>' +
                '</div>' +
            '</div>';
        },

        condition: function (i) {
            return '<div class="lpb-row lpb-row-block">' +
                '<div class="lpb-row-top">' +
                    '<div class="lpb-row-handle">☰</div>' +
                    '<input type="text" name="lpb_conditions[' + i + '][title]" value="" placeholder="Tiêu đề điều kiện" class="widefat">' +
                    '<button type="button" class="button lpb-remove-row">✕</button>' +
                '</div>' +
                '<div class="lpb-row-body">' +
                    '<textarea name="lpb_conditions[' + i + '][content]" rows="4" placeholder="Nội dung (hiển thị khi mở accordion)" class="widefat"></textarea>' +
                '</div>' +
            '</div>';
        },

        process: function (i) {
            return '<div class="lpb-row lpb-row-block">' +
                '<div class="lpb-row-top">' +
                    '<div class="lpb-row-handle">☰</div>' +
                    '<span class="lpb-step-num">Bước ' + (i + 1) + '</span>' +
                    '<input type="text" name="lpb_process[' + i + '][title]" value="" placeholder="Tiêu đề bước" class="widefat">' +
                    '<button type="button" class="button lpb-remove-row">✕</button>' +
                '</div>' +
                '<div class="lpb-row-body">' +
                    '<textarea name="lpb_process[' + i + '][desc]" rows="2" placeholder="Mô tả bước" class="widefat"></textarea>' +
                '</div>' +
            '</div>';
        },

        faq: function (i) {
            return '<div class="lpb-row lpb-row-block">' +
                '<div class="lpb-row-top">' +
                    '<div class="lpb-row-handle">☰</div>' +
                    '<input type="text" name="lpb_faqs[' + i + '][question]" value="" placeholder="Câu hỏi" class="widefat">' +
                    '<button type="button" class="button lpb-remove-row">✕</button>' +
                '</div>' +
                '<div class="lpb-row-body">' +
                    '<textarea name="lpb_faqs[' + i + '][answer]" rows="3" placeholder="Trả lời" class="widefat"></textarea>' +
                '</div>' +
            '</div>';
        }
    };

    /* ── Thêm row ─────────────────────────────────────────── */
    $(document).on('click', '.lpb-add-row', function () {
        var $btn      = $(this);
        var $rows     = $btn.siblings('.lpb-rows');
        var tmpl      = $btn.data('template');
        var count     = $rows.children('.lpb-row').length;
        var html      = templates[tmpl](count);
        $rows.append(html);
        reindex($rows);
    });

    /* ── Xóa row ──────────────────────────────────────────── */
    $(document).on('click', '.lpb-remove-row', function () {
        var $rows = $(this).closest('.lpb-rows');
        $(this).closest('.lpb-row').remove();
        reindex($rows);
    });

    /* ── Reindex tên fields ───────────────────────────────── */
    function reindex($rows) {
        $rows.children('.lpb-row').each(function (i) {
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                // Thay index [N] trong tên
                var newName = name.replace(/\[\d+\]/, '[' + i + ']');
                $(this).attr('name', newName);
            });
            // Cập nhật label bước
            var $stepNum = $(this).find('.lpb-step-num');
            if ($stepNum.length) $stepNum.text('Bước ' + (i + 1));
        });
    }

    /* ── Kéo thả sắp xếp ─────────────────────────────────── */
    $('.lpb-rows').sortable({
        handle:      '.lpb-row-handle',
        placeholder: 'lpb-row ui-sortable-placeholder',
        axis:        'y',
        update: function () {
            reindex($(this));
        }
    });

    /* ── WP Media Uploader ────────────────────────────────── */
    var mediaFrame;

    // Upload từ button đơn (hero banner)
    $(document).on('click', '.lpb-upload-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);

        // Tìm input ẩn và preview tương ứng
        var $picker  = $btn.closest('.lpb-image-picker');
        var $input   = $picker.find('.lpb-img-input').length
                        ? $picker.find('.lpb-img-input')
                        : $('#' + $btn.data('target'));
        var $preview = $picker.find('.lpb-img-preview').length
                        ? $picker.find('.lpb-img-preview')
                        : $('#' + $btn.data('preview'));

        if (mediaFrame) {
            mediaFrame.off('select');
        }

        mediaFrame = wp.media({
            title:    'Chọn hình ảnh',
            button:   { text: 'Sử dụng hình này' },
            multiple: false,
            library:  { type: 'image' },
        });

        mediaFrame.on('select', function () {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            var url = attachment.url;
            $input.val(url);
            $preview.html('<img src="' + url + '">');
            $picker.find('.lpb-remove-btn').show();
        });

        mediaFrame.open();
    });

    // Xóa ảnh (chỉ hero banner). Tìm input/preview trong cùng .lpb-image-picker
    // như nút chọn ảnh: meta box Hero cũng mang id "lpb_hero_icon", nên
    // $('#lpb_hero_icon') trúng khung meta box chứ không trúng input ẩn — trước
    // đây bấm Xóa rồi Lưu thì icon vẫn còn.
    $(document).on('click', '.lpb-remove-btn', function (e) {
        e.preventDefault();
        var $picker = $(this).closest('.lpb-image-picker');
        $picker.find('.lpb-img-input').val('');
        $picker.find('.lpb-img-preview').html('');
        $(this).hide();
    });

})(jQuery);

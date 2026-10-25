/* Trang Chủ Settings – Admin JS */
(function($){
'use strict';

// ── Generic image uploader ──────────────────────────────────────────────────
var uploaders = {};

$(document).on('click', '.tcs-upload-btn', function(e){
    e.preventDefault();
    var $btn         = $(this);
    var targetId     = $btn.data('target');
    var previewId    = $btn.data('preview');
    var placeholderId = $btn.data('placeholder');

    if (uploaders[targetId]) { uploaders[targetId].open(); return; }

    uploaders[targetId] = wp.media({
        title: 'Chọn ảnh',
        button: { text: 'Dùng ảnh này' },
        multiple: false,
        library: { type: 'image' }
    });

    uploaders[targetId].on('select', function(){
        var att = uploaders[targetId].state().get('selection').first().toJSON();
        var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
        $('#' + targetId).val(att.id);
        $('#' + previewId).attr('src', url).show();
        if (placeholderId) $('#' + placeholderId).hide();
        $btn.siblings('.tcs-remove-btn').show();
    });

    uploaders[targetId].open();
});

$(document).on('click', '.tcs-remove-btn', function(e){
    e.preventDefault();
    var $btn          = $(this);
    var targetId      = $btn.data('target');
    var previewId     = $btn.data('preview');
    var placeholderId = $btn.data('placeholder');
    $('#' + targetId).val('');
    $('#' + previewId).attr('src','').hide();
    if (placeholderId) $('#' + placeholderId).show();
    $btn.hide();
    uploaders[targetId] = null;
});

// ── Sortable post picker ────────────────────────────────────────────────────
$('.tcs-picker').each(function(){
    var $picker   = $(this);
    var max       = parseInt($picker.data('max')) || 6;
    var $allList  = $picker.find('.tcs-all-list');
    var $selList  = $picker.find('.tcs-selected-list');
    var $emptyMsg = $picker.find('.tcs-empty-msg');
    var $countEl  = $picker.find('.tcs-selected-count');

    function updateCount(){
        var n = $selList.find('li').length;
        $countEl.text(n + '/' + max);
        $emptyMsg.toggle(n === 0);
        $allList.find('.tcs-add-btn').each(function(){
            var id = $(this).closest('li').data('id');
            var isSelected = $selList.find('li[data-id="' + id + '"]').length > 0;
            $(this).prop('disabled', isSelected || n >= max);
            $(this).closest('li').toggleClass('tcs-used', isSelected);
        });
    }

    // Make selected list sortable
    $selList.sortable({
        handle: '.tcs-drag-handle',
        placeholder: 'ui-sortable-placeholder',
        tolerance: 'pointer',
        update: function(){ updateCount(); }
    });

    // Search filter
    $picker.find('.tcs-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $allList.find('li').each(function(){
            var text = $(this).find('.tcs-item-title, img + text, span, a').text().toLowerCase()
                       || $(this).clone().children().remove().end().text().toLowerCase();
            // simpler: just text content of the li
            $(this).toggle( $(this).text().toLowerCase().indexOf(q) > -1 );
        });
    });

    // Add button
    $allList.on('click', '.tcs-add-btn', function(){
        var $li   = $(this).closest('li');
        var id    = $li.data('id');
        var title = $li.clone().children('button').remove().end().text().trim();
        var thumbSrc = $li.find('img').attr('src') || '';

        if ( $selList.find('li[data-id="'+id+'"]').length ) return;
        if ( $selList.find('li').length >= max ) return;

        var name = $picker.data('name');
        var imgHtml = thumbSrc ? '<img src="'+thumbSrc+'" alt="">' : '';
        var safeTitle = $('<div>').text(title).html();
        var html = '<li data-id="'+id+'">'
                 + '<span class="tcs-drag-handle dashicons dashicons-menu"></span>'
                 + imgHtml
                 + '<span class="tcs-item-title" title="'+safeTitle.replace(/"/g,'&quot;')+'">'+safeTitle+'</span>'
                 + '<input type="hidden" name="'+name+'[]" value="'+id+'">'
                 + '<button type="button" class="tcs-remove-item button-link"><span class="dashicons dashicons-no-alt"></span></button>'
                 + '</li>';
        $selList.append(html);
        $selList.sortable('refresh');
        updateCount();
    });

    // Remove item
    $selList.on('click', '.tcs-remove-item', function(){
        var $li = $(this).closest('li');
        var id  = $li.data('id');
        $li.remove();
        updateCount();
    });

    updateCount();
});

}(jQuery));

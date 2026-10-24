(function($){
'use strict';

/* =============================================
   Media uploader helper
============================================= */
function openMediaUploader(callback, multiple) {
    var frame = wp.media({
        title: 'Chọn ảnh',
        button: { text: 'Sử dụng ảnh' },
        multiple: multiple || false,
    });
    frame.on('select', function(){
        var selection = frame.state().get('selection');
        var ids = [], urls = [];
        selection.each(function(attachment){
            ids.push(attachment.id);
            urls.push(
                attachment.attributes.sizes && attachment.attributes.sizes.thumbnail
                    ? attachment.attributes.sizes.thumbnail.url
                    : attachment.attributes.url
            );
        });
        callback(ids, urls);
    });
    frame.open();
}

/* =============================================
   Carousel - add slides
============================================= */
$(document).on('click', '.ci-add-images', function(){
    var $list = $('#ci-carousel-list');
    openMediaUploader(function(ids, urls){
        ids.forEach(function(id, i){
            $list.append(
                '<li data-id="'+id+'">'+
                    '<img src="'+urls[i]+'" style="width:80px;height:60px;object-fit:cover">'+
                    '<span class="ci-remove dashicons dashicons-trash" style="cursor:pointer;color:red;margin-left:4px"></span>'+
                    '<input type="hidden" name="ci_carousel_slides[]" value="'+id+'">'+
                '</li>'
            );
        });
    }, true);
});

$(document).on('click', '#ci-carousel-list .ci-remove', function(){
    $(this).closest('li').remove();
});

$('#ci-carousel-list').sortable({ cursor:'grab' });

/* =============================================
   Single image picker (Why Us bg, Overlay flag)
============================================= */
$(document).on('click', '.ci-pick-img', function(){
    var field = $(this).data('field');
    var $btn  = $(this);
    openMediaUploader(function(ids, urls){
        $('#'+field).val(ids[0]);
        var $wrap = $btn.closest('.ci-single-img');
        $wrap.find('img').remove();
        $wrap.prepend('<img src="'+urls[0]+'" style="max-height:60px;display:block;margin-bottom:8px;border:1px solid #ddd;padding:3px;border-radius:3px">');
        $wrap.find('.ci-clear-img').show();
    });
});

$(document).on('click', '.ci-clear-img', function(){
    var field = $(this).data('field');
    $('#'+field).val('');
    $(this).closest('.ci-single-img').find('img').remove();
    $(this).hide();
});

/* =============================================
   Features - pick image per row
============================================= */
$(document).on('click', '.ci-feat-pick-img', function(e){
    e.preventDefault();
    e.stopPropagation();
    var $btn  = $(this);
    var $cell = $btn.closest('.ci-feat-icon-cell');
    openMediaUploader(function(ids, urls){
        $cell.find('input[type=hidden]').val(ids[0]);
        $cell.find('.ci-feat-thumb').remove();
        $cell.prepend('<img src="'+urls[0]+'" class="ci-feat-thumb">');
    });
});

/* =============================================
   Features - add new row
============================================= */
$(document).on('click', '#ci-add-feat', function(){
    $('#ci-features-wrap').append(
        '<div class="ci-feat-row">'+
            '<span class="ci-drag dashicons dashicons-menu"></span>'+
            '<div class="ci-feat-icon-cell">'+
                '<span class="ci-feat-thumb ci-feat-thumb--empty">?</span>'+
                '<input type="hidden" name="ci_feat_icon[]" value="0">'+
                '<button type="button" class="button button-small ci-feat-pick-img">Chọn ảnh</button>'+
            '</div>'+
            '<input type="text" name="ci_feat_text[]" placeholder="Nội dung đặc điểm..." style="flex:1">'+
            '<span class="ci-remove-feat dashicons dashicons-trash" style="cursor:pointer;color:#c00;margin-left:8px"></span>'+
        '</div>'
    );
});

$(document).on('click', '.ci-remove-feat', function(){
    $(this).closest('.ci-feat-row').remove();
});

$(function(){
    $('#ci-features-wrap').sortable({ handle: '.ci-drag', cursor: 'grab', cancel: 'button,input,a,select,textarea' });
});

})(jQuery);

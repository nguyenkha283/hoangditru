/* =========================================================
   BẤT ĐỘNG SẢN – main.js  (jQuery + Slick)
   ========================================================= */
jQuery(function ($) {

    /* ---- Slick Slider Syncing ---- */
    var $mainSlider = $('.bds-slider-main');
    var $navSlider  = $('.bds-slider-nav');

    if ($mainSlider.length) {
        $mainSlider.slick({
            slidesToShow   : 1,
            slidesToScroll : 1,
            arrows         : true,
            dots           : false,
            asNavFor       : '.bds-slider-nav',
            fade           : true,
            speed          : 400,
            lazyLoad       : 'ondemand',
        });

        $navSlider.slick({
            slidesToShow   : 5,
            slidesToScroll : 1,
            asNavFor       : '.bds-slider-main',
            dots           : false,
            arrows         : false,
            focusOnSelect  : true,
            responsive     : [
                { breakpoint: 768, settings: { slidesToShow: 4 } },
                { breakpoint: 480, settings: { slidesToShow: 3 } },
            ]
        });
    }

    /* ---- Accordion (Mặt bằng) ---- */
    $(document).on('click', '.bds-acc-header', function () {
        var $item = $(this).closest('.bds-acc-item');
        var isActive = $item.hasClass('active');

        // Đóng tất cả
        $('.bds-acc-item').removeClass('active').find('.bds-acc-body').slideUp(280);

        // Mở mục được click (nếu chưa active)
        if (!isActive) {
            $item.addClass('active').find('.bds-acc-body').slideDown(280);
        }
    });

    // Mở mục đầu tiên mặc định
    var $firstAcc = $('.bds-acc-item.active');
    if ($firstAcc.length) {
        $firstAcc.find('.bds-acc-body').show();
    }

});


/* =========================================================
   LIGHTBOX – Mặt bằng
   ========================================================= */
jQuery(function ($) {

    /* ---- Tạo markup lightbox 1 lần ---- */
    // Lightbox HTML đã được render sẵn trong template PHP trước wp_footer()
    var $lb = $('#bds-lightbox');
    if ( ! $lb.length ) return;

    var images  = [];   // [{src, caption}, ...]
    var current = 0;

    function open( triggers, clickedIndex ) {
        images  = [];
        current = clickedIndex;

        triggers.each(function () {
            images.push({
                src:     $(this).data('full'),
                caption: $(this).data('caption') || '',
            });
        });

        $lb.toggleClass('single', images.length === 1);
        $lb.addClass('is-open');
        $('body').css('overflow', 'hidden');
        show( current );
    }

    function close() {
        $lb.removeClass('is-open');
        $('body').css('overflow', '');
        $lb.find('.bds-lb-img').attr('src', '');
    }

    function show( idx ) {
        current = ( idx + images.length ) % images.length;
        var item   = images[current];
        var $img   = $lb.find('.bds-lb-img');

        $img.addClass('is-loading');
        $img.attr('alt', item.caption);

        var tmp    = new Image();
        tmp.onload = function () {
            $img.attr('src', item.src).removeClass('is-loading');
        };
        tmp.src = item.src;

        $lb.find('.bds-lb-caption').text( item.caption );
        $lb.find('.bds-lb-counter').text( (current + 1) + ' / ' + images.length );
    }

    /* ---- Events ---- */

    // Click thumbnail trigger — thu thập tất cả ảnh trong cùng accordion panel
    $(document).on('click', '.bds-lb-trigger', function (e) {
        e.preventDefault();
        var $panel    = $(this).closest('.bds-acc-body');
        var $triggers = $panel.find('.bds-lb-trigger');
        var idx       = $triggers.index(this);
        open( $triggers, idx );
    });

    // Đóng
    $lb.on('click', '.bds-lb-close', close);
    $lb.on('click', function (e) {
        if ( $(e.target).is($lb) ) close();
    });

    // Prev / Next
    $lb.on('click', '.bds-lb-prev', function () { show( current - 1 ); });
    $lb.on('click', '.bds-lb-next', function () { show( current + 1 ); });

    // Keyboard
    $(document).on('keydown', function (e) {
        if ( ! $lb.hasClass('is-open') ) return;
        if ( e.key === 'Escape'    ) close();
        if ( e.key === 'ArrowLeft' ) show( current - 1 );
        if ( e.key === 'ArrowRight') show( current + 1 );
    });

    // Swipe trên mobile
    var touchStartX = 0;
    $lb[0].addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });
    $lb[0].addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - touchStartX;
        if ( Math.abs(dx) < 40 ) return;
        if ( dx < 0 ) show( current + 1 );
        else          show( current - 1 );
    }, { passive: true });
});

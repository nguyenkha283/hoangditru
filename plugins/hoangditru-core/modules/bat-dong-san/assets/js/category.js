/* =========================================================
   BẤT ĐỘNG SẢN – category.js  v1.3.1
   Lazy load ảnh cho cả taxonomy archive và CPT archive
   ========================================================= */
jQuery(function ($) {

    /* ---- Lazy load bằng IntersectionObserver ---- */
    // sizes và srcset phải gắn TRƯỚC src, nếu không trình duyệt tải src rồi
    // lại tải thêm bản trong srcset.
    function setSrc( img ) {
        if ( img.dataset.srcset ) {
            img.sizes  = img.dataset.sizes || '';
            img.srcset = img.dataset.srcset;
            img.removeAttribute('data-srcset');
            img.removeAttribute('data-sizes');
        }
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
    }

    function lazyLoadImages( container ) {
        var images = container.querySelectorAll('img[data-src]');
        if ( ! images.length ) return;

        if ( 'IntersectionObserver' in window ) {
            var observer = new IntersectionObserver(function ( entries ) {
                entries.forEach(function ( entry ) {
                    if ( ! entry.isIntersecting ) return;
                    var img = entry.target;
                    setSrc( img );
                    img.addEventListener('load', function () { img.classList.add('loaded'); }, { once: true });
                    observer.unobserve(img);
                });
            }, { rootMargin: '200px 0px' });
            images.forEach(function(img) { observer.observe(img); });
        } else {
            // Fallback
            images.forEach(function(img) {
                setSrc( img );
                img.classList.add('loaded');
            });
        }
    }

    // Init lazy load cho taxonomy archive (#bds-cat-grid)
    var catGrid = document.getElementById('bds-cat-grid');
    if ( catGrid ) lazyLoadImages( catGrid );

    // Init lazy load cho CPT archive (.bds-arc-wrap — nhiều grid)
    var arcWrap = document.querySelector('.bds-arc-wrap');
    if ( arcWrap ) lazyLoadImages( arcWrap );

    /* ---- Skeleton ---- */
    function renderSkeletons( count ) {
        var html = '';
        for ( var i = 0; i < count; i++ ) {
            html += '<div class="bds-skeleton">' +
                        '<div class="bds-skeleton__thumb"></div>' +
                        '<div class="bds-skeleton__body">' +
                            '<div class="bds-skeleton__line"></div>' +
                            '<div class="bds-skeleton__line bds-skeleton__line--short"></div>' +
                        '</div></div>';
        }
        return html;
    }

    /* ---- Load More (taxonomy archive) ---- */
    var $btn = $('#bds-loadmore-btn');
    if ( ! $btn.length ) return;

    $btn.on('click', function () {
        var $self   = $(this);
        var page    = parseInt( $self.data('page') );
        var maxPage = parseInt( $self.data('max') );
        var termId  = $self.data('term');
        var $grid   = $('#bds-cat-grid');

        if ( page >= maxPage ) { $self.hide(); return; }

        $self.prop('disabled', true).text('Đang tải...');
        var $skeletons = $( renderSkeletons(4) );
        $grid.append( $skeletons );

        $.ajax({
            url  : BDS_CAT.ajax_url,
            type : 'POST',
            data : { action: 'bds_load_more', nonce: BDS_CAT.nonce, page: page, term_id: termId },
            success: function ( res ) {
                $skeletons.remove();
                if ( res.success && res.data.html ) {
                    var $newItems = $( res.data.html );
                    $grid.append( $newItems );
                    lazyLoadImages( $grid[0] );
                    $self.data('page', res.data.page);
                    if ( res.data.no_more ) { $('#bds-loadmore-wrap').hide(); }
                    else { $self.prop('disabled', false).text('Xem thêm'); }
                } else { $('#bds-loadmore-wrap').hide(); }
            },
            error: function () {
                $skeletons.remove();
                $self.prop('disabled', false).text('Xem thêm');
            }
        });
    });
});

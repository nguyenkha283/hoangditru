(function(){
'use strict';

document.addEventListener('DOMContentLoaded', function(){
    var carousel = document.getElementById('ci-carousel');
    var track    = document.getElementById('ci-carousel-track');
    if (!track || !carousel) return;

    var slides  = track.querySelectorAll('.ci-carousel__slide');
    var bullets = document.querySelectorAll('#ci-carousel-bullets .ci-bullet');
    var prevBtn = document.getElementById('ci-arrow-prev');
    var nextBtn = document.getElementById('ci-arrow-next');
    var total   = slides.length;
    var current = 0;
    var timer;

    if (total < 2) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
    }

    // Set each slide width = carousel width (pixel-based, fixes mobile)
    function setWidths() {
        var w = carousel.offsetWidth;
        track.style.width = (w * total) + 'px';
        for (var i = 0; i < slides.length; i++) {
            slides[i].style.width = w + 'px';
        }
    }

    function goTo(idx) {
        current = (idx + total) % total;
        var w = carousel.offsetWidth;
        track.style.transform = 'translateX(-' + (current * w) + 'px)';
        bullets.forEach(function(b, i){
            b.classList.toggle('is-active', i === current);
        });
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAuto() {
        clearInterval(timer);
        timer = setInterval(next, 3000);
    }

    // Recalc on resize
    window.addEventListener('resize', function(){
        setWidths();
        goTo(current);
    });

    if (prevBtn) prevBtn.addEventListener('click', function(){ prev(); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function(){ next(); startAuto(); });

    bullets.forEach(function(b, i){
        b.addEventListener('click', function(){ goTo(i); startAuto(); });
    });

    carousel.addEventListener('mouseenter', function(){ clearInterval(timer); });
    carousel.addEventListener('mouseleave', startAuto);

    // Touch swipe support
    var touchStartX = 0;
    carousel.addEventListener('touchstart', function(e){ touchStartX = e.touches[0].clientX; }, { passive: true });
    carousel.addEventListener('touchend', function(e){
        var diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) {
            if (diff > 0) next(); else prev();
            startAuto();
        }
    }, { passive: true });

    setWidths();
    goTo(0);
    startAuto();
});

})();

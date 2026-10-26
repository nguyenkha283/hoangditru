/* Landing Page Builder — Frontend JS */
(function () {
    'use strict';

    /* Accordion */
    document.querySelectorAll('.lpb-accordion__trigger').forEach(function (btn) {
        var bodyId = btn.getAttribute('aria-controls');
        var body   = bodyId ? document.getElementById(bodyId) : null;
        if ( body && btn.getAttribute('aria-expanded') === 'true' ) body.classList.add('is-open');

        btn.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!expanded));
            if (body) body.classList.toggle('is-open', !expanded);
        });
    });

    /* Scroll animation */
    if ('IntersectionObserver' in window) {
        var els = document.querySelectorAll(
            '.lpb-advantages__item,.lpb-benefits__item,.lpb-milestone__item,.lpb-accordion__item'
        );
        els.forEach(function (el) { el.classList.add('lpb-animate'); });

        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var siblings = Array.from(entry.target.parentElement.children);
                var delay    = siblings.indexOf(entry.target) * 80;
                setTimeout(function () { entry.target.classList.add('is-visible'); }, delay);
                obs.unobserve(entry.target);
            });
        }, { threshold: 0.15 });

        els.forEach(function (el) { obs.observe(el); });
    }
})();

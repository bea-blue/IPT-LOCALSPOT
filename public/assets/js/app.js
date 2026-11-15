// Minimal JavaScript. The site works without it; this only adds conveniences.

// Ask before submitting forms marked with data-confirm (e.g. delete).
// The server still requires POST + CSRF, so this is only a convenience.
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

// Photo slideshow on place pages.
// Without JavaScript every photo is simply listed. With it, one photo shows at a time and
// the next one appears every few seconds. It pauses while the pointer is over it or a
// control has focus, has a Pause/Play button, and does not start on its own for people
// who ask their system for reduced motion.
(function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-carousel]').forEach(function (root) {
        var slides = Array.prototype.slice.call(root.querySelectorAll('.carousel-slide'));
        var bar = root.querySelector('.carousel-bar');
        if (slides.length < 2 || !bar) {
            return;
        }
        var thumbs = Array.prototype.slice.call(root.querySelectorAll('.carousel-thumb'));
        var toggle = root.querySelector('.carousel-toggle');
        var track = root.querySelector('.carousel-track');
        var interval = parseInt(root.getAttribute('data-interval'), 10) || 5000;
        var index = 0;
        var timer = null;
        var userPaused = reduceMotion;
        var hovering = false;
        var focused = false;

        root.classList.add('is-ready');
        bar.hidden = false;

        function show(i) {
            index = (i + slides.length) % slides.length;
            slides.forEach(function (slide, n) {
                var active = n === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                var lazy = slide.querySelectorAll('img[loading="lazy"]');
                if (active) { lazy.forEach(function (img) { img.loading = 'eager'; }); }
            });
            thumbs.forEach(function (t, n) {
                if (n === index) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
            });
        }

        function running() { return !userPaused && !hovering && !focused; }

        function schedule() {
            window.clearInterval(timer);
            timer = null;
            if (running()) {
                timer = window.setInterval(function () { show(index + 1); }, interval);
            }
            // Announce slide changes only when the visitor is controlling it.
            track.setAttribute('aria-live', running() ? 'off' : 'polite');
            toggle.textContent = userPaused ? 'Play' : 'Pause';
            toggle.setAttribute('aria-label', userPaused ? 'Play slideshow' : 'Pause slideshow');
        }

        root.querySelector('.carousel-prev').addEventListener('click', function () { show(index - 1); schedule(); });
        root.querySelector('.carousel-next').addEventListener('click', function () { show(index + 1); schedule(); });
        toggle.addEventListener('click', function () { userPaused = !userPaused; schedule(); });
        thumbs.forEach(function (t) {
            t.addEventListener('click', function () { show(parseInt(t.getAttribute('data-slide'), 10)); schedule(); });
        });
        root.addEventListener('mouseenter', function () { hovering = true; schedule(); });
        root.addEventListener('mouseleave', function () { hovering = false; schedule(); });
        root.addEventListener('focusin', function () { focused = true; schedule(); });
        root.addEventListener('focusout', function (e) {
            if (!root.contains(e.relatedTarget)) { focused = false; schedule(); }
        });

        show(0);
        schedule();
    });
})();

// Menu photo viewer: clicking a menu photo opens it large on the page.
// Without JavaScript (or <dialog> support) the link simply opens the image in a new tab.
(function () {
    var dialog = document.querySelector('dialog.lightbox');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }
    var img = dialog.querySelector('.lightbox-body img');
    var caption = dialog.querySelector('.lightbox-caption');
    var open = dialog.querySelector('.lightbox-open');
    var lastTrigger = null;

    document.querySelectorAll('a[data-lightbox]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            lastTrigger = link;
            var thumb = link.querySelector('img');
            img.src = link.href;
            img.alt = thumb ? thumb.alt : '';
            caption.textContent = link.getAttribute('data-caption') || '';
            open.href = link.href;
            dialog.showModal();
            dialog.querySelector('.lightbox-close').focus();
        });
    });
    dialog.querySelector('.lightbox-close').addEventListener('click', function () { dialog.close(); });
    // Clicking the dark backdrop (outside the content) closes it too.
    dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });
    dialog.addEventListener('close', function () {
        img.removeAttribute('src');
        if (lastTrigger) { lastTrigger.focus(); }
    });
})();

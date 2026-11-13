// Public website: slider, gallery filter and viewer, pop-up notice and header shadow.
// Without this script the first slide and every picture still show.

const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-slider]').forEach((slider) => {
    const slides = [...slider.querySelectorAll('[data-slide]')];
    const dots = [...slider.querySelectorAll('[data-dot]')];
    if (slides.length < 2) return;

    let current = 0;
    let timer = null;
    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === current);
            slide.toggleAttribute('aria-hidden', i !== current);
            slide.inert = i !== current;
        });
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    };
    const seconds = Number(slider.dataset.interval);
    const stop = () => clearInterval(timer);
    const start = () => {
        stop();
        if (seconds > 0 && !calm) timer = setInterval(() => show(current + 1), seconds * 1000);
    };

    slider.querySelector('[data-prev]').addEventListener('click', () => { show(current - 1); start(); });
    slider.querySelector('[data-next]').addEventListener('click', () => { show(current + 1); start(); });
    dots.forEach((dot) => dot.addEventListener('click', () => { show(Number(dot.dataset.dot)); start(); }));
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);
    show(0);
    start();
});

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const buttons = gallery.querySelectorAll('[data-filter]');
    buttons.forEach((button) => button.addEventListener('click', () => {
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        gallery.querySelectorAll('[data-category]').forEach((figure) => {
            figure.hidden = button.dataset.filter !== '' && figure.dataset.category !== button.dataset.filter;
        });
    }));

    const viewer = gallery.querySelector('[data-lightbox]');
    gallery.querySelectorAll('[data-full]').forEach((button) => button.addEventListener('click', () => {
        viewer.querySelector('img').src = button.dataset.full;
        viewer.showModal();
    }));
});

const header = document.querySelector('[data-site-header]');
if (header) {
    const shade = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', shade, { passive: true });
    shade();
}

// The notice appears once per browser session, and again if the school changes it.
const popup = document.querySelector('[data-popup]');
if (popup) {
    const key = 'site-popup-' + popup.dataset.popup;
    let seen = false;
    try { seen = sessionStorage.getItem(key) === '1'; sessionStorage.setItem(key, '1'); } catch { /* storage blocked: just show it */ }
    if (!seen) popup.showModal();
}

(function () {
    const body = document.body;
    const main = document.querySelector('main');
    const filename = window.location.pathname.split('/').pop().replace(/\.(html|php)$/i, '');
    const page = filename === '' ? 'index' : filename;
    body.dataset.page = page;

    const toggleHost = document.querySelector('.navi') ||
        document.querySelector('.dashboard-topbar');

    if (toggleHost) {
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'theme-toggle';

        function updateToggle(theme) {
            const dark = theme === 'dark';
            toggle.textContent = dark ? '☀ Mode clair' : '☾ Mode sombre';
            toggle.setAttribute('aria-label', dark ? 'Activer le mode clair' : 'Activer le mode sombre');
            toggle.setAttribute('aria-pressed', String(dark));
        }

        const savedTheme = localStorage.getItem('congovital-theme');
        const initialTheme = savedTheme === 'dark' || savedTheme === 'light'
            ? savedTheme
            : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = initialTheme;
        updateToggle(initialTheme);
        toggle.addEventListener('click', function () {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('congovital-theme', nextTheme);
            updateToggle(nextTheme);
        });
        toggleHost.appendChild(toggle);
    }

    if (!main || !('IntersectionObserver' in window) ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const cardSelector = '.service-card, .tip-card, .topic-card, .faq-item, ' +
        '.conseil-article, .dashboard-card, .appointment-item, .history-item, ' +
        '.stat-card, .card';
    const targets = Array.from(main.querySelectorAll(
        ':scope > section, :scope > div, :scope > article, :scope > aside, ' +
        cardSelector
    )).filter(function (target) {
        return !target.querySelector(cardSelector);
    });
    const observer = new IntersectionObserver(function (entries, currentObserver) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                currentObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    targets.forEach(function (target) {
        target.classList.add('reveal');
        observer.observe(target);
    });
})();

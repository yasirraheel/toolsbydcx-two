(function () {
    'use strict';

    var storageKey = 'toolsbydcx-theme';
    var root = document.documentElement;

    function getTheme() {
        return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    function updateControls(theme) {
        var isLight = theme === 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            var icon = button.querySelector('[data-theme-icon]');
            var label = button.querySelector('[data-theme-label]');
            button.setAttribute('aria-pressed', isLight ? 'true' : 'false');
            button.setAttribute('aria-label', isLight ? 'Switch to dark mode' : 'Switch to light mode');
            button.setAttribute('title', isLight ? 'Switch to dark mode' : 'Switch to light mode');
            if (icon) {
                icon.classList.toggle('la-sun', !isLight);
                icon.classList.toggle('la-moon', isLight);
            }
            if (label) label.textContent = isLight ? 'Dark' : 'Light';
        });
    }

    function setTheme(theme) {
        theme = theme === 'light' ? 'light' : 'dark';
        root.setAttribute('data-theme', theme);
        try { window.localStorage.setItem(storageKey, theme); } catch (error) { /* private browsing */ }
        updateControls(theme);
        window.dispatchEvent(new CustomEvent('toolsbydcx:themechange', { detail: { theme: theme } }));
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-theme-toggle]');
        if (!button) return;
        event.preventDefault();
        setTheme(getTheme() === 'light' ? 'dark' : 'light');
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { updateControls(getTheme()); });
    } else {
        updateControls(getTheme());
    }
})();

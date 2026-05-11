/**
 * Dark/Light Mode Theme Toggle
 * Handles localStorage, system preference, and theme switching
 */

(function() {
    'use strict';

    // Theme configuration
    const STORAGE_KEY = 'theme-preference';
    const THEMES = {
        LIGHT: 'light',
        DARK: 'dark'
    };

    /**
     * Get the current theme from localStorage or system preference
     */
    function getCurrentTheme() {
        const savedTheme = localStorage.getItem(STORAGE_KEY);
        
        if (savedTheme === THEMES.LIGHT || savedTheme === THEMES.DARK) {
            return savedTheme;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return THEMES.DARK;
        }
        
        return THEMES.LIGHT;
    }

    /**
     * Apply theme to the document
     */
    function applyTheme(theme) {
        const isDark = theme === THEMES.DARK;
        const root = document.documentElement;
        
        if (isDark) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        
        // Update theme toggle icon if present
        updateThemeIcon(isDark);
        
        // Dispatch custom event for other scripts
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    /**
     * Update the theme toggle button icon
     */
    function updateThemeIcon(isDark) {
        const themeToggle = document.querySelector('.theme-toggle');
        if (!themeToggle) return;
        
        if (isDark) {
            themeToggle.innerHTML = '☀️';
            themeToggle.setAttribute('aria-label', 'Switch to light mode');
        } else {
            themeToggle.innerHTML = '🌙';
            themeToggle.setAttribute('aria-label', 'Switch to dark mode');
        }
    }

    /**
     * Set theme and save to localStorage
     */
    function setTheme(theme) {
        if (theme !== THEMES.LIGHT && theme !== THEMES.DARK) return;
        
        localStorage.setItem(STORAGE_KEY, theme);
        applyTheme(theme);
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === THEMES.LIGHT ? THEMES.DARK : THEMES.LIGHT;
        setTheme(newTheme);
    }

    /**
     * Listen for system preference changes
     */
    function listenForSystemPreferenceChanges() {
        if (!window.matchMedia) return;
        
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', (e) => {
            // Only apply if user hasn't explicitly set a preference
            if (!localStorage.getItem(STORAGE_KEY)) {
                const newTheme = e.matches ? THEMES.DARK : THEMES.LIGHT;
                applyTheme(newTheme);
            }
        });
    }

    /**
     * Initialize theme
     */
    function initTheme() {
        const theme = getCurrentTheme();
        applyTheme(theme);
        
        // Add toggle event listener
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }
        
        listenForSystemPreferenceChanges();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

})();
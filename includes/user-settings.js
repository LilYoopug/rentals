// User Settings Manager - localStorage based
(function() {
  'use strict';

  const SETTINGS_KEY = 'lenscraft_user_settings';
  
  const DEFAULT_SETTINGS = {
    language: 'id',
    timezone: 'Asia/Jakarta',
    theme: 'dark', // Always dark mode
    is_profile_public: false,
    allow_marketing: false,
    allow_data_export: true
  };

  // Get settings from localStorage
  function getSettings() {
    try {
      const stored = localStorage.getItem(SETTINGS_KEY);
      if (stored) {
        return Object.assign({}, DEFAULT_SETTINGS, JSON.parse(stored));
      }
    } catch (e) {
      console.warn('Failed to load settings from localStorage:', e);
    }
    return Object.assign({}, DEFAULT_SETTINGS);
  }

  // Save settings to localStorage
  function saveSettings(settings) {
    try {
      const merged = Object.assign({}, getSettings(), settings);
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(merged));
      return true;
    } catch (e) {
      console.error('Failed to save settings to localStorage:', e);
      return false;
    }
  }

  // Apply theme (always dark mode)
  function applyTheme(theme) {
    // Always use dark mode, ignore theme parameter
    document.documentElement.classList.add('dark-theme');
    document.documentElement.classList.remove('light-theme');
    document.body.classList.remove('light-mode');
  }

  // Initialize settings on page load
  function initSettings() {
    const settings = getSettings();
    
    // Apply theme
    applyTheme(settings.theme);
    
    // Expose to window for backward compatibility
    window.currentSettings = settings;
    
    return settings;
  }

  // Public API
  window.UserSettings = {
    get: getSettings,
    save: saveSettings,
    init: initSettings,
    applyTheme: applyTheme,
    DEFAULT: DEFAULT_SETTINGS
  };

  // Auto-initialize
  initSettings();
})();

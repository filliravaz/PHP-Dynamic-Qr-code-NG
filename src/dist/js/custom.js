/**
 * Theme & UI Customization with Dark Mode & Presets
 * Persists user preferences via localStorage
 */
(function ($) {
  'use strict';

  var STORAGE_KEY = 'qr_app_theme_settings';

  var navbar_dark_skins = [
    'navbar-primary',
    'navbar-secondary',
    'navbar-info',
    'navbar-success',
    'navbar-danger',
    'navbar-indigo',
    'navbar-purple',
    'navbar-pink',
    'navbar-navy',
    'navbar-lightblue',
    'navbar-teal',
    'navbar-cyan',
    'navbar-dark',
    'navbar-gray-dark',
    'navbar-gray'
  ];

  var navbar_light_skins = [
    'navbar-light',
    'navbar-warning',
    'navbar-white',
    'navbar-orange'
  ];

  var navbar_all_colors = navbar_dark_skins.concat(navbar_light_skins);

  var sidebar_colors = [
    'bg-primary',
    'bg-warning',
    'bg-info',
    'bg-danger',
    'bg-success',
    'bg-indigo',
    'bg-lightblue',
    'bg-navy',
    'bg-purple',
    'bg-fuchsia',
    'bg-pink',
    'bg-maroon',
    'bg-orange',
    'bg-lime',
    'bg-teal',
    'bg-olive'
  ];

  var accent_colors = [
    'accent-primary',
    'accent-warning',
    'accent-info',
    'accent-danger',
    'accent-success',
    'accent-indigo',
    'accent-lightblue',
    'accent-navy',
    'accent-purple',
    'accent-fuchsia',
    'accent-pink',
    'accent-maroon',
    'accent-orange',
    'accent-lime',
    'accent-teal',
    'accent-olive'
  ];

  var sidebar_skins = [];
  sidebar_colors.forEach(function (c) {
    var raw = c.replace('bg-', '');
    sidebar_skins.push('sidebar-dark-' + raw);
    sidebar_skins.push('sidebar-light-' + raw);
  });

  // Presets definition
  var PRESETS = {
    'dark-full': {
      label: 'Full Dark (Default)',
      icon: 'fas fa-moon text-info',
      darkMode: true,
      navbarSkin: 'navbar-dark navbar-gray-dark',
      sidebarSkin: 'sidebar-dark-primary',
      accentSkin: 'accent-primary'
    },
    'dark-midnight': {
      label: 'Midnight Blue',
      icon: 'fas fa-cloud-moon text-primary',
      darkMode: true,
      navbarSkin: 'navbar-dark navbar-navy',
      sidebarSkin: 'sidebar-dark-lightblue',
      accentSkin: 'accent-lightblue'
    },
    'dark-emerald': {
      label: 'Emerald Teal',
      icon: 'fas fa-leaf text-success',
      darkMode: true,
      navbarSkin: 'navbar-dark navbar-teal',
      sidebarSkin: 'sidebar-dark-teal',
      accentSkin: 'accent-teal'
    },
    'dark-sunset': {
      label: 'Sunset Amber',
      icon: 'fas fa-fire text-warning',
      darkMode: true,
      navbarSkin: 'navbar-dark navbar-orange',
      sidebarSkin: 'sidebar-dark-warning',
      accentSkin: 'accent-warning'
    },
    'dark-purple': {
      label: 'Cyberpunk Purple',
      icon: 'fas fa-bolt text-purple',
      darkMode: true,
      navbarSkin: 'navbar-dark navbar-purple',
      sidebarSkin: 'sidebar-dark-purple',
      accentSkin: 'accent-purple'
    },
    'light-clean': {
      label: 'Clean Light',
      icon: 'fas fa-sun text-warning',
      darkMode: false,
      navbarSkin: 'navbar-white navbar-light',
      sidebarSkin: 'sidebar-dark-primary',
      accentSkin: ''
    }
  };

  function getSavedSettings() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveSettings(settings) {
    try {
      var current = getSavedSettings() || {};
      var merged = Object.assign({}, current, settings);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(merged));
    } catch (e) {}
  }

  function applyNavbarSkin(skinClass) {
    var $header = $('.main-header');
    if (!$header.length || !skinClass) return;

    $header.removeClass('navbar-dark navbar-light');
    navbar_all_colors.forEach(function (c) {
      $header.removeClass(c);
    });

    var parts = skinClass.split(' ');
    parts.forEach(function (p) {
      if (p) $header.addClass(p);
    });

    if (skinClass.indexOf('navbar-dark') === -1 && skinClass.indexOf('navbar-light') === -1) {
      var hasDark = navbar_dark_skins.some(function (d) { return skinClass.indexOf(d) > -1; });
      $header.addClass(hasDark ? 'navbar-dark' : 'navbar-light');
    }
  }

  function applySidebarSkin(skinClass) {
    var $sidebar = $('.main-sidebar');
    if (!$sidebar.length || !skinClass) return;

    sidebar_skins.forEach(function (s) {
      $sidebar.removeClass(s);
    });
    $sidebar.addClass(skinClass);
  }

  function applyAccentSkin(accentClass) {
    var $body = $('body');
    accent_colors.forEach(function (a) {
      $body.removeClass(a);
    });
    if (accentClass) {
      $body.addClass(accentClass);
    }
  }

  function setDarkMode(enabled) {
    var $body = $('body');
    if (enabled) {
      $body.addClass('dark-mode');
    } else {
      $body.removeClass('dark-mode');
    }
  }

  function applyAllSettings(settings) {
    if (!settings) return;

    if (typeof settings.darkMode === 'boolean') {
      setDarkMode(settings.darkMode);
    }
    if (settings.navbarSkin) {
      applyNavbarSkin(settings.navbarSkin);
    }
    if (settings.sidebarSkin) {
      applySidebarSkin(settings.sidebarSkin);
    }
    if (typeof settings.accentSkin === 'string') {
      applyAccentSkin(settings.accentSkin);
    }
    if (typeof settings.noNavbarBorder === 'boolean') {
      $('.main-header').toggleClass('border-bottom-0', settings.noNavbarBorder);
    }
    if (typeof settings.bodySmallText === 'boolean') {
      $('body').toggleClass('text-sm', settings.bodySmallText);
    }
    if (typeof settings.navbarSmallText === 'boolean') {
      $('.main-header').toggleClass('text-sm', settings.navbarSmallText);
    }
    if (typeof settings.sidebarSmallText === 'boolean') {
      $('.nav-sidebar').toggleClass('text-sm', settings.sidebarSmallText);
    }
    if (typeof settings.footerSmallText === 'boolean') {
      $('.main-footer').toggleClass('text-sm', settings.footerSmallText);
    }
    if (typeof settings.flatSidebar === 'boolean') {
      $('.nav-sidebar').toggleClass('nav-flat', settings.flatSidebar);
    }
    if (typeof settings.legacySidebar === 'boolean') {
      $('.nav-sidebar').toggleClass('nav-legacy', settings.legacySidebar);
    }
    if (typeof settings.compactSidebar === 'boolean') {
      $('.nav-sidebar').toggleClass('nav-compact', settings.compactSidebar);
    }
    if (typeof settings.childIndentSidebar === 'boolean') {
      $('.nav-sidebar').toggleClass('nav-child-indent', settings.childIndentSidebar);
    }
    if (typeof settings.noExpandSidebar === 'boolean') {
      $('.main-sidebar').toggleClass('sidebar-no-expand', settings.noExpandSidebar);
    }
    if (typeof settings.brandSmallText === 'boolean') {
      $('.brand-link').toggleClass('text-sm', settings.brandSmallText);
    }
  }

  // Apply saved theme immediately on script execution
  var initialSettings = getSavedSettings();
  if (initialSettings) {
    applyAllSettings(initialSettings);
  }

  $(function () {
    // Re-apply once DOM is fully ready
    if (initialSettings) {
      applyAllSettings(initialSettings);
    }

    var $sidebar = $('.control-sidebar');
    if (!$sidebar.length) return;

    var $container = $('<div />', {
      class: 'p-3 control-sidebar-content'
    });
    $sidebar.append($container);

    // Header & Reset
    var $headerRow = $('<div class="d-flex justify-content-between align-items-center mb-2">' +
      '<h5 class="m-0">Customize</h5>' +
      '<a href="javascript:void(0)" class="text-xs text-muted" id="reset-theme-btn" title="Reset to Defaults"><i class="fas fa-undo mr-1"></i>Reset</a>' +
      '</div><hr class="mb-3"/>');
    $container.append($headerRow);

    // 1. Dark Mode Switch
    var isDark = $('body').hasClass('dark-mode');
    var $darkModeSwitch = $('<div class="mb-3 p-2 border rounded" style="background: rgba(255,255,255,0.05);">' +
      '<div class="custom-control custom-switch">' +
      '<input type="checkbox" class="custom-control-input" id="dark-mode-toggle" ' + (isDark ? 'checked' : '') + '>' +
      '<label class="custom-control-label font-weight-bold" for="dark-mode-toggle">' +
      '<i class="fas fa-moon mr-1 text-warning"></i> Dark Mode' +
      '</label>' +
      '</div>' +
      '<small class="text-muted d-block mt-1">Switch entire page to dark theme</small>' +
      '</div>');

    $darkModeSwitch.find('#dark-mode-toggle').on('change', function () {
      var enabled = $(this).is(':checked');
      setDarkMode(enabled);
      var currentHeader = getSavedSettings() && getSavedSettings().navbarSkin;
      var newHeader = currentHeader;
      if (enabled) {
        if (!newHeader || newHeader.indexOf('navbar-light') > -1 || newHeader.indexOf('navbar-white') > -1) {
          newHeader = 'navbar-dark navbar-gray-dark';
          applyNavbarSkin(newHeader);
        }
      } else {
        if (newHeader && newHeader.indexOf('navbar-gray-dark') > -1) {
          newHeader = 'navbar-white navbar-light';
          applyNavbarSkin(newHeader);
        }
      }
      saveSettings({ darkMode: enabled, navbarSkin: newHeader });
      updateActivePreset();
    });

    $container.append($darkModeSwitch);

    // 2. Presets Section
    $container.append('<h6 class="mt-3 mb-2 font-weight-bold"><i class="fas fa-palette mr-1 text-primary"></i> Theme Presets</h6>');
    var $presetsContainer = $('<div class="mb-3" id="presets-list"></div>');

    Object.keys(PRESETS).forEach(function (presetKey) {
      var p = PRESETS[presetKey];
      var $btn = $('<button type="button" class="theme-preset-btn" data-preset="' + presetKey + '">' +
        '<span class="preset-icon"><i class="' + p.icon + '"></i></span>' +
        '<span>' + p.label + '</span>' +
        '</button>');

      $btn.on('click', function () {
        setDarkMode(p.darkMode);
        applyNavbarSkin(p.navbarSkin);
        applySidebarSkin(p.sidebarSkin);
        applyAccentSkin(p.accentSkin);

        $('#dark-mode-toggle').prop('checked', p.darkMode);

        saveSettings({
          activePreset: presetKey,
          darkMode: p.darkMode,
          navbarSkin: p.navbarSkin,
          sidebarSkin: p.sidebarSkin,
          accentSkin: p.accentSkin
        });

        updateActivePreset(presetKey);
      });

      $presetsContainer.append($btn);
    });

    $container.append($presetsContainer);

    function updateActivePreset(activeKey) {
      $presetsContainer.find('.theme-preset-btn').removeClass('active');
      if (activeKey) {
        $presetsContainer.find('[data-preset="' + activeKey + '"]').addClass('active');
      }
    }

    var saved = getSavedSettings();
    if (saved && saved.activePreset) {
      updateActivePreset(saved.activePreset);
    }

    // Reset button handler
    $('#reset-theme-btn').on('click', function () {
      localStorage.removeItem(STORAGE_KEY);
      location.reload();
    });

    // 3. Layout Options Checkboxes
    $container.append('<h6 class="mt-4 mb-2 font-weight-bold">Layout Options</h6>');

    function createCheckbox(label, checked, onChange) {
      var id = 'chk_' + Math.random().toString(36).substring(2, 9);
      var $cb = $('<input />', {
        type: 'checkbox',
        id: id,
        class: 'mr-1',
        checked: checked
      }).on('change', function () {
        onChange($(this).is(':checked'));
      });
      return $('<div class="mb-1"></div>').append($cb).append($('<label />', { for: id, class: 'font-weight-normal m-0' }).text(label));
    }

    $container.append(createCheckbox('No Navbar border', $('.main-header').hasClass('border-bottom-0'), function (val) {
      $('.main-header').toggleClass('border-bottom-0', val);
      saveSettings({ noNavbarBorder: val });
    }));

    $container.append(createCheckbox('Body small text', $('body').hasClass('text-sm'), function (val) {
      $('body').toggleClass('text-sm', val);
      saveSettings({ bodySmallText: val });
    }));

    $container.append(createCheckbox('Navbar small text', $('.main-header').hasClass('text-sm'), function (val) {
      $('.main-header').toggleClass('text-sm', val);
      saveSettings({ navbarSmallText: val });
    }));

    $container.append(createCheckbox('Sidebar nav small text', $('.nav-sidebar').hasClass('text-sm'), function (val) {
      $('.nav-sidebar').toggleClass('text-sm', val);
      saveSettings({ sidebarSmallText: val });
    }));

    $container.append(createCheckbox('Footer small text', $('.main-footer').hasClass('text-sm'), function (val) {
      $('.main-footer').toggleClass('text-sm', val);
      saveSettings({ footerSmallText: val });
    }));

    $container.append(createCheckbox('Sidebar nav flat style', $('.nav-sidebar').hasClass('nav-flat'), function (val) {
      $('.nav-sidebar').toggleClass('nav-flat', val);
      saveSettings({ flatSidebar: val });
    }));

    $container.append(createCheckbox('Sidebar nav legacy style', $('.nav-sidebar').hasClass('nav-legacy'), function (val) {
      $('.nav-sidebar').toggleClass('nav-legacy', val);
      saveSettings({ legacySidebar: val });
    }));

    $container.append(createCheckbox('Sidebar nav compact', $('.nav-sidebar').hasClass('nav-compact'), function (val) {
      $('.nav-sidebar').toggleClass('nav-compact', val);
      saveSettings({ compactSidebar: val });
    }));

    $container.append(createCheckbox('Sidebar nav child indent', $('.nav-sidebar').hasClass('nav-child-indent'), function (val) {
      $('.nav-sidebar').toggleClass('nav-child-indent', val);
      saveSettings({ childIndentSidebar: val });
    }));

    $container.append(createCheckbox('Main Sidebar disable hover expand', $('.main-sidebar').hasClass('sidebar-no-expand'), function (val) {
      $('.main-sidebar').toggleClass('sidebar-no-expand', val);
      saveSettings({ noExpandSidebar: val });
    }));

    $container.append(createCheckbox('Brand small text', $('.brand-link').hasClass('text-sm'), function (val) {
      $('.brand-link').toggleClass('text-sm', val);
      saveSettings({ brandSmallText: val });
    }));

    // 4. Color Swatches
    function createSkinBlock(colors, callback) {
      var $block = $('<div />', {
        class: 'd-flex flex-wrap mb-3'
      });

      colors.forEach(function (color) {
        var $color = $('<div />', {
          class: (typeof color === 'object' ? color.join(' ') : color)
            .replace('navbar-', 'bg-')
            .replace('accent-', 'bg-') + ' elevation-2'
        });

        $block.append($color);
        $color.data('color', color);

        $color.css({
          width: '36px',
          height: '20px',
          borderRadius: '12px',
          marginRight: 8,
          marginBottom: 8,
          opacity: 0.85,
          cursor: 'pointer'
        });

        $color.hover(
          function () { $(this).css({ opacity: 1 }).removeClass('elevation-2').addClass('elevation-4'); },
          function () { $(this).css({ opacity: 0.85 }).removeClass('elevation-4').addClass('elevation-2'); }
        );

        if (callback) {
          $color.on('click', callback);
        }
      });

      return $block;
    }

    $container.append('<h6 class="mt-4 mb-2 font-weight-bold">Navbar Variants</h6>');
    $container.append(createSkinBlock(navbar_all_colors, function () {
      var color = $(this).data('color');
      applyNavbarSkin(color);
      saveSettings({ navbarSkin: color, activePreset: null });
      updateActivePreset(null);
    }));

    $container.append('<h6 class="mt-3 mb-2 font-weight-bold">Accent Color Variants</h6>');
    $container.append(createSkinBlock(accent_colors, function () {
      var color = $(this).data('color');
      applyAccentSkin(color);
      saveSettings({ accentSkin: color, activePreset: null });
      updateActivePreset(null);
    }));

    $container.append('<h6 class="mt-3 mb-2 font-weight-bold">Dark Sidebar Variants</h6>');
    $container.append(createSkinBlock(sidebar_colors, function () {
      var color = $(this).data('color');
      var skin = 'sidebar-dark-' + color.replace('bg-', '');
      applySidebarSkin(skin);
      saveSettings({ sidebarSkin: skin, activePreset: null });
      updateActivePreset(null);
    }));

    $container.append('<h6 class="mt-3 mb-2 font-weight-bold">Light Sidebar Variants</h6>');
    $container.append(createSkinBlock(sidebar_colors, function () {
      var color = $(this).data('color');
      var skin = 'sidebar-light-' + color.replace('bg-', '');
      applySidebarSkin(skin);
      saveSettings({ sidebarSkin: skin, activePreset: null });
      updateActivePreset(null);
    }));

    var logo_skins = navbar_all_colors;
    $container.append('<h6 class="mt-3 mb-2 font-weight-bold">Brand Logo Variants</h6>');
    var $clear_btn = $('<a href="javascript:void(0)" class="text-xs d-block mb-2 text-muted"><i class="fas fa-times-circle mr-1"></i>Clear brand color</a>');
    $clear_btn.on('click', function () {
      var $logo = $('.brand-link');
      logo_skins.forEach(function (s) { $logo.removeClass(s); });
      saveSettings({ brandSkin: '' });
    });
    $container.append($clear_btn);
    $container.append(createSkinBlock(logo_skins, function () {
      var color = $(this).data('color');
      var $logo = $('.brand-link');
      logo_skins.forEach(function (s) { $logo.removeClass(s); });
      $logo.addClass(color);
      saveSettings({ brandSkin: color });
    }));
  });

  // Product image thumb handler
  $('.product-image-thumb').on('click', function () {
    var image_element = $(this).find('img');
    $('.product-image').prop('src', $(image_element).attr('src'));
    $('.product-image-thumb.active').removeClass('active');
    $(this).addClass('active');
  });

  // Bulk actions handling
  var bulk_action = $('input[name=bulk-select]');
  if (bulk_action.length) {
    bulk_action.on('change', function () {
      var isChecked = $(this).prop('checked');
      var bulkActionDiv = document.getElementById('bulk-action-div');
      if (bulkActionDiv) {
        bulkActionDiv.style.display = isChecked ? 'block' : 'none';
      }
      $('input[name^=action]').prop('checked', isChecked);
    });
  }

  var bulk_form = $('form#bulk-action');
  if (bulk_form.length) {
    bulk_form.on('submit', function (e) {
      e.preventDefault();
      var paramsElements = document.querySelectorAll('input[name^=action]');
      var params = [];

      paramsElements.forEach(function (val) {
        if (val.checked) {
          params.push(val.value);
        }
      });

      var csrfToken = $('input[name=csrf_token]').val() || '';

      var data = {
        action: $('select[name=action]').val(),
        params: params,
        type: $('input[name=type]').val(),
        csrf_token: csrfToken
      };

      $.ajax({
        url: './bulk_action.php',
        method: 'POST',
        headers: {
          'X-CSRF-Token': csrfToken
        },
        data: JSON.stringify(data),
        dataType: 'json',
        contentType: 'application/json',
        success: function (res) {
          if (res.status == 200) {
            if (data['action'] === 'download') {
              window.location.href = res.data;
              $('#err-msg').css('display', 'none');
            } else if (data['action'] === 'delete') {
              location.reload();
            }
          }

          if (res.status == 400 || res.status == 403) {
            var $err = $('#err-msg').empty().append('<i class="fas fa-exclamation-circle"></i> ');
            $err.append(document.createTextNode(' ' + (res.data || 'Error occurred')));
            $('#err-msg').css('display', 'block');
          }
        },
        error: function (err) {
          var errorText = (err && err.responseText) ? err.responseText : 'An unexpected error occurred.';
          var $err = $('#err-msg').empty().append('<i class="fas fa-exclamation-circle"></i> ');
          $err.append(document.createTextNode(' ' + errorText));
          $('#err-msg').css('display', 'block');
        }
      });

      return false;
    });
  }
})(jQuery);

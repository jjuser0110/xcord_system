<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<!-- Flat Picker -->
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<!-- Form Validation -->
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/ui-modals.js') }}"></script>

<!-- Page JS -->
<!-- <script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script> -->
<!-- <script src="{{ asset('assets/js/tables-datatables-basic.js') }}"></script> -->

@yield('page-js')

@yield('scripts')
<script>
    function showLoading(){
        document.getElementById('loading-screen').classList.remove('d-none');
    }

    function hideLoading(){
        document.getElementById('loading-screen').classList.add('d-none');
    }

    window.addEventListener("pageshow", function (event) {
        hideLoading();
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateThemeIcon(theme) {
      const iconEl = document.getElementById('active-theme-icon');
      if (!iconEl) return;

      iconEl.classList.remove('bx-sun', 'bx-moon', 'bx-desktop');

      if (theme === 'dark') {
        iconEl.classList.add('bx-moon');
      } else if (theme === 'system') {
        iconEl.classList.add('bx-desktop');
      } else {
        iconEl.classList.add('bx-sun');
      }
    }

    window.setTheme = function(theme) {
        const htmlEl = document.documentElement;
        const coreLink = document.querySelector('#core-css-link');
        const themeLink = document.querySelector('#theme-css-link');
        let activeTheme = theme;

        if (coreLink && themeLink && typeof assetsPath !== 'undefined') {
            // Adjust this path base depending on whether you use 'rtl/' or not
            let cssFolder = assetsPath + 'vendor/css/rtl/';

            if (activeTheme === 'dark' || theme === 'bordered-dark' || theme === 'default-dark') {
                coreLink.href = cssFolder + 'core-dark.css';
                themeLink.href = cssFolder + 'theme-default-dark.css';
            } else {
                // Fallback for light or default modes
                coreLink.href = cssFolder + 'core.css';
                themeLink.href = cssFolder + 'theme-default.css';
            }
        }

        // Save preference and update UI elements
        localStorage.setItem('app_theme', theme);
        updateThemeIcon(theme);
    };

    // 1. Get saved theme on load (default to 'light')
    const currentTheme = localStorage.getItem('app_theme') || 'light';

    // 2. Apply it immediately on DOM load so the correct CSS files load right away
    setTheme(currentTheme);

    // Click listener for dropdown items
    document.addEventListener('click', function (e) {
      const item = e.target.closest('.dropdown-styles .dropdown-item');
      if (item) {
        e.preventDefault();
        const themeValue = item.getAttribute('data-theme');
        if (themeValue) {
          showLoading();
          setTheme(themeValue);

          setTimeout(() => {
            hideLoading();
          }, 300);
        }
      }
    });
});
</script>

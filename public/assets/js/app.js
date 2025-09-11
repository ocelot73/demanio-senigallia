// /public/assets/js/app.js

// Passa le configurazioni da PHP a JavaScript
const APP_URL = (typeof APP_URL !== 'undefined') ? APP_URL : '.';
const FIELD_HELP = (typeof FIELD_HELP !== 'undefined') ? FIELD_HELP : {};
const hiddenColumns = (typeof hiddenColumns !== 'undefined') ? hiddenColumns : [];

// --- Funzioni di Utilità per AJAX ---
function toggleColumn(n) { $.post(APP_URL + '/index.php?action=toggle_column', { toggle_column: n }, r => { if(r.success) location.reload(); }, 'json'); }
function applyFilter(n, v) { $.post(window.location.href.split('?')[0] + '?action=set_filter', { set_filter: n, filter_value: v }, r => { if(r.success) location.reload(); }, 'json'); }

$(document).ready(function() {

    // --- GESTIONE UI GLOBALE (Sidebar, Tema, Modali) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });

    const openModal = (modalId) => $(`#${modalId}`).css('display','flex').delay(10).queue(function(next){ $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-popup').remove(); }, 300);
    };

    $('.modal-overlay, .modal-close-btn, #editCancelBtn').on('click', function(e) {
        if (e.target === this) {
            closeModal($(this).closest('.modal-overlay').attr('id'));
        }
    });
    $('.modal-container').on('click', e => e.stopPropagation());

    $('.submenu-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).parent('.has-submenu').toggleClass('open');
    });

    const themeToggle = $('#theme-toggle');
    function setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark-theme');
            themeToggle.find('i').removeClass('fa-moon').addClass('fa-sun');
            themeToggle.find('.link-text').text('Tema Chiaro');
        } else {
            document.documentElement.classList.remove('dark-theme');
            themeToggle.find('i').removeClass('fa-sun').addClass('fa-moon');
            themeToggle.find('.link-text').text('Tema Scuro');
        }
    }
    themeToggle.on('click', () => {
        const newTheme = document.documentElement.classList.contains('dark-theme') ? 'light' : 'dark';
        setTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
    setTheme(localStorage.getItem('theme') || '

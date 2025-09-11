// /public/assets/js/app.js

// Passa le configurazioni da PHP a JavaScript
const APP_URL = (typeof APP_URL !== 'undefined') ? APP_URL : '.';
const FIELD_HELP = (typeof FIELD_HELP !== 'undefined') ? FIELD_HELP : {};
const hiddenColumns = (typeof hiddenColumns !== 'undefined') ? hiddenColumns : [];

// --- Funzioni di Utilità per AJAX ---
function toggleColumn(n) { $.post(APP_URL + '/index.php', { action: 'toggle_column', toggle_column: n }, r => { if(r.success) location.reload(); }, 'json'); }
function applyFilter(n, v) { $.post(window.location.href.split('?')[0], { action: 'set_filter', set_filter: n, filter_value: v }, r => { if(r.success) location.reload(); }, 'json'); }

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
    setTheme(localStorage.getItem('theme') || 'light');

    // --- GESTIONE TABELLA ---
    function updateHiddenColumnsDisplay(){
        const bar=$('#hiddenColumnsBar'), list=$('#hiddenColumnsList');
        if(hiddenColumns.length > 0){
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else {
            bar.hide();
        }
    }
    updateHiddenColumnsDisplay();

    $('.filter-input').on('keypress', function(e){ if (e.key==='Enter'){ e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });
    
    // --- RICERCA GLOBALE CON EVIDENZIAZIONE (CORRETTA E FUNZIONANTE) ---
    function highlightHTML(html, regex){
        if (!html) return '';
        return html.split(/(<[^>]*>)/g).map(part => {
            return part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>');
        }).join('');
    }
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 1 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            $row.find('td').each(function() {
                const $cell = $(this);
                if ($cell.data('origHtml')) { $cell.html($cell.data('origHtml')); }
            });
            if (!regex) { $row.show(); return; }
            const match = regex.test($row.text());
            $row.toggle(match);
            if (match) {
                $row.find('td').each(function() {
                    const $cell = $(this);
                    if (!$cell.data('origHtml')) { $cell.data('origHtml', $cell.html()); }
                    $cell.html(highlightHTML($cell.data('origHtml'), regex));
                });
            }
        });
    });
    $('#clearSearch').on('click', () => {
        $('#globalSearch').val('').trigger('input').focus();
        $('#dataTable tbody tr').find('td').each(function() {
            const $cell = $(this);
            if ($cell.data('origHtml')) {
                $cell.html($cell.data('origHtml'));
                $cell.removeData('origHtml');
            }
        });
    });
    
    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3;
        const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- LOGICA PAGINA IMPORTAZIONE (COMPLETA E CORRETTA) ---
    const uploaderCard = document.getElementById('uploaderCard');
    if (uploaderCard) {
        const progressCard = document.getElementById('progressCard'),
              zipFileInput = document.getElementById('zipfile'),
              dropZone = document.getElementById('drop-zone'),
              fileInfo = document.getElementById('fileInfo'),
              fileNameDisplay = document.getElementById('fileName'),
              uploadButton = document.getElementById('uploadButton'),
              progressText = document.getElementById('progress-text'),
              logContainer = document.getElementById('logContainer'),
              finalActions = document.getElementById('finalActions'),
              progressBar

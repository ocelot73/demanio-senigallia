// /public/assets/js/app.js

// Config JS iniettata dal layout: usa window.APP_URL se presente, altrimenti fallback relativo.
const APP_BASE = (typeof window !== 'undefined' && typeof window.APP_URL !== 'undefined') ? window.APP_URL : '.';
const FIELD_HELP = (typeof window !== 'undefined' && typeof window.FIELD_HELP !== 'undefined') ? window.FIELD_HELP : {};
const hiddenColumns = (typeof window !== 'undefined' && typeof window.hiddenColumns !== 'undefined') ? window.hiddenColumns : [];

// --- Utility AJAX ---
function toggleColumn(columnName) {
    $.post(APP_BASE + '/index.php', { action: 'toggle_column', toggle_column: columnName }, function (r) {
        if (r && r.success) location.reload();
    }, 'json');
}
function applyFilter(columnName, value) {
    $.post(APP_BASE + '/index.php', { action: 'set_filter', set_filter: columnName, filter_value: value }, function (r) {
        if (r && r.success) location.reload();
    }, 'json');
}

$(document).ready(function () {

    // =========================
    //  UI: Sidebar e Tema
    // =========================
    $('#sidebar-toggle').on('click', function () {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
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

    $('.submenu-toggle').on('click', function (e) {
        e.preventDefault();
        $(this).parent('.has-submenu').toggleClass('open');
    });

    // =========================
    //  Tabella: Colonne Nascoste
    // =========================
    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns && hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => {
                const $tag = $(`<span class="hidden-column-tag"></span>`).text(c + ' ');
                const $btn = $('<button title="Mostra colonna">✕</button>').on('click', () => toggleColumn(c));
                $tag.append($btn);
                list.append($tag);
            });
        } else {
            bar.hide();
        }
    }
    updateHiddenColumnsDisplay();

    // Toggle colonna dal pulsante nella testata
    $(document).on('click', '.toggle-btn', function () {
        const col = $(this).data('column');
        if (col) toggleColumn(col);
    });

    // =========================
    //  Filtri per colonna
    // =========================
    $('.filter-input').on('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilter($(this).data('column'), $(this).val());
        }
    });

    // =========================
    //  Ricerca Globale + Evidenziazione
    // =========================
    function escapeRegExp(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    function highlightHTML(html, regex) {
        if (!html) return '';
        // Spezza il contenuto sui tag per non rompere la struttura
        return html.split(/(<[^>]*>)/g).map(part => {
            return part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>');
        }).join('');
    }
    $('#globalSearch').on('input', function () {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);
        const hasTable = $('#dataTable').length > 0;
        if (!hasTable) return;

        // Ripristina HTML originale prima di qualunque nuova evidenziazione
        $('#dataTable tbody tr td').each(function () {
            const $cell = $(this);
            const orig = $cell.data('origHtml');
            if (orig) {
                $cell.html(orig);
                $cell.removeData('origHtml');
            }
        });

        if (query.length === 0) {
            $('#dataTable tbody tr').show();
            return;
        }

        const regex = new RegExp(escapeRegExp(query), 'gi');
        $('#dataTable tbody tr').each(function () {
            const $row = $(this);
            const rowText = $row.text();
            const match = regex.test(rowText);
            $row.toggle(match);
            if (match) {
                $row.find('td').each(function () {
                    const $cell = $(this);
                    if (!$cell.data('origHtml')) $cell.data('origHtml', $cell.html());
                    $cell.html(highlightHTML($cell.data('origHtml'), new RegExp(escapeRegExp(query), 'gi')));
                });
            }
        });
    });

    $('#clearSearch').on('click', function () {
        $('#globalSearch').val('').trigger('input').focus();
    });

    // =========================
    //  Larghezza colonne
    // =========================
    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function () {
        currentWidthMode = (currentWidthMode + 1) % 3;
        const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // =========================
    //  Import ZIP (drag&drop + browse + SSE)
    // =========================
    const uploaderCard = document.getElementById('uploaderCard');
    if (uploaderCard) {
        const progressCard   = document.getElementById('progressCard'),
              zipFileInput    = document.getElementById('zipfile'),
              dropZone        = document.getElementById('drop-zone'),
              fileInfo        = document.getElementById('fileInfo'),
              fileNameDisplay = document.getElementById('fileName'),
              uploadButton    = document.getElementById('uploadButton'),
              progressText    = document.getElementById('progress-text'),
              logContainer    = document.getElementById('logContainer'),
              finalActions    = document.getElementById('finalActions'),
              progressBar     = document.getElementById('progress-bar');
        let eventSource = null;

        const setupUploader = () => {
            const browseLink = dropZone.querySelector('.browse-link');
            dropZone.onclick = (e) => {
                if (e.target === browseLink) {
                    e.preventDefault();
                }
                zipFileInput.click();
            };
            dropZone.ondragover = (e) => { e.preventDefault(); dropZone.classList.add('dragover'); };
            dropZone.ondragleave = () => dropZone.classList.remove('dragover');
            dropZone.ondrop = (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                const f = e.dataTransfer.files && e.dataTransfer.files[0];
                if (f && (f.type.includes('zip') || f.name.endsWith('.zip'))) {
                    zipFileInput.files = e.dataTransfer.files;
                    handleFileSelection();
                } else {
                    alert('Per favore, seleziona un file in formato ZIP.');
                }
            };
            zipFileInput.onchange = handleFileSelection;
        };

        function handleFileSelection() {
            if (zipFileInput.files.length) {
                fileNameDisplay.textContent = zipFileInput.files[0].name;
                fileInfo.style.display = 'inline-flex';
                uploadButton.removeAttribute('disabled');
            } else {
                fileNameDisplay.textContent = '';
                fileInfo.style.display = 'none';
                uploadButton.setAttribute('disabled', 'disabled');
            }
        }

        function startSseProcessing(processId) {
            const url = new URL(APP_BASE + '/index.php');
            url.searchParams.set('action', 'process_import');
            url.searchParams.set('id', processId);

            eventSource = new EventSource(url.toString());

            eventSource.addEventListener('log', e => {
                try {
                    const data = JSON.parse(e.data);
                    updateLog(data.status, data.message);
                } catch(_) {}
            });
            eventSource.addEventListener('progress', e => {
                try {
                    const data = JSON.parse(e.data);
                    updateProgress(data.value, data.text);
                } catch(_) {}
            });
            eventSource.addEventListener('close', e => {
                try {
                    const data = JSON.parse(e.data);
                    finishProcess(data.status, data.message);
                } catch(_) { finishProcess('warning', 'Processo terminato.'); }
            });
            eventSource.onerror = () => {
                if (eventSource && eventSource.readyState !== EventSource.CLOSED) {
                    finishProcess('error', 'Connessione con il server interrotta. Ricaricare la pagina.');
                }
            };
        }

        const iconMap = { info: 'fas fa-info-circle', success: 'fas fa-check-circle', warning: 'fas fa-exclamation-triangle', error: 'fas fa-times-circle' };
        function updateLog(status, message) {
            const item = document.createElement('div');
            item.className = `log-item status-${status}`;
            item.innerHTML = `<i class="icon ${iconMap[status] || ''}"></i><span class="message">${message}</span>`;
            logContainer.appendChild(item);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateProgress(value, text) {
            progressBar.style.width = `${Math.min(parseFloat(value) || 0, 100)}%`;
            progressText.textContent = text || '';
        }

        function finishProcess(status, message) {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            updateProgress(100, message || 'Completato');
            if (status === 'error') {
                progressBar.classList.remove('warning');
                progressBar.classList.add('error');
            } else if (status === 'warning') {
                progressBar.classList.remove('error');
                progressBar.classList.add('warning');
            }
            finalActions.style.display = 'block';
        }

        // Submit del form: upload ZIP via AJAX -> avvio SSE
        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            if (!zipFileInput.files.length) {
                alert('Seleziona prima un file ZIP.');
                return;
            }
            uploaderCard.style.display = 'none';
            progressCard.style.display = 'block';
            updateProgress(0, 'Preparazione upload...');
            updateLog('info', 'Caricamento file ZIP in corso...');

            const formData = new FormData();
            formData.append('zipfile', zipFileInput.files[0]);
            formData.append('action', 'import_zip');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', APP_BASE + '/index.php', true);
            xhr.responseType = 'json';
            xhr.onload = function () {
                const res = xhr.response;
                if (xhr.status === 200 && res && res.success) {
                    updateProgress(5, 'Upload completato. Avvio elaborazione...');
                    updateLog('success', 'File caricato correttamente. Processo ID: ' + res.processId);
                    startSseProcessing(res.processId);
                } else {
                    const err = (res && res.error) ? res.error : 'Errore imprevisto durante il caricamento.';
                    updateLog('error', err);
                    finishProcess('error', err);
                }
            };
            xhr.onerror = function () {
                updateLog('error', 'Errore di rete durante il caricamento del file.');
                finishProcess('error', 'Errore di rete.');
            };
            xhr.send(formData);
        });

        setupUploader();
    }
});

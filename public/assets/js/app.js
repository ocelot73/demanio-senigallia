// /public/assets/js/app.js

// Passa le configurazioni da PHP a JavaScript
const APP_URL = (typeof APP_URL !== 'undefined') ? APP_URL : '.';
const FIELD_HELP = (typeof FIELD_HELP !== 'undefined') ? FIELD_HELP : {};
const hiddenColumns = (typeof hiddenColumns !== 'undefined') ? hiddenColumns : [];

// --- Funzioni di Utilità per AJAX ---
function toggleColumn(n) { $.post(APP_URL + '/index.php', { action: 'toggle_column', toggle_column: n }, r => { if(r.success) location.reload(); }, 'json'); }
function applyFilter(n, v) { $.post(window.location.href, { action: 'set_filter', set_filter: n, filter_value: v }, r => { if(r.success) location.reload(); }, 'json'); }


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

    // --- [CORREZIONE] RICERCA GLOBALE CON EVIDENZIAZIONE ---
    function highlightHTML(html, regex){ return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);
            $row.toggle(match);
            if (match && regex) {
                $row.find('td').each(function() {
                    const $cell = $(this);
                    if (!$cell.data('origHtml')) $cell.data('origHtml', $cell.html());
                    $cell.html(highlightHTML($cell.data('origHtml'), regex));
                });
            } else {
                 $row.find('td').each(function() {
                    const $cell = $(this);
                    if ($cell.data('origHtml')) $cell.html($cell.data('origHtml'));
                });
            }
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });


    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3;
        const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- [CORREZIONE] LOGICA PAGINA IMPORTAZIONE ---
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
              progressBar = document.getElementById('progress-bar');
        let eventSource = null;

        const setupUploader = () => {
            // [FIX] Attiva il click sull'input file nascosto
            dropZone.onclick = () => zipFileInput.click();
            dropZone.ondragover = (e) => { e.preventDefault(); dropZone.classList.add('dragover'); };
            dropZone.ondragleave = () => dropZone.classList.remove('dragover');
            dropZone.ondrop = (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                if (e.dataTransfer.files.length && (e.dataTransfer.files[0].type.includes('zip') || e.dataTransfer.files[0].name.endsWith('.zip'))) {
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
                fileInfo.style.display = 'flex';
                uploadButton.disabled = false;
            }
        }

        document.getElementById('uploadForm').onsubmit = (e) => {
            e.preventDefault();
            if (!zipFileInput.files.length) return;

            uploaderCard.style.display = 'none';
            progressCard.style.display = 'block';
            updateLog('info', 'Preparazione e avvio caricamento file...');

            const formData = new FormData();
            formData.append('zipfile', zipFileInput.files[0]);
            formData.append('action', 'import_zip'); // Azione per il gestore AJAX

            $.ajax({
                url: APP_URL + '/index.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 5;
                            updateProgress(percentComplete, `Fase 1/5: Caricamento file... ${Math.round((evt.loaded / evt.total) * 100)}%`);
                        }
                    }, false);
                    return xhr;
                },
                success: function(result) {
                    if (result.success) {
                        startSseProcessing(result.processId);
                    } else {
                        finishProcess('error', result.error || 'Errore sconosciuto durante il caricamento');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    finishProcess('error', `Errore del server: ${jqXHR.status} ${errorThrown}`);
                }
            });
        };

        function startSseProcessing(processId) {
            const url = new URL(APP_URL + '/index.php');
            url.searchParams.set('action', 'process_import');
            url.searchParams.set('id', processId);
            eventSource = new EventSource(url.toString());

            eventSource.addEventListener('log', e => {
                const data = JSON.parse(e.data);
                updateLog(data.status, data.message);
            });

            eventSource.addEventListener('progress', e => {
                const data = JSON.parse(e.data);
                updateProgress(data.value, data.text);
            });

            eventSource.addEventListener('close', e => {
                const data = JSON.parse(e.data);
                finishProcess(data.status, data.message);
            });

            eventSource.onerror = (e) => {
                if (eventSource.readyState === EventSource.CLOSED) {
                    console.log("Connessione SSE chiusa.");
                } else {
                    finishProcess('error', 'Connessione con il server interrotta.');
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
            progressBar.style.width = `${Math.min(value, 100)}%`;
            progressText.textContent = text;
        }

        function finishProcess(status, message) {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            updateProgress(100, "Completato");
            updateLog(status, `<strong>${message}</strong>`);
            progressBar.classList.remove('error', 'warning');
            if (status === 'error') progressBar.classList.add('error');
            if (status === 'warning') progressBar.classList.add('warning');
            finalActions.style.display = 'block';
        }

        setupUploader();
    }
});

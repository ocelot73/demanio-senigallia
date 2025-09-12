// /public/assets/js/app.js

// Base per le chiamate
const APP_BASE = (typeof window !== 'undefined' && typeof window.APP_URL !== 'undefined') ? window.APP_URL : '.';
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

    // =========================
    //  Gestione barra "colonne nascoste"
    // =========================
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

    // =========================
    //  Ridimensionamento colonne (client-side, salvataggio demandato a futura estensione)
    // =========================
    let isResizing=false, currentTh=null, startX=0, startWidth=0;
    $('.resizer').on('mousedown', function(e){ isResizing=true; currentTh=$(this).closest('th'); startX=e.pageX; startWidth=currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault(); });
    $(document).on('mousemove', function(e){ if (isResizing) { const w=startWidth+(e.pageX-startX); if (w>30) currentTh.width(w); } })
               .on('mouseup', function(){ if(isResizing){ isResizing=false; currentTh=null; $('body').css('cursor',''); } });

    // =========================
    //  Filtri per colonna
    // =========================
    $('.filter-input').on('keypress', function(e){ if (e.key==='Enter'){ e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    // =========================
    //  Ricerca Globale + Evidenziazione (come l’originale)
    // =========================
    function escapeRegExp(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function highlightHTML(html, regex){
        return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join('');
    }

    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);

        // Ripristina HTML originale
        $('#dataTable tbody tr td').each(function () {
            const $cell = $(this);
            const orig = $cell.data('origHtml');
            if (orig) { $cell.html(orig); $cell.removeData('origHtml'); }
        });

        if (query.length === 0) { $('#dataTable tbody tr').show(); return; }

        const regex = new RegExp(escapeRegExp(query), 'gi');
        $('#dataTable tbody tr').each(function () {
            const $row = $(this);
            const match = regex.test($row.text());
            $row.toggle(match);
            if (match) {
                $row.find('td').each(function () {
                    const $cell = $(this);
                    if (!$cell.data('origHtml')) $cell.data('origHtml', $cell.html());
                    $cell.html(highlightHTML($cell.data('origHtml'), regex));
                });
            }
        });
    });
    $('#clearSearch').on('click', function () {
        $('#globalSearch').val('').trigger('input').focus();
    });

    // =========================
    //  Selezione riga (verde)
    // =========================
    $('#dataTable tbody').on('click', 'tr', function() {
        $(this).toggleClass('row-selected');
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

        const handleFileSelection = () => {
            const file = zipFileInput.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
                fileInfo.style.display = 'block';
                uploadButton.disabled = false;
            } else {
                fileInfo.style.display = 'none';
                uploadButton.disabled = true;
            }
        };

        // Click su area = apri file dialog
        dropZone.onclick = () => zipFileInput.click();
        // Drag&drop
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
                alert('Seleziona un file .zip valido.');
            }
        };
        zipFileInput.addEventListener('change', handleFileSelection);

        // Submit con SSE (API già presente lato server)
        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                url: APP_BASE + '/index.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (resp) {
                    if (!resp || !resp.success) {
                        alert(resp && resp.error ? resp.error : 'Errore di caricamento');
                        return;
                    }
                    // Avvia listening SSE
                    $('#uploaderCard').hide();
                    $('#progressCard').show();

                    eventSource = new EventSource(APP_BASE + '/index.php?action=sse_process&id=' + resp.processId);
                    eventSource.addEventListener('progress', (e) => {
                        const d = JSON.parse(e.data);
                        progressBar.style.width = d.value + '%';
                        progressText.textContent = d.text || (d.value + '%');
                    });
                    eventSource.addEventListener('log', (e) => {
                        const d = JSON.parse(e.data);
                        const el = document.createElement('div');
                        el.className = 'log-item status-' + (d.status || 'info');
                        el.innerHTML = '<div class="icon"><i class="fas fa-circle"></i></div><div class="message"></div>';
                        el.querySelector('.message').textContent = d.message || '';
                        logContainer.appendChild(el);
                        logContainer.scrollTop = logContainer.scrollHeight;
                    });
                    eventSource.addEventListener('close', (e) => {
                        const d = JSON.parse(e.data);
                        progressBar.className = '';
                        if (d.status === 'success') progressBar.classList.add('success');
                        else if (d.status === 'warning') progressBar.classList.add('warning');
                        else if (d.status === 'error') progressBar.classList.add('error');
                        finalActions.style.display = 'block';
                        if (eventSource) eventSource.close();
                    });
                },
                error: function (xhr) {
                    alert('Errore: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'richiesta non valida'));
                }
            });
        });
    }
});

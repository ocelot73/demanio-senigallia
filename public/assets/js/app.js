/* /public/assets/js/app.js */
$(document).ready(function () {
    // Sidebar + tema
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

    // Tag barra "colonne nascoste"
    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns && hiddenColumns.length > 0) {
            bar.css('display', 'flex'); list.empty();
            hiddenColumns.forEach(c => {
                const $tag = $(`<span class="hidden-column-tag"></span>`).text(c + ' ');
                const $btn = $('<button title="Mostra colonna">✕</button>').on('click', () => toggleColumn(c));
                $tag.append($btn); list.append($tag);
            });
        } else bar.hide();
    }
    updateHiddenColumnsDisplay();

    // Ridimensionamento colonne
    let isResizing=false, currentTh=null, startX=0, startWidth=0;
    $('.resizer').on('mousedown', function(e){ isResizing=true; currentTh=$(this).closest('th'); startX=e.pageX; startWidth=currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault(); });
    $(document).on('mousemove', function(e){ if (isResizing) { const w=startWidth+(e.pageX-startX); if (w>30) currentTh.width(w); } })
               .on('mouseup', function(){ if(isResizing){ isResizing=false; currentTh=null; $('body').css('cursor',''); } });

    // Filtri per colonna
    $('.filter-input').on('keypress', function(e){ if (e.key==='Enter'){ e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    // Ricerca globale + evidenziazione
    function escapeRegExp(str){ return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function highlightHTML(html, regex){
        if (!html) return '';
        return html.split(/(<[^>]*>)/g).map(p => p.startsWith('<') ? p : p.replace(regex, '<mark class="hl">$&</mark>')).join('');
    }
    $('#globalSearch').on('input', function () {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);

        // ripristino HTML originale
        $('#dataTable tbody tr td').each(function () {
            const $cell = $(this), orig = $cell.data('origHtml');
            if (orig){ $cell.html(orig); $cell.removeData('origHtml'); }
        });

        if (query.length === 0) { $('#dataTable tbody tr').show(); return; }

        const regex = new RegExp(escapeRegExp(query), 'gi');
        $('#dataTable tbody tr').each(function () {
            const $row = $(this); const match = regex.test($row.text());
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
    $('#clearSearch').on('click', function(){ $('#globalSearch').val('').trigger('input').focus(); });

    // Selezione riga = verde
    $('#dataTable tbody').on('click', 'tr', function(){ $(this).toggleClass('row-selected'); });

    // Larghezza colonne (cicla tra standard / content / narrow)
    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function () {
        currentWidthMode = (currentWidthMode + 1) % 3;
        const $t = $('#dataTable'); $t.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $t.addClass('width-mode-content');
        else if (currentWidthMode === 2) $t.addClass('width-mode-narrow');
    });

    // Import ZIP (browse/drag&drop + SSE)
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
            if (zipFileInput.files.length) {
                const f = zipFileInput.files[0];
                fileNameDisplay.textContent = f.name;
                fileInfo.style.display = 'block';
                uploadButton.disabled = false;
            } else {
                fileInfo.style.display = 'none';
                uploadButton.disabled = true;
            }
        };

        const setupUploader = () => {
            const browseLink = dropZone.querySelector('.browse-link');
            dropZone.onclick = (e) => {
                if (e.target === browseLink) e.preventDefault();
                zipFileInput.click();
            };
            dropZone.ondragover  = (e) => { e.preventDefault(); dropZone.classList.add('dragover'); };
            dropZone.ondragleave = () => dropZone.classList.remove('dragover');
            dropZone.ondrop = (e) => {
                e.preventDefault(); dropZone.classList.remove('dragover');
                const f = e.dataTransfer.files && e.dataTransfer.files[0];
                if (f && (f.type.includes('zip') || f.name.endsWith('.zip'))) {
                    zipFileInput.files = e.dataTransfer.files;
                    handleFileSelection();
                } else { alert('Seleziona un file ZIP valido.'); }
            };
            zipFileInput.addEventListener('change', handleFileSelection);
            handleFileSelection();
        };

        function updateProgress(value, text) {
            progressBar.style.width = `${Math.min(parseFloat(value) || 0, 100)}%`;
            progressText.textContent = text || '';
        }
        function updateLog(level, msg) {
            const p = document.createElement('p'); p.className = level; p.textContent = msg;
            logContainer.appendChild(p); logContainer.scrollTop = logContainer.scrollHeight;
        }
        function finishProcess(status, message) {
            if (eventSource) { eventSource.close(); eventSource = null; }
            updateProgress(100, message || 'Completato');
            if (status === 'error')      progressBar.classList.add('error');
            else if (status === 'warning') progressBar.classList.add('warning');
            finalActions.style.display = 'block';
        }

        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            if (!zipFileInput.files.length) { alert('Seleziona prima un file ZIP.'); return; }
            uploaderCard.style.display = 'none'; progressCard.style.display = 'block';
            updateProgress(0, 'Preparazione upload...'); updateLog('info', 'Caricamento file ZIP in corso...');

            const formData = new FormData();
            formData.append('zipfile', zipFileInput.files[0]);
            formData.append('action', 'import_zip');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', APP_URL + '/index.php', true);
            xhr.responseType = 'json';
            xhr.onload = function () {
                const res = xhr.response;
                if (xhr.status === 200 && res && res.success) {
                    updateProgress(5, 'Upload completato. Avvio elaborazione...');
                    updateLog('success', 'File caricato. Processo

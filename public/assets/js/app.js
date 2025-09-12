/* /public/assets/js/app.js */
$(document).ready(function () {

    /* =========================
     * LAYOUT / TEMA / SIDEBAR
     * ========================= */
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

    /* =========================
     * UTILITY
     * ========================= */
    window.toggleColumn = function (col) {
        $.post(window.APP_URL + '/index.php', { action: 'toggle_column', toggle_column: col })
         .done(() => location.reload());
    };
    window.applyFilter = function (col, val) {
        $.post(window.APP_URL + '/index.php', { action: 'set_filter', set_filter: col, filter_value: val })
         .done(() => location.reload());
    };
    
    function escapeHtml(str) { return $('<div/>').text(str ?? '').html(); }

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (typeof hiddenColumns !== 'undefined' && hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => {
                const tag = $(`<span class="hidden-column-tag">${escapeHtml(c)} </span>`);
                const btn = $('<button title="Mostra colonna">✕</button>').on('click', () => toggleColumn(c));
                tag.append(btn);
                list.append(tag);
            });
        } else {
            bar.hide();
        }
    }
    updateHiddenColumnsDisplay();

    // Gestione larghezza colonne
    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3;
        const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    /* =========================
     * RIDIMENSIONE COLONNE
     * ========================= */
    let isResizing=false, currentTh=null, startX=0, startWidth=0;
    $('.resizer').on('mousedown', function(e){ 
        isResizing=true; currentTh=$(this).closest('th'); startX=e.pageX; startWidth=currentTh.width(); 
        $('body').css('cursor', 'col-resize'); e.preventDefault(); 
    });
    $(document).on('mousemove', function(e){ if (isResizing) { const w=startWidth+(e.pageX-startX); if (w>30) currentTh.width(w); } })
               .on('mouseup', function(){ if(isResizing){ isResizing=false; currentTh=null; $('body').css('cursor',''); } });

    /* =========================
     * FILTRI PER COLONNA
     * ========================= */
    $('.filter-input').on('keypress', function(e){ 
        if (e.key === 'Enter'){ e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } 
    });

    /* ===================================================================
     * RICERCA LIVE + EVIDENZIAZIONE (Logica da index.php originale)
     * =================================================================== */
    function highlightHTML(html, regex){ 
        if (!html) return '';
        return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); 
    }
    
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
                    if ($cell.data('origHtml')) {
                        $cell.html($cell.data('origHtml'));
                        $cell.removeData('origHtml');
                    }
                });
            }
        });
    });
    $('#clearSearch').on('click', function(){ $('#globalSearch').val('').trigger('input').focus(); });

    /* =========================
     * SELEZIONE RIGA
     * ========================= */
    $('#dataTable tbody').on('click', 'tr', function(e){
        if ($(e.target).closest('.row-actions').length) return;
        $(this).toggleClass('row-selected');
    });

    /* =========================
     * MODALI: APERTURA/CHIUSURA
     * ========================= */
    function openModal(sel) { const $m=$(sel); $m.addClass('open').css('display','flex'); }
    function closeModal(sel){ const $m=$(sel); $m.removeClass('open'); setTimeout(()=> $m.hide(), 200); }
    $('.modal-close-btn').on('click', function(){ closeModal($(this).closest('.modal-overlay')); });

    /* =========================
     * MODALE DETTAGLI (“lente”)
     * ========================= */
    $('#dataTable').on('click', '.details-btn', function(e){
        e.preventDefault();
        const idf24 = $(this).data('idf24') || $(this).closest('tr').data('idf24');
        if (!idf24) return;

        $.post(window.APP_URL + '/index.php', { action: 'get_sid_details', idf24 })
        .done(function(res){
            if (!res || (!res.idf24 && !res.denominazione)) { alert('Dettagli non disponibili.'); return; }
            $('#modalTitle').text('Dettagli SID');
            $('#modalSubtitle').text((res.denominazione || '') + (res.idf24 ? (' – IDF24: ' + res.idf24) : ''));
            $('#modalNav').html(`
                <button class="nav-button active" data-target="#tab-anagrafica"><i class="fas fa-user"></i> Anagrafica</button>
                <button class="nav-button" data-target="#tab-sid"><i class="fas fa-id-card"></i> Dati SID</button>
            `);
            $('#modalContent').html(`
                <div id="tab-anagrafica" class="record-card">
                    <div><div class="detail-item-label">Denominazione</div><div class="detail-item-value">${escapeHtml(res.denominazione)}</div></div>
                    <div><div class="detail-item-label">Comune</div><div class="detail-item-value">${escapeHtml(res.comune)}</div></div>
                    <div><div class="detail-item-label">Località</div><div class="detail-item-value">${escapeHtml(res.localita)}</div></div>
                </div>
                <div id="tab-sid" class="record-card" style="display:none">
                    <div><div class="detail-item-label">Tipo Atto</div><div class="detail-item-value"><span class="badge badge-blue">${escapeHtml(res.tipo_atto)}</span></div></div>
                    <div><div class="detail-item-label">IDF24</div><div class="detail-item-value">${escapeHtml(res.idf24)}</div></div>
                    <div><div class="detail-item-label">NUM_SID</div><div class="detail-item-value">${escapeHtml(res.num_sid)}</div></div>
                    <div><div class="detail-item-label">Stato</div><div class="detail-item-value"><span class="badge badge-orange">${escapeHtml(res.stato_conc_sid)}</span></div></div>
                </div>
            `);
            openModal('#detailsModal');
        })
        .fail(function(){ alert('Errore nel recupero dei dettagli.'); });
    });

    $('#modalNav').on('click', '.nav-button', function(){
        const $btn = $(this);
        $('#modalNav .nav-button').removeClass('active');
        $btn.addClass('active');
        $('#modalContent > div').hide();
        $($btn.data('target')).show();
    });

    /* =========================
     * MODALE MODIFICA (“matita”)
     * ========================= */
    function renderEditForm(record) {
        if (!record || Object.keys(record).length === 0) return '<p>Nessun dato disponibile per la modifica.</p>';
        let html = '<div id="editGrid">';
        for (const k in record) {
            const v = record[k];
            const readonly = (k === 'idf24');
            html += `
            <div class="edit-field">
              <div class="edit-field-container ${readonly ? 'is-readonly' : ''}">
                 <input class="edit-input" type="text" id="fld_${k.replace(/\W+/g,'_')}" 
                       ${readonly?'readonly':''}
                       value="${escapeHtml(v)}" 
                       placeholder="${v === null ? 'NULL' : ''}">
                <label class="edit-field-label" for="fld_${k.replace(/\W+/g,'_')}">${escapeHtml(k)}</label>
              </div>
            </div>`;
        }
        html += '</div>';
        html += `<p style="margin-top:1rem;font-size:0.9rem;color:var(--color-text-secondary);">
                   Il salvataggio diretto dalla modale non è attivo in questa vista. 
                   Per modifiche, usare la pagina dedicata <a href="#" onclick="document.querySelector('a[title*=\'Modifica la tabella\']').click(); return false;">"Modifica Concessioni"</a>.
                 </p>`;
        return html;
    }

    $('#dataTable').on('click', '.edit-btn', function(e){
        e.preventDefault();
        const idf24 = $(this).data('idf24') || $(this).closest('tr').data('idf24');
        if (!idf24) return;
        $.post(window.APP_URL + '/index.php', { action: 'get_concessione_edit', idf24 })
        .done(function(res){
            $('#editTitle').text('Modifica Concessione');
            $('#editSubtitle').text('IDF24: ' + escapeHtml(idf24));
            $('#editForm').html(renderEditForm(res.record || {}));
            openModal('#editModal');
        })
        .fail(function(){ alert('Impossibile aprire la modale di modifica.'); });
    });

    $('#editSaveContinueBtn, #editSaveExitBtn').on('click', function(){
        alert('Il salvataggio diretto dalla modale non è attivo su questa vista. Usa “Modifica Concessioni”.');
    });
    $('#editCancelBtn').on('click', function(){ closeModal('#editModal'); });

    /* ===================================================================
     * IMPORT ZIP (Logica da index.php originale)
     * =================================================================== */
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
            dropZone.onclick = () => zipFileInput.click();
            dropZone.ondragover  = (e) => { e.preventDefault(); dropZone.classList.add('dragover'); };
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
        
        function updateProgress(value, text) {
            progressBar.style.width = `${Math.min(parseFloat(value) || 0, 100)}%`;
            progressText.textContent = text || '';
        }

        function appendLog(level, msg) {
            const iconMap = { info: 'fas fa-info-circle', success: 'fas fa-check-circle', warning: 'fas fa-exclamation-triangle', error: 'fas fa-times-circle' };
            const p = document.createElement('div');
            p.className = 'log-item status-' + level;
            p.innerHTML = `<i class="icon ${iconMap[level] || ''}"></i><span class="message">${escapeHtml(msg)}</span>`;
            logContainer.appendChild(p);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function finishProcess(status, message) {
            if (eventSource) { eventSource.close(); eventSource = null; }
            updateProgress(100, message || 'Completato');
            if (status === 'error') progressBar.classList.add('error');
            else if (status === 'warning') progressBar.classList.add('warning');
            finalActions.style.display = 'block';
        }

        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            if (!zipFileInput.files.length) { alert('Seleziona prima un file ZIP.'); return; }
            uploaderCard.style.display = 'none';
            progressCard.style.display = 'block';
            updateProgress(0, 'Preparazione upload...');
            appendLog('info', 'Caricamento file ZIP in corso...');

            const formData = new FormData();
            formData.append('zipfile', zipFileInput.files[0]);
            formData.append('action', 'import_zip');

            $.ajax({
                url: window.APP_URL + '/index.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.processId) {
                        updateProgress(5, 'Upload completato. Avvio elaborazione...');
                        appendLog('success', 'File caricato. Processo: ' + res.processId);
                        
                        eventSource = new EventSource(window.APP_URL + '/index.php?action=process_import&id=' + encodeURIComponent(res.processId));
                        eventSource.addEventListener('progress', (ev) => {
                            try { const d = JSON.parse(ev.data); updateProgress(d.value, d.text || ''); } catch(e){}
                        });
                        eventSource.addEventListener('log', (ev) => {
                            try { const d = JSON.parse(ev.data); appendLog(d.status || 'info', d.message || ''); } catch(e){}
                        });
                        eventSource.addEventListener('close', (ev) => {
                            try { const d = JSON.parse(ev.data); finishProcess(d.status || 'success', d.message || 'Completato'); } catch(e){ finishProcess('success'); }
                        });
                        eventSource.onerror = () => { finishProcess('error', 'Connessione SSE interrotta.'); };
                    } else {
                        appendLog('error', res.error || 'Errore durante l’upload.');
                        finishProcess('error', 'Upload fallito');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Errore di rete durante l’upload.';
                    try {
                        const err = JSON.parse(xhr.responseText);
                        if (err.error) errorMsg = err.error;
                    } catch(e){}
                    appendLog('error', errorMsg);
                    finishProcess('error', 'Upload fallito');
                }
            });
        });

        setupUploader();
    }
});

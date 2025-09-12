/* /public/assets/js/app.js */
$(document).ready(function() {

    // --- Config Globale (da PHP) ---
    const FIELD_HELP = window.FIELD_HELP_DATA || {};
    const hiddenColumns = window.hiddenColumnsData || [];

    // --- Gestione UI (Sidebar, Tema) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });
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
        localStorage.setItem('theme', theme);
    }
    setTheme(localStorage.getItem('theme') === 'dark' ? 'dark' : 'light');
    themeToggle.on('click', () => setTheme(document.documentElement.classList.contains('dark-theme') ? 'light' : 'dark'));

    // --- Gestione Tabella ---
    window.toggleColumn = function(n) {
        $.post(window.location.href, { action: 'toggle_column', toggle_column: n }, r => { if(r.success) location.reload(); }, 'json');
    };
    function applyFilter(n, v) {
        $.post(window.location.href, { action: 'set_filter', set_filter: n, filter_value: v }, r => { if(r.success) location.reload(); }, 'json');
    }
    function saveColumnWidths() {
        let w = {};
        $('#dataTable thead th[data-column]').each(function() {
            const n = $(this).data('column');
            if (n) w[n] = $(this).outerWidth();
        });
        $.post(window.location.href, { action: 'save_column_widths', column_widths: w });
    }

    $('#dataTable tbody').on('click', 'tr', function(e) {
        if ($(e.target).is('a, button, .row-actions i, .row-actions')) return;
        $(this).toggleClass('row-selected');
    });

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else {
            bar.hide();
        }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true;
        currentTh = $(this).closest('th');
        startX = e.pageX;
        startWidth = currentTh.width();
        $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) {
        if (isResizing) {
            const w = startWidth + (e.pageX - startX);
            if (w > 30) currentTh.width(w);
        }
    }).on('mouseup', function() {
        if (isResizing) {
            isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths();
        }
    });

    $('.filter-input').on('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); }
    });

    function highlightHTML(html, regex) {
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
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                if (match && regex) $cell.html(highlightHTML($cell.data('origHtml'), regex));
                else $cell.html($cell.data('origHtml'));
            });
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

    // --- Gestione Modali ---
    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    // --- [CODICE CORRETTO] --- Gestori di chiusura Modali
    // Ascolta il click sull'overlay o sui pulsanti con la classe .modal-close-btn
    $('#detailsModal').on('click', function(e) { if (e.target === this || $(e.target).hasClass('modal-close-btn')) closeModal('detailsModal'); });
    $('#detailsModal .modal-container').on('click', e => e.stopPropagation());

    $('#editModal').on('click', function(e) { if (e.target === this || $(e.target).hasClass('modal-close-btn') || e.target.id === 'editCancelBtn') closeModal('editModal'); });
    $('#editModal .modal-container').on('click', e => e.stopPropagation());

    $('#eventDetailsModal').on('click', function(e) { if (e.target === this || $(e.target).hasClass('modal-close-btn')) closeModal('eventDetailsModal'); });
    $('#eventDetailsModal .modal-container').on('click', e => e.stopPropagation());


    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        $.post(window.location.href, { action: 'get_sid_details', idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }

            Object.keys(resp).forEach(k => {
                const it = resp[k];
                const isDisabled = it.count === 0 && !it.error;
                const btn = $(`<button class="nav-button ${isDisabled ? 'disabled' : ''}" data-target="panel-${k}" ${isDisabled ? 'disabled' : ''}></button>`)
                    .html(`<i class="${it.icon}"></i><span>${it.label} (${it.count})</span>`);
                btn.attr('data-comment', (it.comment || ''));
                nav.append(btn);

                if (it.count > 0 && !it.error) {
                    const panel = $(`<div class="detail-panel" id="panel-${k}" style="display:none"></div>`);
                    it.data.forEach(rec => {
                        const card = $('<div class="record-card"></div>');
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border-left': '4px solid var(--color-primary)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${value}</div></div>`));
                            }
                        });
                        if (card.children().length > 0) panel.append(card);
                    });
                    content.append(panel);
                }
            });

            nav.off('click', '.nav-button').on('click', '.nav-button', function() {
                if ($(this).is(':disabled')) return;
                nav.find('.nav-button').removeClass('active'); $(this).addClass('active');
                content.find('.detail-panel').hide(); $('#' + $(this).data('target')).show();
                $('#modalSubtitle').text($(this).data('comment') || '');
            });
            nav.find('.nav-button:not(:disabled)').first().trigger('click');
        }, 'json');
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<div class="edit-grid-loading">Caricamento dati in corso...</div>');
        $('#editAlert').hide();

        $.post(window.location.href, { action: 'get_concessione_edit', idf24: idf24 }, function(r) {
            if (r.error) { $('#editForm').html(`<p class="error-message">${r.error}</p>`); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const grid = $('<div class="edit-grid"></div>'); // Singola griglia come nell'originale

            r.columns.forEach(col => {
                const fieldHtml = buildField(col);
                grid.append(fieldHtml);
            });
            
            form.append(grid);
            
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            // --- [CODICE CORRETTO] --- Visualizzazione data ultimo aggiornamento
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        }, 'json');
    }

    // --- [CODICE CORRETTO] --- Funzione buildField semplificata per stile originale
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');

        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $label = $(`<label for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';

        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''}><option value="">(non impostato)</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="(valore nullo)" ${isReadOnly ? 'readonly' : ''}>`);
            if(hasValue) $input.val(value);
        }

        $field.append($label).append($input);
        return $field;
    }

    function saveEdits(keepOpen) {
        const updates = {};
        $('#editForm .edit-field').each(function() {
            const name = $(this).data('name');
            const original = editOriginalData.values[name] ?? null;
            const $input = $(this).find('.edit-input');
            if ($input.is('[readonly],[disabled]')) return;
            const current = $input.val();
            let originalString = original === null ? '' : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            if (current !== originalString) updates[name] = current;
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        $.post(window.location.href, { action: 'save_concessione_edit', original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                if (keepOpen) openEditModal(updates['idf24'] || editOriginalData.idf24);
                else location.reload();
            } else {
                $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
            }
        }, 'json');
    }

    $('#editSaveContinueBtn').on('click', () => saveEdits(true));
    $('#editSaveExitBtn').on('click', () => saveEdits(false));

    // --- Gestione Help Popups ---
    function buildHelpDot(name, help) {
        const title = help.title || name.replace(/_/g, ' ');
        const content = help.content || '';
        const $dot = $(`<button type="button" class="help-dot" aria-label="Aiuto">?</button>`);
        $dot.on('click', e => { e.preventDefault(); e.stopPropagation(); showHelpPopup($dot, title, `(${name})`, content); });
        return $dot;
    }
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove(); // Rimuovi popup esistenti
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        const pos = $anchor.offset();
        $pop.css({ top: pos.top + $anchor.height() + 5, left: pos.left }).show().addClass('open');
    }
    $(document).on('click', function(e) { if (!$(e.target).closest('.help-pop, .help-dot').length) $('.help-pop').remove(); });
    $(document).on('click', '.help-close', () => $('.help-pop').remove());


    // --- Pagina Importa ---
    const uploaderCard = document.getElementById('uploaderCard');
    if (uploaderCard) {
        const zipFileInput = document.getElementById('zipfile'), dropZone = document.getElementById('drop-zone');
        dropZone.onclick = () => zipFileInput.click();
        // ... (resto della logica di importazione invariata) ...
    }
});

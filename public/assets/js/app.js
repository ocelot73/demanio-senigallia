/* /public/assets/js/app.js */

$(document).ready(function() {

    // --- Config Globale (da PHP) ---
    const FIELD_HELP = window.FIELD_HELP_DATA || {};
    const hiddenColumns = window.hiddenColumnsData || [];
    const savedColumnWidths = JSON.parse(localStorage.getItem('columnWidths_' + window.location.pathname) || '{}');

    // --- Funzioni di Utilità ---
    function postAction(action, data, callback) {
        let postData = { action: action, ...data };
        $.post(window.APP_URL + '/index.php', postData, callback || function(r) {
            if (r.success) {
                // Preserva i parametri GET esistenti durante il reload
                window.location.href = window.location.href;
            } else {
                console.error('Azione fallita:', action, r.error);
                alert('Si è verificato un errore: ' + (r.error || 'Dettagli non disponibili.'));
            }
        }, 'json').fail(function() {
            console.error('Errore di comunicazione con il server per l\'azione:', action);
            alert('Errore di comunicazione con il server.');
        });
    }

    window.toggleColumn = (n) => postAction('toggle_column', { toggle_column: n });
    function applyFilter(n, v) { postAction('set_filter', { set_filter: n, filter_value: v }); }
    function saveColumnWidths() {
        let w = {};
        $('#dataTable thead th[data-column]').each(function() {
            const n = $(this).data('column');
            if (n) w[n] = $(this).outerWidth();
        });
        localStorage.setItem('columnWidths_' + window.location.pathname, JSON.stringify(w));
    }
    function updateColumnOrder() {
        let order = $('#dataTable thead th[data-column]').map(function() {
            return $(this).data('column');
        }).get();
        postAction('save_column_order', { column_order: order }, () => {}); // No reload
    }

    // --- Gestione UI (Sidebar, Tema, Modali) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });
    $('.submenu-toggle').on('click', function(e) { e.preventDefault(); $(this).parent('.has-submenu').toggleClass('open'); });
    
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

    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    $('.modal-overlay').on('click', function(e) { if (e.target === this) closeModal($(this).attr('id')); });
    $('.modal-close-btn').on('click', function() { closeModal($(this).closest('.modal-overlay').attr('id')); });
    $('#editCancelBtn').on('click', function() { closeModal('editModal'); });
    $('.modal-container').on('click', e => e.stopPropagation());


    // --- Gestione Tabella ---
    if ($('#dataTable thead tr').length > 0 && typeof $.ui !== 'undefined' && typeof $.ui.sortable !== 'undefined') {
        $('#dataTable thead tr').sortable({
            items: '> th[data-column]',
            axis: 'x',
            containment: 'parent',
            cursor: 'grabbing',
            helper: 'clone',
            placeholder: 'ui-sortable-placeholder',
            stop: function() { updateColumnOrder(); }
        }).disableSelection();
    }
    
    $('#dataTable thead th[data-column]').each(function() {
        const colName = $(this).data('column');
        if (savedColumnWidths[colName]) {
            $(this).width(savedColumnWidths[colName]);
        }
    });

    $('#dataTable tbody').on('click', 'tr', function(e) { if (!$(e.target).is('a, button, .row-actions i, .row-actions')) $(this).toggleClass('row-selected'); });

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else { bar.hide(); }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true; currentTh = $(this).closest('th'); startX = e.pageX; startWidth = currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) { if (isResizing) { const w = startWidth + (e.pageX - startX); if (w > 30) currentTh.width(w); } })
             .on('mouseup', function() { if (isResizing) { isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths(); } });

    $('.filter-input').on('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    function highlightHTML(html, regex) { return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim(); $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);
            $row.toggle(match);
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                $cell.html((match && regex) ? highlightHTML($cell.data('origHtml'), regex) : $cell.data('origHtml'));
            });
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });

    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3; const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        postAction('get_sid_details', { idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }
            $('#modalSubtitle').text('');

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
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border': '2px solid var(--color-primary)', 'box-shadow': '0 0 8px rgba(59, 130, 246, 0.4)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                let displayValue;
                                const badges_blue = ['oggetto', 'scopi_descrizione', 'superficie_richiesta', 'descrizione'];
                                if (badges_blue.includes(key)) {
                                    displayValue = `<span class="badge badge-blue">${value}</span>`;
                                } else if (key === 'tipo_rimozione') {
                                    if (value === 'Facile rimozione') displayValue = `<span class="badge badge-orange">${value}</span>`;
                                    else if (value === 'Difficile rimozione') displayValue = `<span class="badge badge-purple">${value}</span>`;
                                    else displayValue = value;
                                } else {
                                    displayValue = value;
                                }
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
        });
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        postAction('get_concessione_edit', { idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = { general: { label: 'Dati Principali', fields: [] }, t: { label: 'Turistico-ricreative', fields: [] }, nt: { label: 'NON Turistiche-ricreative', fields: [] }, pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] } };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) groups[prefix].fields.push(fieldHtml);
                else groups.general.fields.push(fieldHtml);
            });

            Object.values(groups).forEach(group => {
                let hasValue = group.fields.some(fieldHtml => {
                    const value = editOriginalData.values[$(fieldHtml).data('name')];
                    return value !== null && String(value).trim() !== '';
                });
                if (hasValue) group.hasActiveFields = true;
            });
            
            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const accordionItem = $(`<div class="accordion-item ${index === 0 ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>').append($('<div class="edit-grid"></div>').append(group.fields));
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });
            
            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        });
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="NULL" ${!hasValue ? 'selected' : ''}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            // CORREZIONE CRITICA: il placeholder deve essere uno spazio vuoto per funzionare con :not(:placeholder-shown)
            // e replicare il comportamento originale senza selettori custom.
            const placeholder = ' ';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            
            // CORREZIONE: La normalizzazione del valore originale per il confronto deve essere identica
            // a quella usata nell'originale. Un booleano 'f' è 'false', non solo la stringa 'f'.
            let originalString = (original === null) ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            
            if (current !== originalString) {
                updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        postAction('save_concessione_edit', { original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    window.location.href = window.location.href; // Reload
                }
            } else {
                $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
            }
        });
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

    function makeDraggable(popup) {
        if (typeof $.ui !== 'undefined' && typeof $.ui.draggable !== 'undefined') {
            popup.draggable({ handle: ".help-title", containment: "window" });
        }
    }
    
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);
        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8, left = dotRect.left + dotRect.width / 2;
        $pop.css({ position: 'fixed', top: `${top}px`, left: `${left}px`, transform: 'translateX(-50%)' });

        setTimeout(() => {
            const popRect = $pop[0].getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth, m = 10;
            if (popRect.height >= vh - (m*2)) top = m; else if (popRect.bottom > vh - m) top = dotRect.top - popRect.height - 8;
            if (top < m) top = m;
            if (popRect.left < m) { left = m; $pop.css({ transform: 'translateX(0)' }); } 
            else if (popRect.right > vw - m) { left = vw - m; $pop.css({ transform: 'translateX(-100%)' }); }
            $pop.css({ top: `${top}px`, left: `${left}px` }).addClass('open');
        }, 10);
    }
    
    $(document).on('click', function(e) { if (!$(e.target).closest('.help-pop, .help-dot').length) $('.help-pop').remove(); });
    $(document).on('click', '.help-close', () => $('.help-pop').remove());
    $(document).on('keydown', function(e) { if(e.key === 'Escape') $('.help-pop').remove(); });

    // --- Pagina Importa ---
    if (document.getElementById('uploaderCard')) {
        const uploaderCard = document.getElementById('uploaderCard'),
              progressCard = document.getElementById('progressCard'),
              zipFileInput = docum            return $(this).data('column');
        }).get();
        postAction('save_column_order', { column_order: order }, () => {}); // No reload
    }

    // --- Gestione UI (Sidebar, Tema, Modali) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });
    $('.submenu-toggle').on('click', function(e) { e.preventDefault(); $(this).parent('.has-submenu').toggleClass('open'); });
    
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

    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    // CORREZIONE: Centralizzazione della logica di chiusura delle modali
    $('.modal-overlay').on('click', function(e) { if (e.target === this) closeModal($(this).attr('id')); });
    $('.modal-close-btn').on('click', function() { closeModal($(this).closest('.modal-overlay').attr('id')); });
    $('#editCancelBtn').on('click', function() { closeModal('editModal'); });
    $('.modal-container').on('click', e => e.stopPropagation());


    // --- Gestione Tabella ---
    if ($('#dataTable thead tr').length > 0 && typeof $.ui !== 'undefined' && typeof $.ui.sortable !== 'undefined') {
        $('#dataTable thead tr').sortable({
            items: '> th[data-column]',
            axis: 'x',
            containment: 'parent',
            cursor: 'grabbing',
            helper: 'clone',
            placeholder: 'ui-sortable-placeholder',
            stop: function() { updateColumnOrder(); }
        }).disableSelection();
    }
    
    $('#dataTable thead th[data-column]').each(function() {
        const colName = $(this).data('column');
        if (savedColumnWidths[colName]) {
            $(this).width(savedColumnWidths[colName]);
        }
    });

    $('#dataTable tbody').on('click', 'tr', function(e) { if (!$(e.target).is('a, button, .row-actions i, .row-actions')) $(this).toggleClass('row-selected'); });

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else { bar.hide(); }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true; currentTh = $(this).closest('th'); startX = e.pageX; startWidth = currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) { if (isResizing) { const w = startWidth + (e.pageX - startX); if (w > 30) currentTh.width(w); } })
             .on('mouseup', function() { if (isResizing) { isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths(); } });

    $('.filter-input').on('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    function highlightHTML(html, regex) { return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim(); $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);
            $row.toggle(match);
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                $cell.html((match && regex) ? highlightHTML($cell.data('origHtml'), regex) : $cell.data('origHtml'));
            });
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });

    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3; const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        postAction('get_sid_details', { idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }
            $('#modalSubtitle').text('');

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
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border': '2px solid var(--color-primary)', 'box-shadow': '0 0 8px rgba(59, 130, 246, 0.4)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                let displayValue;
                                const badges_blue = ['oggetto', 'scopi_descrizione', 'superficie_richiesta', 'descrizione'];
                                if (badges_blue.includes(key)) {
                                    displayValue = `<span class="badge badge-blue">${value}</span>`;
                                } else if (key === 'tipo_rimozione') {
                                    if (value === 'Facile rimozione') displayValue = `<span class="badge badge-orange">${value}</span>`;
                                    else if (value === 'Difficile rimozione') displayValue = `<span class="badge badge-purple">${value}</span>`;
                                    else displayValue = value;
                                } else {
                                    displayValue = value;
                                }
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
        });
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        postAction('get_concessione_edit', { idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = { general: { label: 'Dati Principali', fields: [] }, t: { label: 'Turistico-ricreative', fields: [] }, nt: { label: 'NON Turistiche-ricreative', fields: [] }, pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] } };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) groups[prefix].fields.push(fieldHtml);
                else groups.general.fields.push(fieldHtml);
            });

            Object.values(groups).forEach(group => {
                let hasValue = group.fields.some(fieldHtml => {
                    const value = editOriginalData.values[$(fieldHtml).data('name')];
                    return value !== null && String(value).trim() !== '';
                });
                if (hasValue) group.hasActiveFields = true;
            });
            
            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const accordionItem = $(`<div class="accordion-item ${index === 0 ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>').append($('<div class="edit-grid"></div>').append(group.fields));
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            // Inizializza jQuery UI Draggable sui popup di aiuto
            if (typeof $.ui !== 'undefined' && typeof $.ui.draggable !== 'undefined') {
                 $('.help-pop').draggable({ handle: ".help-title", containment: "window" });
            }

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        });
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            // CORREZIONE: Usa placeholder="NULL" per i campi vuoti per far funzionare correttamente l'etichetta flottante con il CSS corretto.
            const placeholder = (value === null) ? 'NULL' : '';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = (original === null) ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            
            if (current !== originalString) {
                updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        postAction('save_concessione_edit', { original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    window.location.href = window.location.href; // Reload
                }
            } else {
                $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
            }
        });
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

    function makeDraggable(popup) {
        // Usa jQuery UI se disponibile per una migliore esperienza
        if (typeof $.ui !== 'undefined' && typeof $.ui.draggable !== 'undefined') {
            popup.draggable({ handle: ".help-title", containment: "window" });
            return;
        }
        // Fallback a JS nativo
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;
        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');
            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin; if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            }).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }
    
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-    // --- Gestione UI (Sidebar, Tema, Modali) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });
    $('.submenu-toggle').on('click', function(e) { e.preventDefault(); $(this).parent('.has-submenu').toggleClass('open'); });
    
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

    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    // CORREZIONE CRITICA: La logica di chiusura era errata.
    // L'evento click deve essere associato a ogni elemento che può chiudere la modale.
    $('.modal-overlay').on('click', function(e) { if (e.target === this) closeModal($(this).attr('id')); });
    $('.modal-close-btn').on('click', function() { closeModal($(this).closest('.modal-overlay').attr('id')); });
    $('#editCancelBtn').on('click', function() { closeModal('editModal'); });
    
    $('.modal-container').on('click', e => e.stopPropagation());

    // --- Gestione Tabella ---
    if ($('#dataTable thead tr').length > 0 && typeof $.ui !== 'undefined' && typeof $.ui.sortable !== 'undefined') {
        $('#dataTable thead tr').sortable({
            items: '> th[data-column]',
            axis: 'x',
            containment: 'parent',
            cursor: 'grabbing',
            helper: 'clone',
            stop: function() { updateColumnOrder(); }
        }).disableSelection();
    }
    
    $('#dataTable thead th[data-column]').each(function() {
        const colName = $(this).data('column');
        if (savedColumnWidths[colName]) {
            $(this).width(savedColumnWidths[colName]);
        }
    });

    $('#dataTable tbody').on('click', 'tr', function(e) { if (!$(e.target).is('a, button, .row-actions i, .row-actions')) $(this).toggleClass('row-selected'); });

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else { bar.hide(); }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true; currentTh = $(this).closest('th'); startX = e.pageX; startWidth = currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) { if (isResizing) { const w = startWidth + (e.pageX - startX); if (w > 30) currentTh.width(w); } })
             .on('mouseup', function() { if (isResizing) { isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths(); } });

    $('.filter-input').on('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    function highlightHTML(html, regex) { return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim(); $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);
            $row.toggle(match);
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                $cell.html((match && regex) ? highlightHTML($cell.data('origHtml'), regex) : $cell.data('origHtml'));
            });
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });

    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3; const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        postAction('get_sid_details', { idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }
            $('#modalSubtitle').text('');

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
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border': '2px solid var(--color-primary)', 'box-shadow': '0 0 8px rgba(59, 130, 246, 0.4)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                let displayValue;
                                const badges_blue = ['oggetto', 'scopi_descrizione', 'superficie_richiesta', 'descrizione'];
                                if (badges_blue.includes(key)) {
                                    displayValue = `<span class="badge badge-blue">${value}</span>`;
                                } else if (key === 'tipo_rimozione') {
                                    if (value === 'Facile rimozione') displayValue = `<span class="badge badge-orange">${value}</span>`;
                                    else if (value === 'Difficile rimozione') displayValue = `<span class="badge badge-purple">${value}</span>`;
                                    else displayValue = value;
                                } else {
                                    displayValue = value;
                                }
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
        });
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        postAction('get_concessione_edit', { idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = { general: { label: 'Dati Principali', fields: [] }, t: { label: 'Turistico-ricreative', fields: [] }, nt: { label: 'NON Turistiche-ricreative', fields: [] }, pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] } };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) groups[prefix].fields.push(fieldHtml);
                else groups.general.fields.push(fieldHtml);
            });

            Object.values(groups).forEach(group => {
                let hasValue = group.fields.some(fieldHtml => {
                    const value = editOriginalData.values[$(fieldHtml).data('name')];
                    return value !== null && String(value).trim() !== '';
                });
                if (hasValue) group.hasActiveFields = true;
            });
            
            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const accordionItem = $(`<div class="accordion-item ${index === 0 ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>').append($('<div class="edit-grid"></div>').append(group.fields));
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        });
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            // CORREZIONE CRITICA: usa placeholder="NULL" per i campi vuoti
            const placeholder = (value === null) ? 'NULL' : '';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = (original === null) ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            
            if (current !== originalString) {
                updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        postAction('save_concessione_edit', { original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    location.reload();
                }
            } else {
                $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
            }
        });
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

    function makeDraggable(popup) {
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;
        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');
            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin; if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            }).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }
    
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);
        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8, left = dotRect.left + dotRect.width / 2;
        $pop.css({ position: 'fixed', top: `${top}px`, left: `${left}px`, transform: 'translateX(-50%)' });

        setTimeout(() => {
            const popRect = $pop[0].getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth, m = 10;
            if (popRect.height >= vh - (m*2)) top = m; else if (popRect.bottom > vh - m) top = dotRect.top - popRect.height - 8;
            if (top < m) top = m;
            if (popRect.left < m) { left = m; $pop.css({ transform: 'translateX(0)' }); } 
            else if (popRect.right > vw - m) { left = vw - m; $pop.css({ transform: 'translateX(-100%)' }); }
            $pop.css({ top: `${top}px`, left: `${left}px` }).addClass('open');
               body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });
    $('.submenu-toggle').on('click', function(e) { e.preventDefault(); $(this).parent('.has-submenu').toggleClass('open'); });
    
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

    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    $('.modal-overlay').on('click', function(e) { if (e.target === this) closeModal($(this).attr('id')); });
    $('.modal-close-btn, #editCancelBtn').on('click', function() { closeModal($(this).closest('.modal-overlay').attr('id')); });
    $('.modal-container').on('click', e => e.stopPropagation());

    // --- Gestione Tabella ---
    if ($('#dataTable thead tr').length > 0 && typeof $.ui !== 'undefined' && typeof $.ui.sortable !== 'undefined') {
        $('#dataTable thead tr').sortable({
            items: '> th[data-column]',
            axis: 'x',
            containment: 'parent',
            cursor: 'grabbing',
            helper: 'clone',
            stop: function() { updateColumnOrder(); }
        }).disableSelection();
    }
    
    $('#dataTable thead th[data-column]').each(function() {
        const colName = $(this).data('column');
        if (savedColumnWidths[colName]) {
            $(this).width(savedColumnWidths[colName]);
        }
    });

    $('#dataTable tbody').on('click', 'tr', function(e) { if (!$(e.target).is('a, button, .row-actions i, .row-actions')) $(this).toggleClass('row-selected'); });

    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else { bar.hide(); }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true; currentTh = $(this).closest('th'); startX = e.pageX; startWidth = currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) { if (isResizing) { const w = startWidth + (e.pageX - startX); if (w > 30) currentTh.width(w); } })
             .on('mouseup', function() { if (isResizing) { isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths(); } });

    $('.filter-input').on('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });

    function highlightHTML(html, regex) { return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim(); $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);
            $row.toggle(match);
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                $cell.html((match && regex) ? highlightHTML($cell.data('origHtml'), regex) : $cell.data('origHtml'));
            });
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });

    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3; const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });

    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        postAction('get_sid_details', { idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }
            $('#modalSubtitle').text('');

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
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border': '2px solid var(--color-primary)', 'box-shadow': '0 0 8px rgba(59, 130, 246, 0.4)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                let displayValue;
                                const badges_blue = ['oggetto', 'scopi_descrizione', 'superficie_richiesta', 'descrizione'];
                                if (badges_blue.includes(key)) {
                                    displayValue = `<span class="badge badge-blue">${value}</span>`;
                                } else if (key === 'tipo_rimozione') {
                                    if (value === 'Facile rimozione') displayValue = `<span class="badge badge-orange">${value}</span>`;
                                    else if (value === 'Difficile rimozione') displayValue = `<span class="badge badge-purple">${value}</span>`;
                                    else displayValue = value;
                                } else {
                                    displayValue = value;
                                }
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
        });
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        postAction('get_concessione_edit', { idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = { general: { label: 'Dati Principali', fields: [] }, t: { label: 'Turistico-ricreative', fields: [] }, nt: { label: 'NON Turistiche-ricreative', fields: [] }, pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] } };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) groups[prefix].fields.push(fieldHtml);
                else groups.general.fields.push(fieldHtml);
            });

            Object.values(groups).forEach(group => {
                let hasValue = group.fields.some(fieldHtml => {
                    const value = editOriginalData.values[$(fieldHtml).data('name')];
                    return value !== null && String(value).trim() !== '';
                });
                if (hasValue) group.hasActiveFields = true;
            });
            
            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const accordionItem = $(`<div class="accordion-item ${index === 0 ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>').append($('<div class="edit-grid"></div>').append(group.fields));
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        });
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            const placeholder = (value === null) ? 'NULL' : '';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = (original === null) ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            
            if (current !== originalString) {
                updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        postAction('save_concessione_edit', { original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    location.reload();
                }
            } else {
                $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
            }
        });
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

    function makeDraggable(popup) {
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;
        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');
            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin; if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            }).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }
    
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);
        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8, left = dotRect.left + dotRect.width / 2;
        $pop.css({ position: 'fixed', top: `${top}px`, left: `${left}px`, transform: 'translateX(-50%)' });

        setTimeout(() => {
            const popRect = $pop[0].getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth, m = 10;
            if (popRect.height >= vh - (m*2)) top = m; else if (popRect.bottom > vh - m) top = dotRect.top - popRect.height - 8;
            if (top < m) top = m;
            if (popRect.left < m) { left = m; $pop.css({ transform: 'translateX(0)' }); } 
            else if (popRect.right > vw - m) { left = vw - m; $pop.css({ transform: 'translateX(-100%)' }); }
            $pop.css({ top: `${top}px`, left: `${left}px` }).addClass('open');
        }, 10);
    }
    
    $(document).on('click', function(e) { if (!$(e.target).closest('.help-pop, .help-dot').length) $('.help-pop').remove(); });
    $(document).on('click', '.help-close', () => $('.help-pop').remove());
    $(document).on('keydown', function(e) { if(e.key === 'Escape') $('.help-pop').remove(); });

    // --- Pagina Importa ---
    if (document.getElementById('uploaderCard')) {
        const uploaderCar        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });

    $('.submenu-toggle').on('click', function(e) { e.preventDefault(); $(this).parent('.has-submenu').toggleClass('open'); });
    
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

    const openModal = (modalId) => $(`#${modalId}`).css('display', 'flex').delay(10).queue(function(next) { $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-pop').remove(); }, 300);
    };

    $('#detailsModal').on('click', function(e) { if (e.target === this || $(e.target).hasClass('modal-close-btn')) closeModal('detailsModal'); });
    $('#editModal').on('click', function(e) { if (e.target === this || $(e.target).hasClass('modal-close-btn') || e.target.id === 'editCancelBtn') closeModal('editModal'); });
    $('.modal-container').on('click', e => e.stopPropagation());

    // --- Gestione Tabella ---
    // REPLICA 1:1 - Colonne Draggable
    if ($('#dataTable thead tr').length > 0) {
        $('#dataTable thead tr').sortable({
            items: '> th[data-column]',
            axis: 'x',
            containment: 'parent',
            cursor: 'grabbing',
            helper: 'clone',
            stop: function() { updateColumnOrder(); }
        }).disableSelection();
    }
    
    // REPLICA 1:1 - Applicazione larghezze colonne salvate
    $('#dataTable thead th[data-column]').each(function() {
        const colName = $(this).data('column');
        if (savedColumnWidths[colName]) {
            $(this).width(savedColumnWidths[colName]);
        }
    });
    
    $('#dataTable tbody').on('click', 'tr', function(e) { if (!$(e.target).is('a, button, .row-actions i, .row-actions')) $(this).toggleClass('row-selected'); });
    
    function updateHiddenColumnsDisplay() {
        const bar = $('#hiddenColumnsBar'), list = $('#hiddenColumnsList');
        if (hiddenColumns.length > 0) {
            bar.css('display', 'flex');
            list.empty();
            hiddenColumns.forEach(c => list.append($(`<span class="hidden-column-tag">${c} <button onclick="toggleColumn('${c}')" title="Mostra colonna">✕</button></span>`)));
        } else { bar.hide(); }
    }
    updateHiddenColumnsDisplay();

    let isResizing = false, currentTh = null, startX = 0, startWidth = 0;
    $('#dataTable .resizer').on('mousedown', function(e) {
        isResizing = true; currentTh = $(this).closest('th'); startX = e.pageX; startWidth = currentTh.width(); $('body').css('cursor', 'col-resize'); e.preventDefault();
    });
    $(document).on('mousemove', function(e) { if (isResizing) { const w = startWidth + (e.pageX - startX); if (w > 30) currentTh.width(w); } })
             .on('mouseup', function() { if (isResizing) { isResizing = false; currentTh = null; $('body').css('cursor', ''); saveColumnWidths(); } });

    $('.filter-input').on('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyFilter($(this).data('column'), $(this).val()); } });
    
    function highlightHTML(html, regex) { return html.split(/(<[^>]+>)/g).map(part => part.startsWith('<') ? part : part.replace(regex, '<mark class="hl">$&</mark>')).join(''); }
    
    $('#globalSearch').on('input', function() {
        const query = $(this).val().trim(); $('#clearSearch').toggle(query.length > 0);
        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;
        $('#dataTable tbody tr').each(function() {
            const $row = $(this), text = $row.text(), match = !regex || regex.test(text); $row.toggle(match);
            $row.find('td').each(function() {
                const $cell = $(this);
                if (typeof $cell.data('origHtml') === 'undefined') $cell.data('origHtml', $cell.html());
                $cell.html((match && regex) ? highlightHTML($cell.data('origHtml'), regex) : $cell.data('origHtml'));
            });
        });
    });
    $('#clearSearch').on('click', () => { $('#globalSearch').val('').trigger('input').focus(); });

    let currentWidthMode = 0;
    $('#toggle-col-width').on('click', function() {
        currentWidthMode = (currentWidthMode + 1) % 3; const $table = $('#dataTable');
        $table.removeClass('width-mode-content width-mode-narrow');
        if (currentWidthMode === 1) $table.addClass('width-mode-content');
        else if (currentWidthMode === 2) $table.addClass('width-mode-narrow');
    });
    
    // --- LOGICA MODALE DETTAGLI (LENTE) ---
    $('#dataTable tbody').on('click', '.details-btn', function(e) { e.preventDefault(); e.stopPropagation(); openDetailsModal($(this).closest('tr').data('idf24')); });
    $('#dataTable tbody').on('dblclick', 'tr', function() { openDetailsModal($(this).data('idf24')); });

    function openDetailsModal(idf24) {
        if (!idf24) return;
        const nav = $('#modalNav'), content = $('#modalContent');
        openModal('detailsModal');
        nav.empty().html('<p>Caricamento...</p>'); content.html('');
        $('#modalTitle').text('Dettagli SID - ID Concessione: ' + idf24);
        
        postAction('get_sid_details', { idf24: idf24 }, function(resp) {
            nav.empty(); content.empty();
            if (resp.error) { content.html(`<p class="error-message">${resp.error}</p>`); return; }
            
            // REPLICA 1:1 - Il sottotitolo viene impostato al click sul pulsante
            $('#modalSubtitle').text('');

            // Itera sulla risposta (che è già l'array/oggetto corretto)
            Object.keys(resp).forEach(k => {
                const it = resp[k];
                const isDisabled = it.count === 0 && !it.error;
                const btn = $(`<button class="nav-button ${isDisabled ? 'disabled' : ''}" data-target="panel-${k}" ${isDisabled ? 'disabled' : ''}></button>`)
                    .html(`<i class="${it.icon}"></i><span>${it.label} (${it.count})</span>`);
                btn.attr('data-comment', (it.comment || '')); // Salva il commento
                nav.append(btn);

                if (it.count > 0 && !it.error) {
                    const panel = $(`<div class="detail-panel" id="panel-${k}" style="display:none"></div>`);
                    it.data.forEach(rec => {
                        const card = $('<div class="record-card"></div>');
                        if(rec['tipo_oggetto'] === 'Zona Demaniale (ZD)') card.css({'border': '2px solid var(--color-primary)', 'box-shadow': '0 0 8px rgba(59, 130, 246, 0.4)'});
                        Object.entries(rec).forEach(([key, value]) => {
                            if (value !== null && String(value).trim() !== '') {
                                let displayValue;
                                const badges_blue = ['oggetto', 'scopi_descrizione', 'superficie_richiesta', 'descrizione'];
                                if (badges_blue.includes(key)) displayValue = `<span class="badge badge-blue">${value}</span>`;
                                else if (key === 'tipo_rimozione') {
                                    if (value === 'Facile rimozione') displayValue = `<span class="badge badge-orange">${value}</span>`;
                                    else if (value === 'Difficile rimozione') displayValue = `<span class="badge badge-purple">${value}</span>`;
                                    else displayValue = value;
                                } else displayValue = value;
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
                // REPLICA 1:1 - Imposta il sottotitolo con il commento della vista attiva
                $('#modalSubtitle').text($(this).data('comment') || '');
            });
            nav.find('.nav-button:not(:disabled)').first().trigger('click');
        });
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        postAction('get_concessione_edit', { idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = { general: { label: 'Dati Principali', fields: [] }, t: { label: 'Turistico-ricreative', fields: [] }, nt: { label: 'NON Turistiche-ricreative', fields: [] }, pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] } };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) groups[prefix].fields.push(fieldHtml);
                else groups.general.fields.push(fieldHtml);
            });

            Object.values(groups).forEach(group => {
                let hasValue = group.fields.some(fieldHtml => {
                    const value = editOriginalData.values[$(fieldHtml).data('name')];
                    return value !== null && String(value).trim() !== '';
                });
                if (hasValue) group.hasActiveFields = true;
            });

            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const accordionItem = $(`<div class="accordion-item ${index === 0 ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>').append($('<div class="edit-grid"></div>').append(group.fields));
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            // REPLICA 1:1 - Mostra la data dell'ultima modifica
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        });
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            const placeholder = (value === null) ? 'NULL' : ' ';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = (original === null) ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));
            if (current !== originalString) updates[name] = current;
        });

        if (Object.keys(updates).length === 0) { if (!keepOpen) closeModal('editModal'); return; }

        postAction('save_concessione_edit', { original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) openEditModal(newIdf24);
                else location.reload();
            } else $('#editAlert').text(r.error || 'Errore durante il salvataggio.').show();
        });
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
    function makeDraggable(popup) {
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;
        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');
            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin; if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            }).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }
    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);
        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8, left = dotRect.left + dotRect.width / 2;
        $pop.css({ position: 'fixed', top: `${top}px`, left: `${left}px`, transform: 'translateX(-50%)' });
        setTimeout(() => {
            const popRect = $pop[0].getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth, m = 10;
            if (popRect.height >= vh - (m*2)) top = m; else if (popRect.bottom > vh - m) top = dotRect.top - popRect.height - 8;
            if (top < m) top = m;
            if (popRect.left < m) { left = m; $pop.css({ transform: 'translateX(0)' }); } 
            else if (popRect.right > vw - m) { left = vw - m; $pop.css({ transform: 'translateX(-100%)' }); }
            $pop.css({ top: `${top}px`, left: `${left}px` }).addClass('open');
        }, 10);
    }
    $(document).on('click', function(e) { if (!$(e.target).closest('.help-pop, .help-dot').length) $('.help-pop').remove(); });
    $(document).on('click', '.help-close', () => $('.help-pop').remove());
    $(document).on('keydown', function(e) { if(e.key === 'Escape') $('.help-pop').remove(); });

                                   } else {
                                    displayValue = value;
                                }
                                card.append($(`<div class="detail-item"><div class="detail-item-label">${key.replace(/_/g, ' ')}</div><div class="detail-item-value">${displayValue}</div></div>`));
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
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        $.post(window.APP_URL + '/index.php', { action: 'get_concessione_edit', idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = {
                general: { label: 'Dati Principali', fields: [] },
                t: { label: 'Turistico-ricreative', fields: [] },
                nt: { label: 'NON Turistiche-ricreative', fields: [] },
                pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] }
            };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) {
                    groups[prefix].fields.push(fieldHtml);
                } else {
                    groups.general.fields.push(fieldHtml);
                }
            });

            Object.values(groups).forEach(group => {
                let hasValue = false;
                for (const fieldHtml of group.fields) {
                    const fieldName = $(fieldHtml).data('name');
                    const value = editOriginalData.values[fieldName];
                    if (value !== null && String(value).trim() !== '') {
                        hasValue = true; break;
                    }
                }
                if (hasValue) group.hasActiveFields = true;
            });

            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const isOpen = index === 0;
                    const accordionItem = $(`<div class="accordion-item ${isOpen ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>');
                    const grid = $('<div class="edit-grid"></div>').append(group.fields);
                    accordionContent.append(grid);
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        }, 'json');
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        
        // CORREZIONE: Aggiunge l'icona '?' se la configurazione HELP esiste per il campo
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            // CORREZIONE: Imposta placeholder='NULL' per i campi vuoti per far funzionare l'etichetta flottante
            const placeholder = (value === null) ? 'NULL' : '';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = original === null ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));

            if (current !== originalString) {
                 updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        $.post(window.APP_URL + '/index.php', { action:'save_concessione_edit', original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    location.reload();
                }
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
    
    function makeDraggable(popup) {
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;

        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');

            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin;
                    if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            });
            $(document).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }

    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);

        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8;
        let left = dotRect.left + dotRect.width / 2;
        $popclick');
        }, 'json');
    }

    // --- LOGICA MODALE MODIFICA (MATITA) ---
    let editOriginalData = {};
    $('#dataTable tbody').on('click', '.edit-btn', function(e) { e.preventDefault(); e.stopPropagation(); openEditModal($(this).closest('tr').data('idf24')); });

    function openEditModal(idf24) {
        if (!idf24) return;
        openModal('editModal');
        $('#editForm').html('<p style="text-align:center; padding: 2rem;">Caricamento dati in corso...</p>');
        $('#editAlert').hide();

        $.post(window.location.href, { action: 'get_concessione_edit', idf24: idf24 }, function(r) {
            if (r.error) { $('#editAlert').text(r.error).show(); return; }
            editOriginalData = r;
            const form = $('#editForm').empty();
            const groups = {
                general: { label: 'Dati Principali', fields: [] },
                t: { label: 'Turistico-ricreative', fields: [] },
                nt: { label: 'NON Turistiche-ricreative', fields: [] },
                pac: { label: 'Pesca Acquacoltura Cantieristica', fields: [] }
            };

            r.columns.forEach(col => {
                const prefix = col.name.substring(0, col.name.indexOf('_'));
                const fieldHtml = buildField(col);
                if (['t', 'nt', 'pac'].includes(prefix)) {
                    groups[prefix].fields.push(fieldHtml);
                } else {
                    groups.general.fields.push(fieldHtml);
                }
            });

            Object.values(groups).forEach(group => {
                let hasValue = false;
                for (const fieldHtml of group.fields) {
                    const fieldName = $(fieldHtml).data('name');
                    const value = editOriginalData.values[fieldName];
                    if (value !== null && String(value).trim() !== '') {
                        hasValue = true;
                        break;
                    }
                }
                if (hasValue) group.hasActiveFields = true;
            });

            Object.values(groups).forEach((group, index) => {
                if (group.fields.length > 0) {
                    const isOpen = index === 0;
                    const accordionItem = $(`<div class="accordion-item ${isOpen ? 'open' : ''}"></div>`);
                    const accordionHeader = $(`<div class="accordion-header ${group.hasActiveFields ? 'has-active-fields' : ''}">${group.label}</div>`);
                    const accordionContent = $('<div class="accordion-content"></div>');
                    const grid = $('<div class="edit-grid"></div>').append(group.fields);
                    accordionContent.append(grid);
                    accordionItem.append(accordionHeader).append(accordionContent);
                    form.append(accordionItem);
                }
            });

            $('.accordion-header').on('click', function() { $(this).parent('.accordion-item').toggleClass('open'); });
            $('#editTitle').text('Modifica Concessione - ID Concessione: ' + r.idf24);
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));
        }, 'json');
    }
    
    function buildField(col) {
        const name = col.name, ui = col.ui_type, value = editOriginalData.values[name], help = FIELD_HELP[name];
        const isReadOnly = name === 'id' || name === 'geom';
        let displayLabel = help?.label || name.replace(/_/g, ' ');
        const $field = $(`<div class="edit-field" data-name="${name}"></div>`);
        const $container = $(`<div class="edit-field-container ${isReadOnly ? 'is-readonly' : ''}"></div>`);
        const $label = $(`<label class="edit-field-label" for="edit-field-${name}">${displayLabel}</label>`);
        if (help) $label.append(buildHelpDot(name, help));
        
        let $input;
        const hasValue = value !== null && String(value).trim() !== '';
        if (ui === 'boolean') {
            $input = $(`<select class="edit-input" id="edit-field-${name}" ${isReadOnly ? 'disabled' : ''} required><option value="" disabled ${hasValue ? '' : 'selected'}>NULL</option><option value="true">Sì</option><option value="false">No</option></select>`);
            if (value === true || String(value).toLowerCase() === 't') $input.val('true');
            else if (value === false || String(value).toLowerCase() === 'f') $input.val('false');
        } else {
            const placeholder = (value === null) ? 'NULL' : ' ';
            $input = $(`<input type="text" class="edit-input" id="edit-field-${name}" placeholder="${placeholder}" ${isReadOnly ? 'readonly' : ''} />`);
            if(value !== null) $input.val(value);
        }

        $container.append($input).append($label);
        $field.append($container);
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
            let originalString = original === null ? null : (typeof original === 'boolean' ? (original ? 'true' : 'false') : String(original));

            if (current !== originalString) {
                 updates[name] = current;
            }
        });

        if (Object.keys(updates).length === 0) {
            if (!keepOpen) closeModal('editModal');
            return;
        }

        $.post(window.location.href, { action:'save_concessione_edit', original_idf24: editOriginalData.idf24, updates: JSON.stringify(updates) }, function(r) {
            if (r.success) {
                const newIdf24 = updates['idf24'] || editOriginalData.idf24;
                if (keepOpen) {
                    openEditModal(newIdf24);
                } else {
                    location.reload();
                }
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
    
    function makeDraggable(popup) {
        const dragHandle = popup.find('.help-title');
        let isDragging = false, initialMouseX, initialMouseY, initialPopupX, initialPopupY;

        dragHandle.on('mousedown', function(e) {
            e.preventDefault(); isDragging = true;
            initialMouseX = e.clientX; initialMouseY = e.clientY;
            const rect = popup[0].getBoundingClientRect();
            initialPopupX = rect.left; initialPopupY = rect.top;
            popup.css('transform', 'none');

            $(document).on('mousemove.drag', function(e) {
                if (isDragging) {
                    const deltaX = e.clientX - initialMouseX, deltaY = e.clientY - initialMouseY;
                    let newX = initialPopupX + deltaX, newY = initialPopupY + deltaY;
                    const popRect = popup[0].getBoundingClientRect(), margin = 5;
                    if (newX < margin) newX = margin;
                    if (newY < margin) newY = margin;
                    if (newX + popRect.width > window.innerWidth - margin) newX = window.innerWidth - popRect.width - margin;
                    if (newY + popRect.height > window.innerHeight - margin) newY = window.innerHeight - popRect.height - margin;
                    popup.css({ left: newX + 'px', top: newY + 'px' });
                }
            });
            $(document).on('mouseup.drag', function() { isDragging = false; $(document).off('mousemove.drag mouseup.drag'); });
        });
    }

    function showHelpPopup($anchor, title, subtitle, content) {
        $('.help-pop').remove();
        const $pop = $(`<div class="help-pop" role="dialog"><button class="help-close">&times;</button><div class="help-title">${title}</div><div class="help-sub">${subtitle}</div><div class="help-content">${content}</div></div>`);
        $('body').append($pop);
        makeDraggable($pop);

        const dotRect = $anchor[0].getBoundingClientRect();
        let top = dotRect.bottom + 8;
        let left = dotRect.left + dotRect.width / 2;
        $pop.css({ position: 'fixed', top: `${top}px`, left: `${left}px`, transform: 'translateX(-50%)' });
        
        setTimeout(() => {
            const popRect = $pop[0].getBoundingClientRect(), viewportHeight = window.innerHeight, viewportWidth = window.innerWidth, margin = 10;
            if (popRect.height >= viewportHeight - (margin * 2)) top = margin;
            else if (popRect.bottom > viewportHeight - margin) top = dotRect.top - popRect.height - 8;
            if (top < margin) top = margin;
            if (popRect.left < margin) {
                left = margin; $pop.css({ transform: 'translateX(0)' });
            } else if (popRect.right > viewportWidth - margin) {
                left = viewportWidth - margin; $pop.css({ transform: 'translateX(-100%)' });
            }
            $pop.css({ top: `${top}px`, left: `${left}px` }).addClass('open');
        }, 10);
    }
    
    $(document).on('click', function(e) { if (!$(e.target).closest('.help-pop, .help-dot').length) $('.help-pop').remove(); });
    $(document).on('click', '.help-close', () => $('.help-pop').remove());
    $(document).on('keydown', function(e) { if(e.key === 'Escape') $('.help-pop').remove(); });

    // --- Pagina Importa ---
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

        const browseLink = dropZone.querySelector('.browse-link');
        dropZone.onclick = (e) => {
            if (e.target === browseLink) e.preventDefault();
            zipFileInput.click();
        };
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

            $.ajax({
                url: window.APP_URL + '/index.php?page=importa',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
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
            const url = new URL(window.APP_URL + '/index.php');
            url.searchParams.set('page', 'importa');
            url.searchParams.set('action', 'process');
            url.searchParams.set('id', processId);
            eventSource = new EventSource(url.toString());
            eventSource.addEventListener('log', e => { const data = JSON.parse(e.data); updateLog(data.status, data.message); });
            eventSource.addEventListener('progress', e => { const data = JSON.parse(e.data); updateProgress(data.value, data.text); });
            eventSource.addEventListener('close', e => { const data = JSON.parse(e.data); finishProcess(data.status, data.message); });
            eventSource.onerror = () => { finishProcess('error', 'Connessione con il server interrotta.'); };
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
            if (eventSource) { eventSource.close(); eventSource = null; }
            updateProgress(100, "Completato");
            updateLog(status, `<strong>${message}</strong>`);
            progressBar.classList.remove('error', 'warning');
            if (status === 'error') progressBar.classList.add('error');
            if (status === 'warning') progressBar.classList.add('warning');
            finalActions.style.display = 'block';
        }
    }
});

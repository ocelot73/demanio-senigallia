/* /public/assets/js/app.js */
$(document).ready(function () {
    // --- Config globale dal backend ---
    const FIELD_HELP = window.FIELD_HELP_DATA || {};
    const hiddenColumns = window.hiddenColumnsData || [];
    let CURRENT_DETAIL_ID = null; // tiene traccia dell'ID aperto nella modale dettaglio

    // -----------------------------
    // Utilità AJAX
    // -----------------------------
    function postAction(action, data, callback, dataType = 'json') {
        const payload = { action, ...data };
        $.post(window.location.href, payload, callback || function (r) {
            if (r && r.success) {
                location.reload();
            } else {
                console.error('Azione fallita:', action, r?.error);
                alert('Si è verificato un errore: ' + (r?.error || 'Dettagli non disponibili.'));
            }
        }, dataType).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Errore AJAX ' + action + ':', jqXHR.responseText);
            alert('Errore di comunicazione con il server: ' + (jqXHR.responseJSON?.error || errorThrown));
        });
    }
    window.toggleColumn = (n) => postAction('toggle_column', { toggle_column: n });
    function applyFilter(n, v) { postAction('set_filter', { set_filter: n, filter_value: v }); }
    function saveColumnWidths() {
        const widths = {};
        $('#dataTable thead th[data-column]').each(function () {
            const n = $(this).data('column');
            if (n && n !== '_azioni') widths[n] = $(this).outerWidth();
        });
        postAction('save_column_widths', { column_widths: widths }, () => {});
    }
    function updateColumnOrder() {
        const order = $('#dataTable thead tr th[data-column]').map(function () {
            const name = $(this).data('column');
            return name && name !== '_azioni' ? name : null;
        }).get().filter(Boolean);
        postAction('save_column_order', { column_order: order }, () => {});
    }

    // -----------------------------
    // Ricerca live con evidenziazione
    // -----------------------------
    function highlightHTML(html, regex) {
        return html.split(/(<[^>]+>)/g).map(part => {
            if (part.startsWith('<')) return part;
            return regex ? part.replace(regex, '<mark class="hl">$&</mark>') : part;
        }).join('');
    }

    $('#globalSearch').on('input', function () {
        const query = $(this).val().trim();
        $('#clearSearch').toggle(query.length > 0);

        const regex = query.length > 0 ? new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi') : null;

        $('#dataTable tbody tr').each(function () {
            const $row = $(this);
            const text = $row.text();
            const match = !regex || regex.test(text);

            $row.toggle(match);
            $row.find('td').each(function () {
                const $cell = $(this);
                // evita di toccare la cella azioni per non interferire con pulsanti
                if ($cell.hasClass('row-actions')) return;

                if (typeof $cell.data('origHtml') === 'undefined') {
                    $cell.data('origHtml', $cell.html());
                }
                const originalHtml = $cell.data('origHtml');
                $cell.html((match && regex) ? highlightHTML(originalHtml, regex) : originalHtml);
            });
        });
    });
    $('#clearSearch').on('click', function () {
        $('#globalSearch').val('').trigger('input');
    });

    // -----------------------------
    // Filtri per colonna (Invio)
    // -----------------------------
    $('.filter-input').on('keydown', function (e) {
        if (e.key === 'Enter') {
            const col = $(this).data('col');
            const val = $(this).val().trim();
            applyFilter(col, val);
        }
    });
    $('#btnResetFilters').on('click', function () {
        $('.filter-input').val('');
        applyFilter('', ''); // reset backend
        setTimeout(() => location.href = window.location.href.replace(/[#?].*$/, ''), 50);
    });

    // -----------------------------
    // Mostra / nascondi colonne
    // -----------------------------
    $('#btnColumns').on('click', function (e) {
        e.stopPropagation();
        $('#columnsMenu').toggle();
        const pos = $(this).offset();
        $('#columnsMenu').css({ left: pos.left, top: pos.top + $(this).outerHeight() + 6 });
    });
    $(document).on('click', function () { $('#columnsMenu').hide(); });
    $('#columnsMenu').on('click', function (e) { e.stopPropagation(); });
    $('.col-toggle').on('change', function () {
        const col = $(this).data('col');
        if (col === '_azioni') return; // la colonna azioni è fissa
        window.toggleColumn(col);
    });

    // -----------------------------
    // Ordinamento colonne (drag & drop header) + salvataggio larghezze
    // -----------------------------
    if ($.fn.sortable) {
        $('#headerRow').sortable({
            items: 'th[data-column]:not([data-fixed="1"])',
            helper: 'clone',
            stop: function () { updateColumnOrder(); }
        });
    }
    let saveTimer = null;
    $('#dataTable thead').on('mousemove', function () {
        if (saveTimer) return;
        saveTimer = setTimeout(() => { saveTimer = null; saveColumnWidths(); }, 2000);
    });
    $(window).on('beforeunload', saveColumnWidths);

    // -----------------------------
    // Modale Dettagli (lente)
    // -----------------------------
    function renderDetailMenu(sections) {
        const $menu = $('#detailMenu').empty();
        Object.keys(sections).forEach(key => {
            const s = sections[key];
            const count = s.count ?? (Array.isArray(s.data) ? s.data.length : 0);
            const label = s.short_label || s.label || key;
            const li = $(`
                <li>
                    <a href="#" class="detail-link" data-key="${key}">
                        <span class="menu-label">${label}</span>
                        <span class="menu-count">${count}</span>
                    </a>
                </li>
            `);
            $menu.append(li);
        });
    }
    function renderDetailBody(section) {
        const $body = $('#detailContent').empty();
        const data = section?.data || [];
        if (!data.length) {
            $body.append('<div class="info-box">Nessun record</div>');
            return;
        }
        const cols = Object.keys(data[0] || {});
        const $tbl = $('<table class="detail-table"><thead><tr></tr></thead><tbody></tbody></table>');
        cols.forEach(c => $tbl.find('thead tr').append(`<th>${c}</th>`));
        data.forEach(r => {
            const tr = $('<tr></tr>');
            cols.forEach(c => tr.append(`<td>${(r[c] ?? '')}</td>`));
            $tbl.find('tbody').append(tr);
        });
        $body.append($tbl);
    }
    function openDetailModal(idf24) {
        postAction('get_sid_details', { idf24 }, function (resp) {
            CURRENT_DETAIL_ID = idf24;
            renderDetailMenu(resp || {});
            const firstKey = resp ? Object.keys(resp)[0] : null;
            if (firstKey) {
                renderDetailBody(resp[firstKey]);
                $('#detailHeader').html(`<h3><i class="fa-solid fa-magnifying-glass"></i> Dettagli — IDF24: <strong>${idf24}</strong></h3>`);
            } else {
                $('#detailHeader').html(`<h3>Dettagli — IDF24: <strong>${idf24}</strong></h3>`);
                $('#detailContent').html('<div class="info-box">Nessun dato disponibile.</div>');
            }
            $('#detailModal').fadeIn(120);
        });
    }
    $('#dataTable').on('dblclick', 'tbody tr', function () {
        const idf24 = $(this).data('idf24');
        if (idf24 != null && idf24 !== '') openDetailModal(idf24);
    });
    $('#dataTable').on('click', '.btn-detail', function (e) {
        e.stopPropagation();
        const idf24 = $(this).closest('tr').data('idf24');
        if (idf24 != null && idf24 !== '') openDetailModal(idf24);
    });
    $('#detailMenu').on('click', '.detail-link', function (e) {
        e.preventDefault();
        const key = $(this).data('key');
        if (!CURRENT_DETAIL_ID) return;
        postAction('get_sid_details', { idf24: CURRENT_DETAIL_ID }, function (resp) {
            renderDetailBody(resp?.[key]);
        });
    });

    // -----------------------------
    // Modale Modifica (matita)
    // -----------------------------
    function section(title, inner) {
        return `
        <div class="accordion-item">
            <button class="accordion-header">${title}<i class="fa-solid fa-chevron-down"></i></button>
            <div class="accordion-panel">${inner}</div>
        </div>`;
    }
    function fieldControl(name, value, meta) {
        const help = FIELD_HELP[name] || {};
        const label = help.label || name;
        const hint = help.hint ? `<small class="form-hint">${help.hint}</small>` : '';
        const type = (meta?.ui_type) || 'text';
        const nullable = !!meta?.nullable;

        let input;
        if (type === 'boolean') {
            const checked = String(value).toLowerCase().match(/^(1|t|true|y|yes|on)$/) ? 'checked' : '';
            input = `<label class="switch"><input type="checkbox" data-name="${name}" ${checked}><span class="slider"></span></label>`;
        } else if (type === 'date') {
            input = `<input type="date" class="input" data-name="${name}" value="${value ? String(value).substring(0,10) : ''}">`;
        } else if (type === 'number') {
            input = `<input type="text" class="input" data-name="${name}" value="${value ?? ''}" inputmode="decimal">`;
        } else {
            input = `<input type="text" class="input" data-name="${name}" value="${value ?? ''}">`;
        }

        return `
        <div class="form-row">
            <label>${label} ${nullable ? '' : '<span class="req">*</span>'}
                <button class="help-icon" data-help="${name}" title="Aiuto" type="button"><i class="fa-regular fa-circle-question"></i></button>
            </label>
            ${input}
            ${hint}
        </div>`;
    }
    function openEditModal(idf24) {
        postAction('get_concessione_edit', { idf24 }, function (r) {
            if (!r || !r.success) {
                alert('Impossibile aprire la modale di modifica.');
                return;
            }
            const cols = r.columns || [];
            const values = r.values || {};
            $('#editSubtitle').text('Ultima modifica: ' + (r.last_operation_time_fmt || 'n/d'));

            const groups = {
                'Dati principali': [],
                'Turistico-ricreative': [],
                'Contabilità': [],
                'Altri campi': []
            };
            cols.forEach(c => {
                const name = c.name;
                const meta = { ui_type: c.ui_type, nullable: c.nullable };
                const ctrl = fieldControl(name, values[name], meta);
                if (/^(idf24|denominazione|partita|codice|pec|verifica)/i.test(name)) groups['Dati principali'].push(ctrl);
                else if (/^(t_|nt_|pac_|stagionalita)/i.test(name)) groups['Turistico-ricreative'].push(ctrl);
                else if (/^(importo|canone|versato|rate|protocollo)/i.test(name)) groups['Contabilità'].push(ctrl);
                else groups['Altri campi'].push(ctrl);
            });

            const html =
                section('Dati Principali', groups['Dati principali'].join('')) +
                section('Turistico-ricreative', groups['Turistico-ricreative'].join('')) +
                section('Contabilità', groups['Contabilità'].join('')) +
                section('Altro', groups['Altri campi'].join(''));
            $('#editAccordion').html(html);

            // Behavior accordion
            $('#editAccordion .accordion-header').off('click').on('click', function () {
                $(this).toggleClass('open').next('.accordion-panel').slideToggle(120);
            });

            // Help popover draggable
            $(document).off('click.help').on('click.help', '.help-icon', function (e) {
                e.preventDefault();
                const key = $(this).data('help');
                const h = FIELD_HELP[key] || {};
                const title = h.title || key;
                const content = h.content || '<p>Nessuna descrizione disponibile.</p>';
                const $pop = $(`
                    <div class="help-popover">
                        <div class="help-popover-header">
                            <strong>${title}</strong>
                            <button class="help-close" type="button">&times;</button>
                        </div>
                        <div class="help-popover-body">${content}</div>
                    </div>
                `).appendTo('body');
                $pop.draggable({ handle: '.help-popover-header' }).css({ left: e.pageX + 12, top: e.pageY + 12 });
            });
            $(document).off('click.helpClose').on('click.helpClose', '.help-close', function () {
                $(this).closest('.help-popover').remove();
            });

            $('#editModal.modal').fadeIn(120).data('idf24', r.idf24);
        });
    }
    $('#dataTable').on('click', '.btn-edit', function (e) {
        e.stopPropagation();
        const idf24 = $(this).closest('tr').data('idf24');
        if (idf24 != null && idf24 !== '') openEditModal(idf24);
    });
    $('#btnSaveEdit').on('click', function () {
        const $modal = $('#editModal.modal');
        const original_idf24 = $modal.data('idf24');
        const updates = {};
        $('#editAccordion [data-name]').each(function () {
            const name = $(this).data('name');
            if ($(this).is(':checkbox')) {
                updates[name] = $(this).is(':checked');
            } else {
                updates[name] = $(this).val();
            }
        });
        postAction('save_concessione_edit', { original_idf24, updates: JSON.stringify(updates) }, function (resp) {
            if (resp && resp.success) {
                alert('Salvato correttamente.');
                $modal.fadeOut(120);
                location.reload();
            } else {
                alert('Errore salvataggio: ' + (resp?.error || 'n/d'));
            }
        });
    });

    // -----------------------------
    // Chiusura modali (× e Annulla)
    // -----------------------------
    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).fadeOut(120);
    });
    $('.modal').on('click', function (e) {
        if (e.target === this) $(this).fadeOut(120);
    });

    // -----------------------------
    // Tema & accessori
    // -----------------------------
    $('#theme-toggle, a#theme-toggle').on('click', function (e) {
        e.preventDefault();
        const root = document.documentElement;
        const isDark = root.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
});

<?php // /templates/concessioni.php ?>

<div class="controls-bar">
    <div class="btn-group">
        <?php if (($currentPageKey ?? '') === 'concessioni'): ?>
            <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_not_null_pec_null'])) ?>" class="btn" title="Verificati ma non spediti">
                <i class="fa-solid fa-circle-check" style="color:var(--color-warning);"></i> Verificati non spediti
            </a>
            <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_not_null_pec_not_null'])) ?>" class="btn" title="Verificati e spediti">
                <i class="fa-solid fa-check-double" style="color:var(--color-success);"></i> Verificati e spediti
            </a>
            <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_null_pec_null'])) ?>" class="btn" title="Non verificati">
                <i class="fa-solid fa-circle-xmark" style="color:var(--color-danger);"></i> Non verificati
            </a>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <a href="<?= htmlspecialchars(build_current_url(['reset_view' => 1])) ?>" class="btn" title="Ripristina vista iniziale">
            <i class="fa-solid fa-house"></i> Ripristina
        </a>
        <a href="<?= htmlspecialchars(build_current_url(['toggle_view' => 1])) ?>" class="btn" title="<?= !empty($full_view) ? 'Vista parziale' : 'Vista completa' ?>">
            <i class="fa-solid fa-table"></i> <?= !empty($full_view) ? 'Parziale' : 'Completa' ?>
        </a>
        <a href="<?= htmlspecialchars(build_current_url(['export_csv' => 1])) ?>" class="btn" title="Esporta CSV">
            <i class="fa-solid fa-file-csv"></i> CSV
        </a>
        <button id="btnResetFilters" class="btn" title="Azzera filtri"><i class="fa-solid fa-eraser"></i> Azzera filtri</button>
        <div class="dropdown">
            <button class="btn" id="btnColumns"><i class="fa-solid fa-columns"></i> Mostra/Nascondi colonne</button>
            <div class="dropdown-menu" id="columnsMenu">
                <?php foreach (($all_columns ?? $columns ?? []) as $col): ?>
                    <label>
                        <input type="checkbox" class="col-toggle" data-col="<?= htmlspecialchars($col) ?>" <?= in_array($col, $visible_columns ?? []) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($col) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="search-group">
        <input id="globalSearch" type="text" class="input" placeholder="Ricerca..." value="">
        <button id="clearSearch" class="btn btn-icon" title="Pulisci ricerca" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>

<div class="table-wrapper">
    <table id="dataTable">
        <thead>
            <tr id="headerRow">
                <?php foreach (($columns ?? []) as $col): 
                    $hidden = in_array($col, $hidden_columns ?? []);
                ?>
                    <th data-column="<?= htmlspecialchars($col) ?>" <?= $hidden ? 'data-hidden="1" style="display:none;"' : '' ?>>
                        <div class="th-inner">
                            <a href="<?= htmlspecialchars(build_current_url(['order' => $col, 'dir' => ($order_column ?? '') === $col && ($order_direction ?? 'ASC') === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                               class="sort-link"
                               title="Ordina per <?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($col) ?>
                                <?php if (($order_column ?? '') === $col): ?>
                                    <i class="fa-solid fa-sort-<?= ($order_direction ?? 'ASC') === 'ASC' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </a>
                            <button class="help-icon" data-help="<?= htmlspecialchars($col) ?>" title="Aiuto"><i class="fa-regular fa-circle-question"></i></button>
                        </div>
                        <div class="th-filter">
                            <input type="text" class="filter-input input" data-col="<?= htmlspecialchars($col) ?>" value="<?= htmlspecialchars(($filters ?? [])[$col] ?? '') ?>" placeholder="Filtra e premi Invio">
                        </div>
                    </th>
                <?php endforeach; ?>
                <!-- Colonna azioni fissa (non filtrabile/ordinabile) -->
                <th class="actions-col" data-column="_azioni" data-fixed="1">
                    <div class="th-inner">
                        Azioni
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($records ?? []) as $row): ?>
                <tr data-idf24="<?= htmlspecialchars($row['idf24'] ?? '') ?>">
                    <?php foreach (($columns ?? []) as $col): 
                        $hidden = in_array($col, $hidden_columns ?? []);
                        $value = $row[$col] ?? '';
                    ?>
                        <td <?= $hidden ? 'style="display:none;"' : '' ?>>
                            <span class="cell-text"><?= htmlspecialchars((string)$value) ?></span>
                            <?php if (in_array($col, ($pageConfig['highlight_columns'] ?? []), true)): ?>
                                <?php if (!empty($value)): ?>
                                    <span class="badge badge-success">OK</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">—</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="row-actions">
                        <button class="btn-icon btn-detail" type="button" aria-label="Dettagli" title="Dettagli (doppio click riga per aprire)">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button class="btn-icon btn-edit" type="button" aria-label="Modifica" title="Modifica">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($full_view) && ($total_pages ?? 1) > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= (int)$total_pages; $p++): ?>
                <a class="page-link <?= ($current_page ?? 1) === $p ? 'active' : '' ?>"
                   href="<?= htmlspecialchars(build_current_url(['page' => $p])) ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modali -->
<div id="detailModal" class="modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" data-close="detailModal" title="Chiudi" type="button">&times;</button>
        <div class="modal-grid">
            <aside class="modal-nav">
                <ul id="detailMenu"></ul>
            </aside>
            <section class="modal-body">
                <div id="detailHeader" class="detail-header"></div>
                <div id="detailContent" class="detail-content"></div>
            </section>
        </div>
    </div>
</div>

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content modal-xl">
        <button class="modal-close" data-close="editModal" title="Chiudi" type="button">&times;</button>
        <header class="modal-header">
            <h3><i class="fa-regular fa-pen-to-square"></i> Modifica Concessione</h3>
            <small id="editSubtitle" class="subtitle"></small>
        </header>
        <div id="editAccordion" class="accordion"></div>
        <footer class="modal-footer">
            <button id="btnSaveEdit" class="btn btn-primary" type="button"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
            <button class="btn" data-close="editModal" type="button"><i class="fa-solid fa-xmark"></i> Annulla</button>
        </footer>
    </div>
</div>

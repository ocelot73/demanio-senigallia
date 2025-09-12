<?php // /templates/concessioni.php ?>
<?php
// Salvaguardie: se il controller non avesse passato le variabili, calcolale qui.
$all_columns     = $columns ?? [];
$hidden_columns  = $hidden_columns ?? [];
$visible_columns = $visible_columns ?? array_values(array_diff($all_columns, $hidden_columns));
$order_column    = $order_column    ?? ($all_columns[0] ?? '');
$order_direction = $order_direction ?? 'ASC';
$current_page    = $current_page    ?? 1;
$full_view       = $full_view ?? true;
$filters_active  = $filters_active ?? false;
?>
<div class="card-container">
    <div class="controls-bar">
        <div class="btn-group">
            <?php if ($currentPageKey === 'concessioni'): ?>
                <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_not_null_pec_null'])) ?>" class="btn" title="Verificati ma non spediti"><i class="fas fa-check-circle" style="color:var(--color-warning);"></i> Verificati non spediti</a>
                <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_not_null_pec_not_null'])) ?>" class="btn" title="Verificati e spediti"><i class="fas fa-check-double" style="color:var(--color-success);"></i> Verificati e spediti</a>
                <a href="<?= htmlspecialchars(build_current_url(['filter_type' => 'verifica_null_pec_null'])) ?>" class="btn" title="Non verificati"><i class="fas fa-times-circle" style="color:var(--color-danger);"></i> Non verificati</a>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <a href="<?= htmlspecialchars(build_current_url(['reset_view' => 1])) ?>" class="btn" title="Ripristina vista iniziale"><i class="fas fa-home"></i> Ripristina</a>
            <a href="<?= htmlspecialchars(build_current_url(['toggle_view' => 1])) ?>" class="btn" title="<?= $full_view ? 'Vista parziale' : 'Vista completa' ?>"><i class="fas fa-table"></i> <?= $full_view ? 'Parziale' : 'Completa' ?></a>
            <?php if (!empty($hidden_columns)): ?>
                <a href="<?= htmlspecialchars(build_current_url(['show_all' => 1])) ?>" class="btn" title="Mostra tutte le colonne"><i class="fas fa-eye"></i> Mostra Colonne</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(build_current_url(['hide_all' => 1])) ?>" class="btn" title="Nascondi tutte le colonne"><i class="fas fa-eye-slash"></i> Nascondi Colonne</a>
            <?php endif; ?>
            <button id="toggle-col-width" class="btn" title="Cambia larghezza colonne"><i class="fas fa-text-width"></i> Largh. colonne</button>
            <a href="<?= htmlspecialchars(build_current_url(['reset_order' => 1])) ?>" class="btn" title="Ripristina ordinamento colonne"><i class="fas fa-undo"></i> Ordine</a>
            <a href="<?= htmlspecialchars(build_current_url(['export_csv' => 1])) ?>" class="btn" title="Esporta dati in formato CSV"><i class="fas fa-file-csv"></i> Esporta</a>
            <?php if ($filters_active): ?>
                <a href="<?= htmlspecialchars(build_current_url(['clear_filters' => 1])) ?>" class="btn btn-primary" title="Azzera tutti i filtri di colonna"><i class="fas fa-broom"></i> Azzera filtri</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="hiddenColumnsBar" class="hidden-columns-bar" style="display:none">
        <span>Colonne nascoste:</span>
        <div id="hiddenColumnsList"></div>
    </div>

    <div class="table-wrapper">
        <table id="dataTable" class="data-table">
            <thead>
                <tr>
                    <th class="thin">Azioni</th>
                    <th class="thin">N.</th>
                    <?php foreach ($visible_columns as $col): ?>
                        <th data-column="<?= htmlspecialchars($col) ?>">
                            <div class="header-content">
                                <span class="col-title"><?= htmlspecialchars($col) ?></span>
                                <?php
                                  $isAsc   = (strtoupper($order_direction) === 'ASC');
                                  $nextDir = ($order_column === $col && $isAsc) ? 'DESC' : 'ASC';
                                ?>
                                <a class="sort-btn <?= $order_column === $col ? 'active' : '' ?>"
                                   href="<?= htmlspecialchars(build_current_url(['order' => $col, 'dir' => $nextDir])) ?>"
                                   title="Ordina">
                                    <i class="fas fa-sort"></i>
                                </a>
                                <button class="toggle-btn" onclick="toggleColumn('<?= htmlspecialchars($col) ?>')" title="Nascondi/Mostra colonna">
                                    <i class="fas fa-columns"></i>
                                </button>
                            </div>
                            <input class="filter-input" type="text" data-column="<?= htmlspecialchars($col) ?>" placeholder="Filtra..." value="<?= htmlspecialchars($filters[$col] ?? '') ?>">
                            <div class="resizer"></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $rowNumber = 1 + (($current_page - 1) * (int)RECORDS_PER_PAGE);
            if (empty($records)): ?>
                <tr>
                    <td colspan="<?= count($visible_columns) + 2 ?>" style="text-align: center; padding: 2rem;">Nessun dato trovato.</td>
                </tr>
            <?php else:
                foreach ($records as $row):
                    $verVal = strtolower(trim((string)($row['verifica'] ?? $row['verifica '] ?? '')));
                    $isVerified = in_array($verVal, ['si','sì','sí','sì'], true);
                    $trClass = $isVerified ? '' : 'no-verifica';
                    $idf24 = (string)($row['idf24'] ?? '');
                ?>
                <tr class="<?= $trClass ?>" data-idf24="<?= htmlspecialchars($idf24) ?>">
                    <td class="row-actions">
                        <a class="details-btn" href="#" data-idf24="<?= htmlspecialchars($idf24) ?>" title="Dettagli">
                             <i class="fas fa-eye"></i>
                        </a>
                        <a class="edit-btn" href="#" data-idf24="<?= htmlspecialchars($idf24) ?>" title="Modifica">
                            <i class="fas fa-pen-to-square"></i>
                        </a>
                    </td>
                    <td><?= $rowNumber++ ?></td>
                    <?php foreach ($visible_columns as $col): ?>
                        <td><?= htmlspecialchars((string)($row[$col] ?? '')) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach;
            endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$full_view) require __DIR__ . '/partials/pagination.php'; ?>
</div>

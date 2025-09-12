<?php // /templates/concessioni.php
// Variabili attese: $records, $columns, $hidden_columns, $order_column, $order_direction,
// $total_pages, $current_page, $full_view

$visible_columns = array_values(array_diff($columns, $hidden_columns ?? []));
?>
<div class="card-container">

    <div class="table-actions">
        <div class="left">
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['reset_view' => 1])) ?>">
                <i class="fas fa-rotate-left"></i> Ripristina
            </a>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['toggle_view' => 1])) ?>">
                <i class="fas fa-eye"></i> <?= $full_view ? 'Parziale' : 'Completa' ?>
            </a>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['show_all' => 1])) ?>">
                <i class="fas fa-table-columns"></i> Mostra colonne
            </a>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['hide_all' => 1])) ?>">
                <i class="fas fa-table-columns"></i> Nascondi colonne
            </a>
            <button id="toggle-col-width" class="btn">
                <i class="fas fa-arrows-left-right-to-line"></i> Largh. colonne
            </button>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['reset_order' => 1])) ?>">
                <i class="fas fa-arrow-down-a-z"></i> Ordine
            </a>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['clear_filters' => 1])) ?>">
                <i class="fas fa-filter-circle-xmark"></i> Azzera filtri
            </a>
            <a class="btn" href="<?= htmlspecialchars(build_current_url(['export_csv' => 1])) ?>">
                <i class="fas fa-file-export"></i> Esporta
            </a>
        </div>
    </div>

    <!-- Barra “colonne nascoste” gestita da JS -->
    <div id="hiddenColumnsBar" class="hidden-columns-bar" style="display:none">
        <div class="title"><i class="fas fa-eye-slash"></i> Colonne nascoste:</div>
        <div id="hiddenColumnsList"></div>
    </div>

    <div class="table-wrapper">
        <table id="dataTable">
            <thead>
                <tr>
                    <!-- Azioni / N. -->
                    <th data-column="azioni"><div class="header-content"><span class="col-title">AZIONI</span></div></th>
                    <th data-column="n"><div class="header-content"><span class="col-title">N.</span></div></th>

                    <?php foreach ($visible_columns as $col): ?>
                        <th data-column="<?= htmlspecialchars($col) ?>">
                            <div class="header-content">
                                <span class="col-title"><?= htmlspecialchars($col) ?></span>
                                <?php
                                    $nextDir = ($order_column === $col && strtoupper($order_direction) === 'ASC') ? 'DESC' : 'ASC';
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
                            <input class="filter-input" type="text" data-column="<?= htmlspecialchars($col) ?>" placeholder="Filtra...">
                            <div class="resizer"></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $rowNumber = 1 + (($current_page - 1) * RECORDS_PER_PAGE);
                foreach ($records as $row):
                    // Rileva verifica
                    $verVal = strtolower(trim((string)($row['verifica'] ?? $row['verifica '] ?? '')));
                    $isVerified = in_array($verVal, ['si','sì','sí','sì']); // varianti
                    $trClass = $isVerified ? '' : 'no-verifica';
                ?>
                <tr class="<?= $trClass ?>">
                    <td class="row-actions">
                        <a class="details-btn" href="<?= htmlspecialchars(build_current_url(['details' => $row['idf24'] ?? ''])) ?>" title="Dettagli">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a class="edit-btn" href="<?= htmlspecialchars(build_current_url(['edit' => $row['idf24'] ?? ''])) ?>" title="Modifica">
                            <i class="fas fa-pen-to-square"></i>
                        </a>
                    </td>
                    <td><?= $rowNumber++ ?></td>

                    <?php foreach ($visible_columns as $col): ?>
                        <td><?= htmlspecialchars((string)($row[$col] ?? '')) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require __DIR__ . '/partials/pagination.php'; ?>
</div>

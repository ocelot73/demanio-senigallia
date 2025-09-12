<?php // /templates/concessioni.php ?>
<?php
// Salvaguardie: se il controller non avesse passato le variabili, calcolale qui.
$all_columns    = isset($columns) && is_array($columns) ? $columns : [];
$hidden_columns = isset($hidden_columns) && is_array($hidden_columns) ? $hidden_columns : [];
$visible_columns = isset($visible_columns) && is_array($visible_columns)
    ? $visible_columns
    : array_values(array_diff($all_columns, $hidden_columns));

$order_column    = $order_column    ?? (isset($all_columns[0]) ? $all_columns[0] : '');
$order_direction = $order_direction ?? 'ASC';
$current_page    = $current_page    ?? 1;
?>
<div class="card-container">
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
                            <input class="filter-input" type="text" data-column="<?= htmlspecialchars($col) ?>" placeholder="Filtra.">
                            <div class="resizer"></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $rowNumber = 1 + (($current_page - 1) * RECORDS_PER_PAGE);
            foreach (($records ?? []) as $row):
                $verVal = strtolower(trim((string)($row['verifica'] ?? $row['verifica '] ?? '')));
                $isVerified = in_array($verVal, ['si','sì','sí','sì'], true);
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

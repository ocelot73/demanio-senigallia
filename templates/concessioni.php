<?php // /templates/concessioni.php ?>

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

<div class="hidden-columns-bar" id="hiddenColumnsBar"><strong>Colonne nascoste:</strong> <span id="hiddenColumnsList"></span></div>

<div class="table-container">
    <table id="dataTable">
        <thead>
            <tr>
                <?php if (in_array('idf24', $columns)): ?>
                    <th class="action-column-header" style="width:110px; text-align:center; min-width: 110px;">Azioni</th>
                <?php endif; ?>
                <?php foreach ($columns as $col):
                  if (in_array($col, $hidden_columns)) continue;
                  $is_sorted = ($col == $order_column);
                  $next_dir = $is_sorted && strtoupper($order_direction) === 'ASC' ? 'DESC' : 'ASC';
                  // Logica per le nuove icone
                  $sort_icon_class = 'fa-sort'; // Icona di default (non ordinato)
                  if ($is_sorted) {
                      $sort_icon_class = strtoupper($order_direction) === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
                  }
                ?>
                <th data-column="<?= htmlspecialchars($col) ?>">
                  <div class="header-content">
                    <span class="col-title"><?= htmlspecialchars($col) ?></span>
                    <div class="header-title-actions">
                        <a href="<?= htmlspecialchars(build_current_url(['order' => $col, 'dir' => $next_dir])) ?>" class="sort-btn <?= $is_sorted ? 'active' : '' ?>" title="Ordina">
                            <i class="fas <?= $sort_icon_class ?>"></i>
                        </a>
                         <button class="toggle-btn" onclick="toggleColumn('<?= htmlspecialchars($col) ?>')" title="Nascondi">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                     </div>
                  </div>
                  <input type="text" class="filter-input" data-column="<?= htmlspecialchars($col) ?>" value="<?= htmlspecialchars($filters[$col] ?? '') ?>" placeholder="Filtra..." title="Premi Invio per filtrare">
                  <div class="resizer"></div>
                </th>
                 <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="<?= count($visible_columns) + 1 ?>" style="text-align:center; padding: 2rem;">Nessun dato trovato.</td></tr>
          <?php else:
            foreach ($records as $row):
                $row_class = ($currentPageKey === 'concessioni' && (!isset($row['verifica']) || $row['verifica'] === null || strtolower(trim($row['verifica'])) !== 'sì')) ? 'no-verifica' : '';
                $idf24_value = $row['idf24'] ?? '';
          ?>
              <tr class="<?= $row_class ?>" data-idf24="<?= htmlspecialchars($idf24_value) ?>">
                <?php if (in_array('idf24', $columns)): ?>
                  <td style="text-align:center;">
                    <?php if ($idf24_value): ?>
                     <span class="row-actions">
                        <a href="index.php?page=concessione_dettaglio&idf24=<?= htmlspecialchars($idf24_value) ?>" class="action-btn-link" title="Fascicolo e Contabilità">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </a>
                        <a href="#" class="details-btn" title="Visualizza dati SID"><i class="fas fa-search"></i></a>
                        <a href="#" class="edit-btn" title="Modifica dati concessione"><i class="fas fa-pencil-alt"></i></a>
                      </span>
                     <?php endif; ?>
                  </td>
                <?php endif; ?>
                <?php foreach ($columns as $col):
                  if (in_array($col, $hidden_columns)) continue;
                  $value = $row[$col] ?? '';
                  // Logica di formattazione copiata dall'originale
                  if (($col === 'verifica' || $col === 'pec inviata') && strtolower(trim($value)) === 'sì') {
                      $value = '<span class="icon-check">✓</span>';
                  } elseif ($col === 'diff_scad' && trim($value) === 'diff.') {
                      $value = '<span class="icon-cross">✗</span>';
                  } elseif ($col === 'canone' && is_numeric($value) && $currentPageKey === 'calcolo_canoni') {
                      $value = number_format((float)$value, 2, ',', '.');
                  } elseif ($col === 'canone 2025' && is_numeric($value)) {
                      $value = number_format((float)$value, 2, ',', '.');
                  } elseif (is_numeric($value) && str_contains((string)$value, '.') && !in_array($col, ['canone 2025','canone'])) {
                      $value = number_format((float)$value, 2, ',', '.');
                  } else {
                      $value = htmlspecialchars($value);
                  }
                ?>
                  <td><?= $value ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!$full_view && $total_pages > 1): ?>
    <?php require __DIR__ . '/partials/pagination.php'; ?>
<?php endif; ?>

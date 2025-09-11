<?php // /templates/concessioni.php ?>
<div class="controls-bar">
    <div class="btn-group">
        <?php if ($currentPageKey === 'concessioni'): ?>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_not_null_pec_null" class="btn" title="Verificati ma non spediti"><i class="fas fa-check-circle" style="color:var(--color-warning);"></i> Verificati non spediti</a>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_not_null_pec_not_null" class="btn" title="Verificati e spediti"><i class="fas fa-check-double" style="color:var(--color-success);"></i> Verificati e spediti</a>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_null_pec_null" class="btn" title="Non verificati"><i class="fas fa-times-circle" style="color:var(--color-danger);"></i> Non verificati</a>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&reset_view=1" class="btn" title="Ripristina vista iniziale"><i class="fas fa-home"></i> Ripristina</a>
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&toggle_view=1" class="btn" title="<?= ($data['full_view'] ?? false) ? 'Vista parziale' : 'Vista completa' ?>"><i class="fas fa-table"></i> <?= ($data['full_view'] ?? ($currentPageKey === 'concessioni')) ? 'Parziale' : 'Completa' ?></a>
        <?php if (!empty($data['hidden_columns'] ?? [])): ?>
            <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&show_all=1" class="btn" title="Mostra tutte le colonne"><i class="fas fa-eye"></i> Mostra Colonne</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&hide_all=1" class="btn" title="Nascondi tutte le colonne"><i class="fas fa-eye-slash"></i> Nascondi Colonne</a>
        <?php endif; ?>
        <button id="toggle-col-width" class="btn" title="Cambia larghezza colonne"><i class="fas fa-text-width"></i> Largh.colonne</button>
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&reset_order=1" class="btn" title="Ripristina ordinamento colonne"><i class="fas fa-undo"></i> Ordine</a>
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&export_csv=1" class="btn" title="Esporta dati in formato CSV"><i class="fas fa-file-csv"></i> Esporta</a>
        <?php if (!empty($data['filters'] ?? [])): ?><a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&clear_filters=1" class="btn btn-primary" title="Azzera tutti i filtri di colonna"><i class="fas fa-broom"></i> Azzera filtri</a><?php endif; ?>
    </div>
</div>

<div class="hidden-columns-bar" id="hiddenColumnsBar" style="<?= empty($data['hidden_columns'] ?? []) ? 'display:none' : '' ?>">
    <strong>Colonne nascoste:</strong> <span id="hiddenColumnsList"></span>
</div>

<div class="table-container">
    <table id="dataTable">
        <thead>
            <tr>
                <th style="width: 80px;">Azioni</th>
                <?php foreach ($data['columns'] as $col):
                    if (in_array($col, ($data['hidden_columns'] ?? []))) continue;
                ?>
                <th data-column="<?= htmlspecialchars($col) ?>">
                    <div class="header-content">
                        <span class="col-title"><?= htmlspecialchars($col) ?></span>
                        <div class="header-title-actions">
                            <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&order=<?= urlencode($col) ?>&dir=<?= ($data['order_column'] ?? '') == $col && ($data['order_direction'] ?? '') == 'ASC' ? 'DESC' : 'ASC' ?>" class="sort-btn <?= ($data['order_column'] ?? '') == $col ? 'active' : '' ?>">
                            <?= ($data['order_column'] ?? '') == $col ? (($data['order_direction'] ?? '') == 'ASC' ? '↑' : '↓') : '↕' ?>
                          </a>
                          <button class="toggle-btn" data-column="<?= htmlspecialchars($col) ?>">✕</button>
                        </div>
                    </div>
                    <input type="text" class="filter-input" data-column="<?= htmlspecialchars($col) ?>" value="<?= htmlspecialchars(($data['filters'] ?? [])[$col] ?? '') ?>" placeholder="Filtra...">
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
          <?php if (!empty($data['records'])): foreach ($data['records'] as $row): ?>
          <tr data-idf24="<?= htmlspecialchars($row['idf24'] ?? '') ?>">
              <td style="text-align:center;">
                  <span class="row-actions">
                    <a href="#" class="details-btn" title="Dettagli SID"><i class="fas fa-search"></i></a>
                    <a href="#" class="edit-btn" title="Modifica"><i class="fas fa-pencil-alt"></i></a>
                  </span>
              </td>
              <?php foreach ($data['columns'] as $col): if (in_array($col, ($data['hidden_columns'] ?? []))) continue; ?>
                  <td><?= htmlspecialchars($row[$col] ?? '') ?></td>
              <?php endforeach; ?>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="<?= count($data['columns'] ?? []) + 1 ?>">Nessun record trovato.</td></tr>
          <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/pagination.php'; ?>

<?php // /templates/concessioni.php ?>
<div class="controls-bar">
    <div class="btn-group">
        <?php // ?>
        <?php if ($currentPageKey === 'concessioni'): ?>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_not_null_pec_null" class="btn" title="Verificati ma non spediti"><i class="fas fa-check-circle" style="color:var(--color-warning);"></i> Verificati non spediti</a>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_not_null_pec_not_null" class="btn" title="Verificati e spediti"><i class="fas fa-check-double" style="color:var(--color-success);"></i> Verificati e spediti</a>
            <a href="<?= APP_URL ?>/index.php?page=concessioni&filter_type=verifica_null_pec_null" class="btn" title="Non verificati"><i class="fas fa-times-circle" style="color:var(--color-danger);"></i> Non verificati</a>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <?php // ?>
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&reset_view=1" class="btn"><i class="fas fa-home"></i> Ripristina Vista</a>
        <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&export_csv=1" class="btn"><i class="fas fa-file-csv"></i> Esporta</a>
    </div>
</div>

<div class="hidden-columns-bar" id="hiddenColumnsBar" style="<?= empty($hidden_columns) ? 'display:none' : '' ?>">
    <strong>Colonne nascoste:</strong> <span id="hiddenColumnsList"></span>
</div>

<div class="table-container">
    <table id="dataTable">
        <thead>
            <tr>
                <th style="width: 80px;">Azioni</th>
                <?php foreach ($columns as $col):
                    if (in_array($col, $hidden_columns)) continue;
                ?>
                <th data-column="<?= htmlspecialchars($col) ?>">
                    <div class="header-content">
                        <span class="col-title"><?= htmlspecialchars($col) ?></span>
                        <div class="header-title-actions">
                            <?php // ?>
                            <a href="<?= APP_URL ?>/index.php?page=<?= $currentPageKey ?>&order=<?= $col ?>&dir=<?= $order_column == $col && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" class="sort-btn <?= $order_column == $col ? 'active' : '' ?>">
                            <?= $order_column == $col ? ($order_direction == 'ASC' ? '↑' : '↓') : '↕' ?>
                          </a>
                          <button class="toggle-btn" data-column="<?= htmlspecialchars($col) ?>">✕</button>
                        </div>
                    </div>
                    <input type="text" class="filter-input" data-column="<?= htmlspecialchars($col) ?>" value="<?= htmlspecialchars($filters[$col] ?? '') ?>" placeholder="Filtra...">
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
          <?php if (!empty($records)): foreach ($records as $row): ?>
          <tr data-idf24="<?= htmlspecialchars($row['idf24'] ?? '') ?>">
              <td style="text-align:center;">
                  <span class="row-actions">
                      <a href="#" class="details-btn" title="Dettagli SID"><i class="fas fa-search"></i></a>
                    <a href="#" class="edit-btn" title="Modifica"><i class="fas fa-pencil-alt"></i></a>
                  </span>
              </td>
              <?php foreach ($columns as $col): if (in_array($col, $hidden_columns)) continue; ?>
                  <td><?= htmlspecialchars($row[$col] ?? '') ?></td>
              <?php endforeach; ?>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="<?= count($columns) + 1 ?>">Nessun record trovato.</td></tr>
          <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/pagination.php'; ?>

<?php // /templates/partials/header.php ?>
<header class="main-header">
    <div class="header-left">
        <button id="sidebar-toggle" title="Apri/Chiudi Menu"><i class="fas fa-bars"></i></button>
        <div class="page-title">
            <h1><?= htmlspecialchars($pageConfig['label']) ?></h1>
            <p><?= htmlspecialchars($pageConfig['title']) ?></p>
        </div>
    </div>
    <div class="header-right">
        <?php if ($currentPageKey === 'calcolo_canoni'): ?>
            <form method="GET" style="display:inline-flex; align-items:center; gap: 0.5rem;">
              <input type="hidden" name="page" value="calcolo_canoni">
              <label for="yearSelector" style="font-weight: 600;">Anno:</label>
              <select id="yearSelector" name="anno" class="btn" onchange="this.form.submit()">
                  <?php 
                  $selected_year = $_GET['anno'] ?? date('Y');
                  for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                  <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                  <?php endfor; ?>
              </select>
            </form>
        <?php endif; ?>
        <div class="global-search">
            <i class="fas fa-search"></i>
            <input type="text" id="globalSearch" placeholder="Cerca nella tabella...">
            <button id="clearSearch" title="Svuota ricerca">&times;</button>
        </div>
    </div>
</header>

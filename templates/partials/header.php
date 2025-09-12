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
        <div class="global-search">
            <i class="fas fa-search"></i>
            <input type="text" id="globalSearch" placeholder="Cerca nella tabella...">
            <button id="clearSearch" title="Svuota ricerca">&times;</button>
        </div>
        <?php if ($currentPageKey === 'calcolo_canoni'): ?>
            <form method="GET" style="display:inline-flex; align-items:center; gap: 0.5rem;">
              <input type="hidden" name="page" value="calcolo_canoni">
              <label for="yearSelector" style="font-weight: 600;">Anno:</label>
              <select id="yearSelector" name="anno" class="btn" onchange="this.form.submit()">
                  <?php 
                  // CORREZIONE: La logica per popolare gli anni deve essere identica all'originale,
                  // interrogando il DB per gli anni disponibili invece di un loop statico.
                  $selected_year_for_query = $_SESSION['selected_year'] ?? date('Y');
                  $years_result = pg_query($conn, "SELECT DISTINCT anno FROM demanio.canoni ORDER BY anno DESC");
                  $available_years = $years_result ? array_map(fn($r)=>(int)$r['anno'], pg_fetch_all($years_result) ?: []) : [];
                  if (!in_array($selected_year_for_query, $available_years)) { 
                      $available_years[] = $selected_year_for_query; 
                      rsort($available_years); 
                  }
                  foreach ($available_years as $year): ?>
                  <option value="<?= $year ?>" <?= $year == $selected_year_for_query ? 'selected' : '' ?>><?= $year ?></option>
                  <?php endforeach; ?>
              </select>
            </form>
        <?php endif; ?>
    </div>
</header>

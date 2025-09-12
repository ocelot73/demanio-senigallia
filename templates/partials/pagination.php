<?php
// /templates/partials/pagination.php

// CORREZIONE: L'intera paginazione è stata sostituita con quella dell'originale
// per replicare fedelmente la logica e lo stile, inclusi i link "Prima/Ultima" e "..."
if (!isset($total_pages) || $total_pages <= 1) return;

$current = (int)($current_page ?? 1);
?>
<div class="pagination">
    <?php if ($current > 1): ?>
      <a href="<?= htmlspecialchars(build_current_url(['p' => 1])) ?>">&laquo; Prima</a>
      <a href="<?= htmlspecialchars(build_current_url(['p' => $current - 1])) ?>">&lsaquo; Prec.</a>
    <?php else: ?>
      <span class="disabled">&laquo; Prima</span><span class="disabled">&lsaquo; Prec.</span>
    <?php endif; ?>

    <?php
      $start = max(1, $current - 2); 
      $end = min($total_pages, $current + 2);
      
      if ($start > 1) { 
          echo '<a href="' . htmlspecialchars(build_current_url(['p' => 1])) . '">1</a>'; 
          if ($start > 2) echo '<span class="disabled">...</span>'; 
      }
      
      for ($i = $start; $i <= $end; $i++) {
          echo $i == $current 
            ? '<a class="page-link active">' . $i . '</a>' 
            : '<a href="' . htmlspecialchars(build_current_url(['p' => $i])) . '">' . $i . '</a>';
      }
      
      if ($end < $total_pages) { 
          if ($end < $total_pages - 1) echo '<span class="disabled">...</span>'; 
          echo '<a href="' . htmlspecialchars(build_current_url(['p' => $total_pages])) . '">' . $total_pages . '</a>'; 
      }
    ?>
    
    <?php if ($current < $total_pages): ?>
      <a href="<?= htmlspecialchars(build_current_url(['p' => $current + 1])) ?>">Succ. &rsaquo;</a>
      <a href="<?= htmlspecialchars(build_current_url(['p' => $total_pages])) ?>">Ultima &raquo;</a>
    <?php else: ?>
      <span class="disabled">Succ. &rsaquo;</span>
      <span class="disabled">Ultima &raquo;</span>
    <?php endif; ?>
    
    <span style="margin-left:10px; color: var(--color-text-secondary); font-size: 0.85rem; font-weight: 600;">Pagina <?= $current ?> di <?= $total_pages ?> (Tot: <?= $total_records ?>)</span>
</div>

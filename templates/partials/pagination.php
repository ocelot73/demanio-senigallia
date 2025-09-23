<?php
// /templates/partials/pagination.php

if (!isset($total_pages) || $total_pages <= 1) return;

$current = (int)($current_page ?? 1);
?>
<div class="pagination">
    <?php if ($current > 1): ?>
      <!-- CORREZIONE: Usa 'page' invece di 'p' per il parametro -->
      <a href="<?= htmlspecialchars(build_current_url(['page' => 1])) ?>">&laquo; Prima</a>
      <a href="<?= htmlspecialchars(build_current_url(['page' => $current - 1])) ?>">&lsaquo; Prec.</a>
    <?php else: ?>
      <span class="disabled">&laquo; Prima</span><span class="disabled">&lsaquo; Prec.</span>
    <?php endif; ?>

    <?php
      $start = max(1, $current - 2);
      $end = min($total_pages, $current + 2);

      if ($start > 1) {
          echo '<a href="' . htmlspecialchars(build_current_url(['page' => 1])) . '">1</a>';
          if ($start > 2) echo '<span class="disabled">...</span>';
      }

      for ($i = $start; $i <= $end; $i++) {
          echo $i == $current
            ? '<span class="current">' . $i . '</span>'
            : '<a href="' . htmlspecialchars(build_current_url(['page' => $i])) . '">' . $i . '</a>';
      }

      if ($end < $total_pages) {
          if ($end < $total_pages - 1) echo '<span class="disabled">...</span>';
          echo '<a href="' . htmlspecialchars(build_current_url(['page' => $total_pages])) . '">' . $total_pages . '</a>';
      }
    ?>

    <?php if ($current < $total_pages): ?>
      <a href="<?= htmlspecialchars(build_current_url(['page' => $current + 1])) ?>">Succ. &rsaquo;</a>
      <a href="<?= htmlspecialchars(build_current_url(['page' => $total_pages])) ?>">Ultima &raquo;</a>
    <?php else: ?>
      <span class="disabled">Succ. &rsaquo;</span>
      <span class="disabled">Ultima &raquo;</span>
    <?php endif; ?>

    <span style="margin-left:10px; color: var(--color-text-secondary);">Pagina <?= $current ?> di <?= $total_pages ?></span>
</div>

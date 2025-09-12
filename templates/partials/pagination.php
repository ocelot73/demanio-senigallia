<?php // /templates/partials/pagination.php 
if (isset($total_pages) && $total_pages > 1): ?>
<div class="pagination">
    <?php if ($current_page > 1): ?>
      <a href="<?= build_current_url(['p' => 1]) ?>">&laquo;</a>
      <a href="<?= build_current_url(['p' => $current_page - 1]) ?>">&lsaquo;</a>
    <?php else: ?>
      <span class="disabled">&laquo;</span><span class="disabled">&lsaquo;</span>
    <?php endif; ?>
    
    <?php 
      $start = max(1, $current_page - 2); $end = min($total_pages, $current_page + 2);
      if ($start > 1) { echo '<a href="' . build_current_url(['p' => 1]) . '">1</a>'; if ($start > 2) echo '<span class="dots">...</span>'; }
      for ($i = $start; $i <= $end; $i++) echo $i == $current_page ? '<span class="current">' . $i . '</span>' : '<a href="' . build_current_url(['p' => $i]) . '">' . $i . '</a>';
      if ($end < $total_pages) { if ($end < $total_pages - 1) echo '<span class="dots">...</span>'; echo '<a href="' . build_current_url(['p' => $total_pages]) . '">' . $total_pages . '</a>'; }
    ?>

    <?php if ($current_page < $total_pages): ?>
      <a href="<?= build_current_url(['p' => $current_page + 1]) ?>">&rsaquo;</a>
      <a href="<?= build_current_url(['p' => $total_pages]) ?>">&raquo;</a>
    <?php else: ?>
      <span class="disabled">&rsaquo;</span><span class="disabled">&raquo;</span>
    <?php endif; ?>
</div>
<?php endif; ?>

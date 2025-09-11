<?php // /templates/partials/pagination.php 
if (isset($total_pages) && $total_pages > 1): ?>
<div class="pagination">
    <?php if ($current_page > 1): ?>
      <a href="?page=<?= $currentPageKey ?>&p=1">&laquo;</a>
      <a href="?page=<?= $currentPageKey ?>&p=<?= $current_page - 1 ?>">&lsaquo;</a>
    <?php else: ?>
      <span class="disabled">&laquo;</span><span class="disabled">&lsaquo;</span>
    <?php endif; ?>
    
    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
        <a href="?page=<?= $currentPageKey ?>&p=<?= $i ?>" class="<?= $i == $current_page ? 'current' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($current_page < $total_pages): ?>
      <a href="?page=<?= $currentPageKey ?>&p=<?= $current_page + 1 ?>">&rsaquo;</a>
      <a href="?page=<?= $currentPageKey ?>&p=<?= $total_pages ?>">&raquo;</a>
    <?php else: ?>
      <span class="disabled">&rsaquo;</span><span class="disabled">&raquo;</span>
    <?php endif; ?>
</div>
<?php endif; ?>

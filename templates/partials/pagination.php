<?php
// /templates/partials/pagination.php
if (!isset($total_pages) || $total_pages <= 1) return;

$current = (int)($current_page ?? 1);
$prev = max(1, $current - 1);
$next = min($total_pages, $current + 1);
?>
<div class="pagination">
    <a class="page-link" href="<?= htmlspecialchars(build_current_url(['p' => $prev])) ?>">&laquo;</a>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a class="page-link <?= $i === $current ? 'active' : '' ?>"
           href="<?= htmlspecialchars(build_current_url(['p' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a class="page-link" href="<?= htmlspecialchars(build_current_url(['p' => $next])) ?>">&raquo;</a>
</div>

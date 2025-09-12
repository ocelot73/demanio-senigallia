<?php // /templates/partials/pagination.php

// Salvaguardie
$total_pages  = isset($total_pages)  ? max(1, (int)$total_pages) : 1;
$current_page = isset($current_page) ? max(1, (int)$current_page) : max(1, (int)($_GET['p'] ?? 1));

$go = function(int $p) {
    return htmlspecialchars(build_current_url(['p' => $p]));
};

$prev = max(1, $current_page - 1);
$next = min($total_pages, $current_page + 1);
?>
<div class="pagination">
    <a class="nav-button <?= $current_page <= 1 ? 'disabled' : '' ?>" href="<?= $go(1) ?>">&laquo;</a>
    <a class="nav-button <?= $current_page <= 1 ? 'disabled' : '' ?>" href="<?= $go($prev) ?>">&lsaquo;</a>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a class="nav-button <?= $i === $current_page ? 'active' : '' ?>" href="<?= $go($i) ?>"><?= $i ?></a>
    <?php endfor; ?>

    <a class="nav-button <?= $current_page >= $total_pages ? 'disabled' : '' ?>" href="<?= $go($next) ?>">&rsaquo;</a>
    <a class="nav-button <?= $current_page >= $total_pages ? 'disabled' : '' ?>" href="<?= $go($total_pages) ?>">&raquo;</a>
</div>

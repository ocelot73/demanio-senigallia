<?php // /templates/partials/sidebar.php ?>
<nav id="sidebar">
    <div>
        <div class="sidebar-header">
            <img src="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" alt="Logo">
            <div class="logo-text">
                <h2>Demanio</h2>
                <p>Comune di Senigallia</p>
            </div>
        </div>
        <ul class="sidebar-nav">
            <?php
            // CORREZIONE: La logica di rendering del menu è stata sostituita con quella
            // dell'originale per garantire il corretto raggruppamento del sottomenu "Canoni".
            $canoni_group_rendered = false;
            $is_canoni_page_active = count(array_intersect([$currentPageKey], $MENU_GROUPS['Canoni']['pages'])) > 0;

            foreach ($PAGES as $key => $config):
                $is_in_canoni_group = in_array($key, $MENU_GROUPS['Canoni']['pages']);

                if ($is_in_canoni_group) {
                    if (!$canoni_group_rendered) {
                        ?>
                        <li class="has-submenu <?= $is_canoni_page_active ? 'open' : '' ?>">
                            <a href="#" class="submenu-toggle">
                                <i class="<?= htmlspecialchars($MENU_GROUPS['Canoni']['icon']) ?>"></i>
                                <span>Canoni</span>
                                <i class="fas fa-chevron-right submenu-arrow"></i>
                            </a>
                            <ul class="submenu">
                                <?php foreach($MENU_GROUPS['Canoni']['pages'] as $canoni_key):
                                    $canoni_config = $PAGES[$canoni_key];
                                ?>
                                <li>
                                    <a href="<?= APP_URL ?>/index.php?page=<?= $canoni_key ?>" class="<?= $canoni_key === $currentPageKey ? 'active' : '' ?>" title="<?= htmlspecialchars($canoni_config['title']) ?>">
                                        <i class="<?= htmlspecialchars($canoni_config['icon']) ?>"></i>
                                        <span><?= htmlspecialchars($canoni_config['label']) ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php
                        $canoni_group_rendered = true;
                    }
                } else {
                    ?>
                    <li>
                        <a href="<?= isset($config['url']) ? htmlspecialchars($config['url']) : APP_URL . '/index.php?page=' . $key ?>"
                           class="<?= $key === $currentPageKey ? 'active' : '' ?>"
                           title="<?= htmlspecialchars($config['title']) ?>"
                           <?= isset($config['url']) ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                            <i class="<?= htmlspecialchars($config['icon']) ?>"></i>
                            <span><?= htmlspecialchars($config['label']) ?></span>
                        </a>
                    </li>
                    <?php
                }
            endforeach;
            ?>
        </ul>
    </div>
    <div class="sidebar-footer">
        <div class="theme-switcher">
            <button id="theme-toggle" title="Cambia Tema">
                <i class="fas fa-moon"></i>
                <span class="link-text">Tema Scuro</span>
            </button>
        </div>
        <a href="<?= APP_URL ?>/index.php?logout=1"><i class="fas fa-sign-out-alt"></i><span class="link-text">Logout</span></a>
    </div>
</nav>

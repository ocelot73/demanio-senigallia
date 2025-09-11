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
            $rendered_groups = [];
            foreach ($PAGES as $key => $config):
                $is_in_group = false;
                foreach ($MENU_GROUPS as $group_name => $group_config) {
                    if (in_array($key, $group_config['pages'])) {
                        $is_in_group = true;
                        if (!in_array($group_name, $rendered_groups)) {
                            // Controlla se una delle pagine del gruppo è quella attiva
                            $is_group_active = count(array_intersect([$currentPageKey], $group_config['pages'])) > 0;
                            ?>
                            <li class="has-submenu <?= $is_group_active ? 'open' : '' ?>">
                                <a href="#" class="submenu-toggle">
                                    <i class="<?= htmlspecialchars($group_config['icon']) ?>"></i>
                                    <span><?= htmlspecialchars($group_name) ?></span>
                                    <i class="fas fa-chevron-right submenu-arrow"></i>
                                </a>
                                <ul class="submenu">
                                    <?php foreach($group_config['pages'] as $page_key):
                                        $sub_config = $PAGES[$page_key]; ?>
                                    <li>
                                        <a href="<?= APP_URL ?>/index.php?page=<?= $page_key ?>" class="<?= $page_key === $currentPageKey ? 'active' : '' ?>" title="<?= htmlspecialchars($sub_config['title']) ?>">
                                            <i class="<?= htmlspecialchars($sub_config['icon']) ?>"></i>
                                            <span><?= htmlspecialchars($sub_config['label']) ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                            <?php
                            $rendered_groups[] = $group_name;
                        }
                    }
                }
                if (!$is_in_group): ?>
                <li>
                    <a href="<?= isset($config['url']) ? htmlspecialchars($config['url']) : APP_URL . '/index.php?page=' . $key ?>"
                       class="<?= $key === $currentPageKey ? 'active' : '' ?>"
                       title="<?= htmlspecialchars($config['title']) ?>"
                       <?= isset($config['url']) ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <i class="<?= htmlspecialchars($config['icon']) ?>"></i>
                        <span><?= htmlspecialchars($config['label']) ?></span>
                    </a>
                </li>
                <?php endif;
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

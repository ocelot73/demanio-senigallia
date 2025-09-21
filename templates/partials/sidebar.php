<?php // /templates/partials/sidebar.php ?>
<aside id="sidebar">
    <div class="sidebar-header">
        <a href="index.php?page=concessioni">
            <img src="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" alt="Logo" class="logo">
            <span class="app-name">Demanio Marittimo</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php
            $pages_in_groups = [];
            foreach ($MENU_GROUPS as $group_info) {
                $pages_in_groups = array_merge($pages_in_groups, $group_info['pages']);
            }
            
            foreach ($PAGES as $page_key => $page_info):
                if (empty($page_info['hidden_from_menu']) && !in_array($page_key, $pages_in_groups)): ?>
                    <li class="<?= ($page_key === $page) ? 'active' : '' ?>">
                        <a href="<?= isset($page_info['url']) ? $page_info['url'] : 'index.php?page='.$page_key ?>" 
                           <?= isset($page_info['url']) ? 'target="_blank"' : '' ?>
                           title="<?= htmlspecialchars($page_info['title'] ?? $page_info['label']) ?>">
                            <i class="<?= $page_info['icon'] ?? 'fas fa-question-circle' ?>"></i>
                            <span class="link-text"><?= htmlspecialchars($page_info['label']) ?></span>
                        </a>
                    </li>
                <?php endif;
            endforeach;

            foreach ($MENU_GROUPS as $group_label => $group_info): ?>
                <li class="has-submenu">
                    <a href="#" class="submenu-toggle" title="<?= htmlspecialchars($group_label) ?>">
                        <i class="<?= $group_info['icon'] ?? 'fas fa-folder' ?>"></i>
                        <span class="link-text"><?= htmlspecialchars($group_label) ?></span>
                        <i class="fas fa-chevron-right arrow"></i>
                    </a>
                    <ul class="submenu">
                        <?php foreach ($group_info['pages'] as $page_key): 
                            if (isset($PAGES[$page_key])):
                                $page_info = $PAGES[$page_key]; ?>
                                 <li class="<?= ($page_key === $page) ? 'active' : '' ?>">
                                    <a href="index.php?page=<?= $page_key ?>" title="<?= htmlspecialchars($page_info['title'] ?? $page_info['label']) ?>">
                                        <i class="<?= $page_info['icon'] ?? 'fas fa-stream' ?>"></i>
                                        <span class="link-text"><?= htmlspecialchars($page_info['label']) ?></span>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <ul>
             <li>
                <a href="#" id="theme-toggle" title="Cambia Tema">
                    <i class="fas fa-moon"></i>
                    <span class="link-text">Tema Scuro</span>
                </a>
            </li>
            <li>
                <a href="?logout=1" title="Esci">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-text">Esci</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

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
            $canoni_group_rendered = false;
            $canoni_pages = $MENU_GROUPS['Canoni']['pages'] ?? [];
            $is_canoni_page_active = in_array($currentPageKey, $canoni_pages);

            foreach ($PAGES as $key => $config):
                $is_in_canoni_group = in_array($key, $canoni_pages);
                
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
                                <?php foreach($canoni_pages as $canoni_key):
                                    $canoni_config = $PAGES[$canoni_key]; ?>
                                <li>
                                    <a href="<?= build_current_url(['page' => $canoni_key], APP_URL . '/index.php') ?>" class="<?= $canoni_key === $currentPageKey ? 'active' : '' ?>" title="<?= htmlspecialchars($canoni_config['title']) ?>">
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
                        <a href="<?= isset($config['url']) ? htmlspecialchars($config['url']) : build_current_url(['page' => $key], APP_URL . '/index.php') ?>"
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
        <a href="<?= build_current_url(['logout' => 1], APP_URL . '/index.php') ?>"><i class="fas fa-sign-out-alt"></i><span class="link-text">Logout</span></a>
    </div>
</nav>
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

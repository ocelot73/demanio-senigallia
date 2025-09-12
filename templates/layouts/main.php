<?php // /templates/layouts/main.php ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageConfig['title'] ?? APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" type="image/webp">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- CORREZIONE: Aggiunta di jQuery UI per il drag&drop delle colonne -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
            const isFirstLoad = <?= json_encode($_SESSION['first_load_after_login'] ?? false) ?>;
            if (isFirstLoad) {
                localStorage.setItem('sidebarCollapsed', 'false');
                <?php $_SESSION['first_load_after_login'] = false; ?>
            }
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                 document.body.classList.add('sidebar-collapsed');
            }
        })();

        window.APP_URL = "<?= APP_URL ?>";
        window.FIELD_HELP_DATA = <?= json_encode($FIELD_HELP ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.hiddenColumnsData = <?= json_encode($hidden_columns ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="sidebar-collapsed">

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main id="main-content">
        
        <?php require __DIR__ . '/../partials/header.php'; ?>

        <?php
        $view_path = __DIR__ . '/../views/' . ($pageConfig['view'] ?? 'concessioni') . '.php';
        if (file_exists($view_path)) {
            require $view_path;
        } else {
            echo "<div class='card'><p>Errore: Vista non trovata: " . htmlspecialchars($pageConfig['view']) . "</p></div>";
        }
        ?>
        
    </main>

    <?php require __DIR__ . '/../partials/modals.php'; ?>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>

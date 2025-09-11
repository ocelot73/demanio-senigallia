<?php // /templates/layouts/main.php ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageConfig['title'] ?? APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" type="image/webp">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>

    <link rel="stylesheet" href="assets/css/style.css">
    
    <script>
        // Applica il tema prima del rendering per evitare flash
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
            const isFirstLoad = <?= json_encode($_SESSION['first_load_after_login'] ?? false) ?>;
            const sidebarState = localStorage.getItem('sidebarCollapsed');
            if (isFirstLoad) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                <?php $_SESSION['first_load_after_login'] = false; // Resetta il flag ?>
            } else if (sidebarState === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
        })();
    </script>
</head>
<body>

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main id="main-content">
        
        <?php require __DIR__ . '/../partials/header.php'; ?>

        <?php
        // Includi la vista specifica per la pagina corrente
        $view_path = __DIR__ . '/../' . ($pageConfig['view'] ?? 'concessioni') . '.php';
        if (file_exists($view_path)) {
            require $view_path;
        } else {
            echo "<div class='card-container'><p>Errore: Vista non trovata: " . htmlspecialchars($pageConfig['view']) . "</p></div>";
        }
        ?>
        
    </main>

    <?php require __DIR__ . '/../partials/modals.php'; ?>

    <script>
        // Passa le configurazioni da PHP a JavaScript
        const FIELD_HELP = <?= json_encode($FIELD_HELP ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const hiddenColumns = <?= json_encode($hidden_columns ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>

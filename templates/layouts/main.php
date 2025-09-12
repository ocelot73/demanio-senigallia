<?php // /templates/layouts/main.php ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageConfig['title'] ?? APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" type="image/webp">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    
    <script>
        // Applica il tema scuro prima del rendering per evitare flash
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();

        // Passa le variabili PHP a JavaScript
        window.APP_URL = "<?= APP_URL ?>";
        window.FIELD_HELP_DATA = <?= json_encode($FIELD_HELP ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.hiddenColumnsData = <?= json_encode($hidden_columns ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
    <script>
        // Logica per la gestione della sidebar, identica all'originale.
        // Eseguita subito dopo l'apertura del body per evitare "flickering".
        (function() {
            const isFirstLoad = <?= json_encode($_SESSION['first_load_after_login'] ?? false) ?>;
            const sidebarState = localStorage.getItem('sidebarCollapsed');

            if (isFirstLoad) {
                // Primo caricamento dopo il login: sidebar aperta e stato salvato.
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                <?php $_SESSION['first_load_after_login'] = false; ?>
            } else {
                // Caricamenti successivi: usa lo stato salvato o il default (chiuso).
                if (sidebarState === 'true') {
                    document.body.classList.add('sidebar-collapsed');
                } else if (sidebarState === 'false') {
                    document.body.classList.remove('sidebar-collapsed');
                } else {
                    // Se non c'è stato salvato, il default è chiuso.
                    document.body.classList.add('sidebar-collapsed');
                }
            }
        })();
    </script>

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main id="main-content">
        
        <?php require __DIR__ . '/../partials/header.php'; ?>

        <?php
        // CORREZIONE: Il percorso per le viste era errato. I file come 'concessioni.php'
        // si trovano direttamente in /templates/, non in una sottocartella /views/.
        $view_path = __DIR__ . '/../' . ($pageConfig['view'] ?? 'concessioni') . '.php';
        if (file_exists($view_path)) {
            require $view_path;
        } else {
            echo "<div class='card'><p>Errore: Vista non trovata nel percorso: " . htmlspecialchars($view_path) . "</p></div>";
        }
        ?>
        
    </main>

    <?php require __DIR__ . '/../partials/modals.php'; ?>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>


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

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>

    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                 document.documentElement.classList.add('dark-theme');
            }
        })();
        window.APP_URL = "<?= APP_URL ?>";
        window.FIELD_HELP_DATA = <?= json_encode($GLOBALS['FIELD_HELP'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
        window.hiddenColumnsData = <?= json_encode($_SESSION['hidden_columns'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="loading">
    <script>
        (function() {
            const isFirstLoad = <?= json_encode(isset($_SESSION['first_load_after_login']) && $_SESSION['first_load_after_login']) ?>;
            const sidebarState = localStorage.getItem('sidebarCollapsed');

            if (isFirstLoad) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                 <?php unset($_SESSION['first_load_after_login']); ?>
            } else {
                if (sidebarState === 'true') {
                    document.body.classList.add('sidebar-collapsed');
                }
            }
        })();
    </script>

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main id="main-content">
        <?php require __DIR__ . '/../partials/header.php'; ?>
        <div class="content-wrapper">
            <?php
            if(isset($data['error'])) {
                 echo "<div class='card-container glass-effect'><h2>Errore</h2><p>" . htmlspecialchars($data['error']) . "</p></div>";
            } else {
                $view_path = __DIR__ . '/../' . ($pageConfig['view'] ?? 'concessioni') . '.php';
                if (file_exists($view_path)) {
                    // Estrai le variabili dall'array $data per renderle disponibili nella vista
                    extract($data);
                    require $view_path;
                } else {
                    echo "<div class='card-container glass-effect'><h2>Errore 404</h2><p>Pagina non trovata.</p></div>";
                }
            }
            ?>
        </div>
    </main>
    
    <?php require __DIR__ . '/../partials/modals.php'; ?>
    
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <script>
        // Rimuovi la classe 'loading' dopo che tutto è stato caricato
        window.addEventListener('load', () => {
            document.body.classList.remove('loading');
        });
    </script>
</body>
</html>

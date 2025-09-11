<?php // public/index.php
session_start();

// Carica il core dell'applicazione
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';

// Gestione autenticazione (potrebbe essere una funzione in una libreria)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Logica di rendering per la pagina di login
    exit;
}

// Routing semplice
$currentPageKey = $_GET['page'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}

$pageConfig = $PAGES[$currentPageKey];
$data = [];

// Invocazione del controller corretto
switch ($currentPageKey) {
    case 'concessioni':
    case 'calcolo_canoni':
        require_once __DIR__ . '/../src/controllers/concessioni_controller.php';
        $data = get_concessioni_data($db_connection, $pageConfig);
        break;
    case 'importa':
        require_once __DIR__ . '/../src/controllers/import_controller.php';
        // La logica di importazione AJAX sarà gestita qui
        break;
    case 'scadenzario_solleciti':
        require_once __DIR__ . '/../src/controllers/solleciti_controller.php';
        $data = get_solleciti_data($db_connection);
        break;
    // ... altri casi
}

// Renderizza la pagina
render_page($currentPageKey, $pageConfig, $data);

<?php
// public/index.php
// Front Controller

ini_set('display_errors', 1); // Rimuovere in produzione
error_reporting(E_ALL);

session_start();

// 1. Caricamento Iniziale
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// 2. Gestione Richieste AJAX
// Se la richiesta è AJAX, viene gestita e lo script termina.
handle_ajax_request($FIELD_HELP);

// 3. Gestione Login e Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (($_POST['username'] ?? '') === 'demanio' && ($_POST['password'] ?? '') === 'demanio60019!') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'demanio';
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Username o password errati!';
    }
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    include __DIR__ . '/../templates/login.php';
    exit;
}


// 4. Routing e Selezione Pagina
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// 5. Invocazione Controller
$data = [];
$controllerPath = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? '');
if (!empty($pageConfig['controller']) && file_exists($controllerPath)) {
    require_once $controllerPath;
    
    // Determina la funzione del controller da chiamare (es. get_concessioni_data)
    $functionName = str_replace('_controller.php', '_data', $pageConfig['controller']);
    if(function_exists($functionName)) {
        $conn = get_db_connection();
        $data = $functionName($conn, $pageConfig);
        pg_close($conn);
    }
}

// 6. Rendering della Pagina
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

<?php
// /public/index.php

ini_set('display_errors', 1); // <-- AGGIUNTA PER DEBUG
error_reporting(E_ALL);      // <-- AGGIUNTA PER DEBUG

session_start();

// 1. Caricamento Iniziale
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// 2. Gestione Richieste AJAX (termina lo script se è una chiamata API)
handle_ajax_request($FIELD_HELP);

// Gestione logica per i filtri rapidi
if (isset($_GET['filter_type'])) {
    $filter_type = $_GET['filter_type'];
    $new_filters = [];
    switch ($filter_type) {
        case 'verifica_not_null_pec_null':
            $new_filters['verifica'] = 'NOT_NULL';
            $new_filters['pec inviata'] = 'NULL';
            break;
        case 'verifica_not_null_pec_not_null':
            $new_filters['verifica'] = 'NOT_NULL';
            $new_filters['pec inviata'] = 'NOT_NULL';
            break;
        case 'verifica_null_pec_null':
            $new_filters['verifica'] = 'NULL';
            $new_filters['pec inviata'] = 'NULL';
            break;
    }
    $_SESSION['column_filters'] = $new_filters;
    header('Location: ' . APP_URL . '/index.php?page=concessioni');
    exit;
}

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
        $_SESSION['first_load_after_login'] = true; // Flag per sidebar
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

// 5. Invocazione Controller per recuperare i dati della pagina
$data = [];
$controllerPath = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? '');
if (!empty($pageConfig['controller']) && file_exists($controllerPath)) {
    require_once $controllerPath;
    // Convenzione: nome_controller_data()
    $functionName = str_replace(['_controller.php', '.php'], '', basename($controllerPath)) . '_data';
    if (function_exists($functionName)) {
        $conn = get_db_connection();
        $data = $functionName($conn, $pageConfig);
        pg_close($conn);
    }
}

// 6. Rendering della pagina completa
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

<?php
// /public/index.php

ini_set('display_errors', 1); // Abilita la visualizzazione degli errori per il debug
error_reporting(E_ALL);

session_start();

// 1. Caricamento Iniziale
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// 2. Gestione Richieste AJAX
handle_ajax_request($FIELD_HELP);

// 3. Gestione Azioni (da bottoni e link)
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
$redirect_url = APP_URL . '/index.php?page=' . $currentPageKey;

// --- CORREZIONE: Applica i filtri rapidi SOLO se la pagina è 'concessioni' ---
if ($currentPageKey === 'concessioni' && isset($_GET['filter_type'])) {
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
    header('Location: ' . $redirect_url);
    exit;
}

// --- CORREZIONE: Aggiunta la logica per i bottoni dell'interfaccia ---
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['toggle_view'])) {
    $_SESSION['full_view'] = !($_SESSION['full_view'] ?? ($currentPageKey === 'concessioni'));
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['show_all'])) {
    $_SESSION['hidden_columns'] = [];
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['hide_all'])) {
    $conn = get_db_connection();
    $table = $PAGES[$currentPageKey]['table'] ?? null;
    if ($conn && $table) {
        $result = pg_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0");
        if ($result) {
            $all_columns = [];
            for ($i = 0; $i < pg_num_fields($result); $i++) $all_columns[] = pg_field_name($result, $i);
            $_SESSION['hidden_columns'] = $all_columns;
        }
        pg_close($conn);
    }
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['reset_order'])) {
    unset($_SESSION['column_order'], $_SESSION['column_widths']);
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['clear_filters'])) {
    $_SESSION['column_filters'] = [];
    header('Location: ' . $redirect_url);
    exit;
}

// 4. Gestione Login e Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_URL);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (($_POST['username'] ?? '') === 'demanio' && ($_POST['password'] ?? '') === 'demanio60019!') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'demanio';
        $_SESSION['first_load_after_login'] = true;
        header('Location: ' . APP_URL);
        exit;
    } else {
        $login_error = 'Username o password errati!';
    }
}
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    include __DIR__ . '/../templates/login.php';
    exit;
}

// 5. Routing e Selezione Pagina
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// 6. Invocazione Controller
$data = [];
$controllerPath = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? '');
if (!empty($pageConfig['controller']) && file_exists($controllerPath)) {
    require_once $controllerPath;
    $functionName = str_replace(['_controller.php', '.php'], '', basename($controllerPath)) . '_data';
    if (function_exists($functionName)) {
        $conn = get_db_connection();
        $data = $functionName($conn, $pageConfig);
        pg_close($conn);
    }
}

// 7. Rendering della pagina completa
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

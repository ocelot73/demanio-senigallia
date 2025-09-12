<?php
// /public/index.php

ini_set('display_errors', 1);
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

// 3. Selezione Pagina Corrente
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// [CORREZIONE] Helper per costruire URL preservando i parametri GET
function build_current_url($new_params = []) {
    $query_params = $_GET;
    foreach ($new_params as $key => $value) {
        if ($value === null) {
            unset($query_params[$key]);
        } else {
            $query_params[$key] = $value;
        }
    }
    // Rimuove parametri di azione per evitare loop
    unset($query_params['reset_view'], $query_params['toggle_view'], $query_params['show_all'], $query_params['hide_all'], $query_params['reset_order'], $query_params['clear_filters'], $query_params['filter_type']);
    
    return APP_URL . '/index.php?' . http_build_query($query_params);
}

// 4. Gestione Azioni GET con redirect corretto
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
    header('Location: ' . build_current_url());
    exit;
}
if (isset($_GET['toggle_view'])) {
    $_SESSION['full_view'] = !($_SESSION['full_view'] ?? ($currentPageKey === 'concessioni'));
    header('Location: ' . build_current_url());
    exit;
}
if (isset($_GET['show_all'])) {
    $_SESSION['hidden_columns'] = [];
    header('Location: ' . build_current_url());
    exit;
}
if (isset($_GET['hide_all']) && isset($pageConfig['table'])) {
    $conn = get_db_connection();
    $result = pg_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $pageConfig['table']) . " LIMIT 0");
    if ($result) {
        $all_columns = [];
        for ($i = 0; $i < pg_num_fields($result); $i++) $all_columns[] = pg_field_name($result, $i);
        $_SESSION['hidden_columns'] = $all_columns;
    }
    pg_close($conn);
    header('Location: ' . build_current_url());
    exit;
}
if (isset($_GET['reset_order'])) {
    unset($_SESSION['column_order'], $_SESSION['column_widths']);
    header('Location: ' . build_current_url());
    exit;
}
if (isset($_GET['clear_filters'])) {
    $_SESSION['column_filters'] = [];
    header('Location: ' . build_current_url());
    exit;
}
if ($currentPageKey === 'concessioni' && isset($_GET['filter_type'])) {
    $new_filters = [];
    switch ($_GET['filter_type']) {
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
    header('Location: ' . build_current_url(['p' => 1])); // Resetta alla prima pagina
    exit;
}


// 5. Gestione Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === 'demanio' && $_POST['password'] === 'demanio60019!') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'demanio';
        $_SESSION['first_load_after_login'] = true;
        header('Location: ' . APP_URL . '/index.php');
        exit;
    } else {
        $login_error = 'Username o password errati!';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}
if (!($_SESSION['logged_in'] ?? false)) {
    require __DIR__ . '/../templates/login.php';
    exit;
}


// 6. Preparazione Dati e Rendering
$conn = get_db_connection();
$data = [];
$controller_path = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? 'concessioni_controller.php');

if (file_exists($controller_path)) {
    require_once $controller_path;
    // La funzione nel controller (es. concessioni_data) prepara i dati
    $data_function_name = explode('_', $pageConfig['view'])[0] . '_data'; 
    if (function_exists($data_function_name)) {
        $data = $data_function_name($conn, $pageConfig);
    }
}

// Aggiungi dati globali necessari per la vista
$data['full_view'] = $_SESSION['full_view'] ?? ($currentPageKey === 'concessioni');

render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

if ($conn) {
    pg_close($conn);
}

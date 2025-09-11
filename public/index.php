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

// 2. Gestione Richieste AJAX (non blocca il caricamento della pagina)
handle_ajax_request($FIELD_HELP);

// 3. Selezione Pagina Corrente
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];
$base_redirect_url = APP_URL . '/index.php?page=' . $currentPageKey;

// 4. Gestione Azioni GET (Logica dei pulsanti replicata da index.php)
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
    header('Location: ' . $base_redirect_url);
    exit;
}
if (isset($_GET['toggle_view'])) {
    $_SESSION['full_view'] = !($_SESSION['full_view'] ?? ($currentPageKey === 'concessioni'));
    header('Location: ' . $base_redirect_url);
    exit;
}
if (isset($_GET['show_all'])) {
    $_SESSION['hidden_columns'] = [];
    header('Location: ' . $base_redirect_url);
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
    header('Location: ' . $base_redirect_url);
    exit;
}
if (isset($_GET['reset_order'])) {
    unset($_SESSION['column_order'], $_SESSION['column_widths']);
    header('Location: ' . $base_redirect_url);
    exit;
}
if (isset($_GET['clear_filters'])) {
    $_SESSION['column_filters'] = [];
    header('Location: ' . $base_redirect_url);
    exit;
}
if ($currentPageKey === 'concessioni' && isset($_GET['filter_type'])) {
    $new_filters = [];
    switch ($_GET['filter_type']) {
        case 'verifica_not_null_pec_null':

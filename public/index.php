<?php
// /public/index.php

session_start();

// 1. Caricamento Iniziale
require_once __DIR__ . '/../config/config.php'; [cite: 218, 583]
require_once __DIR__ . '/../config/pages.php'; [cite: 218, 583]
require_once __DIR__ . '/../src/lib/database.php'; [cite: 218, 583]
require_once __DIR__ . '/../src/lib/template.php'; [cite: 218, 583]
require_once __DIR__ . '/../src/lib/request_handler.php'; [cite: 218, 583]

// 2. Gestione Richieste AJAX (termina lo script se è una chiamata API)
handle_ajax_request($FIELD_HELP); [cite: 219, 584]

// --- CORREZIONE: Aggiunta logica per i filtri rapidi ---
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
    session_destroy(); [cite: 220, 585]
    header('Location: index.php'); [cite: 220, 585]
    exit; [cite: 220, 585]
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (($_POST['username'] ?? '') === 'demanio' && ($_POST['password'] ?? '') === 'demanio60019!') {
        $_SESSION['logged_in'] = true; [cite: 221, 586]
        $_SESSION['username'] = 'demanio'; [cite: 221, 586]
        $_SESSION['first_load_after_login'] = true; // Flag per sidebar [cite: 222, 587]
        header('Location: index.php'); [cite: 222, 587]
        exit; [cite: 222, 587]
    } else {
        $login_error = 'Username o password errati!'; [cite: 223, 588]
    }
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    include __DIR__ . '/../templates/login.php'; [cite: 224, 589]
    exit; [cite: 224, 589]
}

// 4. Routing e Selezione Pagina
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni'; [cite: 225, 590]
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni'; [cite: 225, 590]
}
$_SESSION['current_page_key'] = $currentPageKey; [cite: 226, 591]
$pageConfig = $PAGES[$currentPageKey]; [cite: 226, 591]

// 5. Invocazione Controller per recuperare i dati della pagina
$data = []; [cite: 227, 592]
$controllerPath = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? ''); [cite: 227, 592]
if (!empty($pageConfig['controller']) && file_exists($controllerPath)) {
    require_once $controllerPath; [cite: 228, 593]
    // Convenzione: nome_controller_data() [cite: 229, 594]
    $functionName = str_replace(['_controller.php', '.php'], '', basename($controllerPath)) . '_data'; [cite: 229, 594]
    if (function_exists($functionName)) {
        $conn = get_db_connection(); [cite: 230, 595]
        $data = $functionName($conn, $pageConfig); [cite: 230, 595]
        pg_close($conn); [cite: 230, 595]
    }
}

// 6. Rendering della pagina completa
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS); [cite: 231, 596]

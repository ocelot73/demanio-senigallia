<?php
// /public/index.php

session_start();

// --- Caricamento Configurazioni e Librerie ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php'; // Contiene $PAGES e $MENU_GROUPS
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';


// --- Gestione Login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password_input = $_POST['password'] ?? '';
    if ($username === 'demanio' && $password_input === 'demanio60019!') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['first_load_after_login'] = true;
        header('Location: ' . APP_URL . '/index.php');
        exit;
    } else {
        $login_error = 'Username o password errati!';
        require __DIR__ . '/../templates/login.php';
        exit;
    }
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require __DIR__ . '/../templates/login.php';
    exit;
}

// --- [CODICE CORRETTO] Gestione Azioni Globali via GET ---
// Questo blocco replica la logica di gestione dei pulsanti della tabella
// presente nel file index.php originale.
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}
if (isset($_GET['show_all'])) {
    $_SESSION['hidden_columns'] = [];
    $_SESSION['column_filters'] = [];
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
if (isset($_GET['hide_all'])) {
    // Per nascondere tutto, abbiamo bisogno delle colonne della tabella corrente
    // Questa logica è complessa da inserire qui, ma possiamo semplicemente resettare a un array vuoto
    // o assumere che il controller fornirà le colonne. Per ora, implementiamo un reset.
    // Una soluzione più robusta richiederebbe una chiamata al modello.
    // L'originale faceva una query, ma qui cerchiamo di tenerlo nel controller.
    // Per ora, l'azione resetta i filtri. L'azione completa richiede il contesto della tabella.
    // Per replicare, si può fare una query qui, anche se non è "pulito".
    $conn_temp = get_db_connection();
    $current_page_key_temp = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
    $table_temp = $PAGES[$current_page_key_temp]['table'] ?? null;
    if ($conn_temp && $table_temp) {
        pg_query($conn_temp, "SET search_path TO " . pg_escape_string($conn_temp, DB_SCHEMA));
        $result = pg_query($conn_temp, "SELECT * FROM " . pg_escape_identifier($conn_temp, $table_temp) . " LIMIT 0");
        if ($result) {
            $all_columns = [];
            for ($i = 0; $i < pg_num_fields($result); $i++) {
                $all_columns[] = pg_field_name($result, $i);
            }
            $_SESSION['hidden_columns'] = $all_columns;
            $_SESSION['column_filters'] = [];
        }
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
if (isset($_GET['reset_order'])) {
    unset($_SESSION['column_order'], $_SESSION['column_widths']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
if (isset($_GET['toggle_view'])) {
    $_SESSION['full_view'] = !isset($_SESSION['full_view']) || !$_SESSION['full_view'];
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
if (isset($_GET['clear_filters'])) {
    if (!empty($_SESSION['column_filters'] ?? [])) {
        $_SESSION['column_filters'] = [];
    }
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    $query_params = $_GET;
    unset($query_params['clear_filters']);
    $redirect_url = $current_url . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
    header('Location: ' . $redirect_url);
    exit;
}
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
// Gestione filtri rapidi per la pagina concessioni
if (isset($_GET['filter_type'])) {
    $filter_type = $_GET['filter_type'];
    $new_filters = [];
    $current_page_key_filter = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
    if ($current_page_key_filter === 'concessioni') {
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
    }
    $_SESSION['column_filters'] = $new_filters;
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}


// --- Gestione Richieste AJAX ---
// La funzione `handle_ajax_request` gestisce tutte le chiamate POST/AJAX e termina l'esecuzione.
handle_ajax_request($FIELD_HELP);


// --- Routing Principale ---
$pageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($pageKey, $PAGES)) {
    $pageKey = 'concessioni'; // Pagina di fallback
}
$_SESSION['current_page_key'] = $pageKey;
$pageConfig = $PAGES[$pageKey];


// --- Connessione al Database ---
$conn = get_db_connection();


// --- Esecuzione del Controller ---
$controllerFile = __DIR__ . '/../src/controllers/' . $pageConfig['controller'];
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    // Determina la funzione del controller da chiamare (es. 'concessioni' -> 'concessioni_data')
    $controllerFunctionName = str_replace('_controller.php', '_data', $pageConfig['controller']);
    if (function_exists($controllerFunctionName)) {
        $data = $controllerFunctionName($conn, $pageConfig);
    } else {
        die("Errore: Funzione controller '{$controllerFunctionName}' non trovata.");
    }
} else {
    die("Errore: Controller '{$pageConfig['controller']}' non trovato.");
}


// --- Rendering della Pagina ---
render_page($pageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

// --- Chiusura Connessione ---
if ($conn) {
    pg_close($conn);
}

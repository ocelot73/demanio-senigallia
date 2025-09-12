<?php
// /public/index.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// 1) Bootstrap
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// 3) Determinazione pagina corrente
$currentPageKey = $_GET['page'] ?? ($_SESSION['current_page_key'] ?? 'concessioni');
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// Utility redirect URL per la pagina corrente
$base_redirect_url = APP_URL . '/index.php?page=' . urlencode($currentPageKey);

// 4) Azioni GET che mutano lo stato della vista (replica 1:1 della logica originale)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

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

if (isset($_GET['hide_all'])) {
    if (!empty($pageConfig['table'])) {
        $conn = get_db_connection();
        $q = sprintf('SELECT * FROM %s LIMIT 0', pg_escape_identifier($conn, $pageConfig['table']));
        $result = @pg_query($conn, $q);
        if ($result) {
            $all_columns = [];
            for ($i = 0; $i < pg_num_fields($result); $i++) {
                $all_columns[] = pg_field_name($result, $i);
            }
            $_SESSION['hidden_columns'] = $all_columns;
            pg_free_result($result);
        }
        pg_close($conn);
    }
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

// Filtri rapidi (verifica/PEC) come nell'originale index.php
if (isset($_GET['filter_type'])) {
    $new_filters = [];
    if ($currentPageKey === 'concessioni') {
        $ft = $_GET['filter_type'];
        if ($ft === 'verifica_not_null_pec_null') {
            $new_filters['verifica'] = 'NOT_NULL'; $new_filters['pec inviata'] = 'NULL';
        } elseif ($ft === 'verifica_not_null_pec_not_null') {
            $new_filters['verifica'] = 'NOT_NULL'; $new_filters['pec inviata'] = 'NOT_NULL';
        } elseif ($ft === 'verifica_null_pec_null') {
            $new_filters['verifica'] = 'NULL';     $new_filters['pec inviata'] = 'NULL';
        }
    }
    $_SESSION['column_filters'] = $new_filters;
    header('Location: ' . $base_redirect_url);
    exit;
}

// Esportazione CSV (rispetta filtri, ordinamento, colonne nascoste e vista parziale/completa)
if (isset($_GET['export_csv']) && !empty($pageConfig['table'])) {
    require_once __DIR__ . '/../src/models/concessione.php';

    $conn = get_db_connection();

    // Per "Calcolo Canoni" imposta il parametro di sessione del DB per l'anno
    if (($pageConfig['table'] ?? '') === 'calcolo_canoni_v') {
        $selected_year = $_GET['anno'] ?? date('Y');
        @pg_query($conn, "SELECT set_config('demanio.anno', " . pg_escape_literal($conn, (string)$selected_year) . ", false)");
    }

    $table = $pageConfig['table'];
    $hidden_columns = $_SESSION['hidden_columns'] ?? [];
    $column_filters = $_SESSION['column_filters'] ?? [];
    $full_view = $_SESSION['full_view'] ?? ($currentPageKey === 'concessioni');
    $page = max(1, (int)($_GET['p'] ?? 1));
    $offset = ($page - 1) * RECORDS_PER_PAGE;
    $order_column = $_GET['order'] ?? 'denominazione ditta concessionario';
    $order_direction = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    // Recupero elenco colonne e tipi
    $res = @pg_query($conn, 'SELECT * FROM ' . pg_escape_identifier($conn, $table) . ' LIMIT 0');
    if (!$res) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Errore nella lettura delle colonne di tabella.";
        exit;
    }
    $all_columns = [];
    for ($i = 0; $i < pg_num_fields($result); $i++) {
        $all_columns[] = pg_field_name($result, $i);
    }
    pg_free_result($res);

    // Ordine colonne personalizzato (se mai implementato)
    if (isset($_SESSION['column_order']) && is_array($_SESSION['column_order'])) {
        $ordered = [];
        foreach ($_SESSION['column_order'] as $c) {
            if (in_array($c, $all_columns, true)) $ordered[] = $c;
        }
        foreach ($all_columns as $c) {
            if (!in_array($c, $ordered, true)) $ordered[] = $c;
        }
        $columns = $ordered;
    } else {
        $columns = $all_columns;
    }

    $visible_columns = array_values(array_diff($columns, $hidden_columns));

    // Clausola WHERE per i filtri
    list($where_clause, $params) = build_filter_where_clause($conn, $column_filters);
    $query = 'SELECT * FROM ' . pg_escape_identifier($conn, $table);
    if (!empty($where_clause)) $query .= ' WHERE ' . $where_clause;
    if (!empty($order_column) && in_array($order_column, $columns, true)) {
        $qcol = pg_escape_identifier($conn, $order_column);
        $query .= ' ORDER BY ' . $qcol . ' ' . $order_direction . ' NULLS LAST';
    }

    if (!$full_view) {
        $query .= ' LIMIT ' . intval(RECORDS_PER_PAGE) . ' OFFSET ' . intval($offset);
    }

    $result = @pg_query_params($conn, $query, $params);
    if (!$result) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Errore nella query di esportazione.";
        exit;
    }

    $filename = "concessioni_export_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // BOM UTF-8 per Excel
    echo "\xEF\xBB\xBF";
    // Intestazioni
    if (!empty($visible_columns)) {
        $headers = array_map(function($h){
            return str_contains($h, ';') ? '"' . str_replace('"', '""', $h) . '"' : $h;
        }, $visible_columns);
        echo implode(';', $headers) . "\n";
    }

    // Righe
    while ($row = pg_fetch_assoc($result)) {
        $csv_row = [];
        foreach ($columns as $col) {
            if (in_array($col, $hidden_columns, true)) continue;
            $value = $row[$col] ?? '';
            // Formattazioni principali come nell'originale (semplificate)
            if (is_numeric($value)) {
                // Usa formattazione italiana 1.234,56
                $value = number_format((float)$value, 2, ',', '.');
            } else {
                // Normalizza eventuali \r\n
                $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
            }
            // Quote se contiene separatore o newline o doppi apici
            if (str_contains($value, ';') || str_contains($value, '"') || str_contains($value, "\n")) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $csv_row[] = $value;
        }
        echo implode(';', $csv_row) . "\n";
    }
    pg_free_result($result);
    pg_close($conn);
    exit;
}

// 5) Autenticazione (login semplice come nell'originale)
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password_input = $_POST['password'] ?? '';
    if ($username === 'demanio' && $password_input === 'demanio60019!') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['first_load_after_login'] = true;
        header('Location: ' . $base_redirect_url);
        exit;
    } else {
        $login_error = 'Username o password errati!';
    }
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require __DIR__ . '/../templates/login.php';
    exit;
}

// 6) Controller -> Dati -> Render
// Apri connessione se la pagina richiede dati da tabella
$conn = null;
if (!empty($pageConfig['table']) || ($pageConfig['view'] ?? '') === 'scadenzario') {
    $conn = get_db_connection();
    // Per "Calcolo Canoni" imposta l'anno (coerente con esportazione)
    if (($pageConfig['table'] ?? '') === 'calcolo_canoni_v') {
        $selected_year = $_GET['anno'] ?? date('Y');
        @pg_query($conn, "SELECT set_config('demanio.anno', " . pg_escape_literal($conn, (string)$selected_year) . ", false)");
    }
}

// Carica il controller specifico
$data = [];
if (!empty($pageConfig['controller'])) {
    $controller_path = __DIR__ . '/../src/controllers/' . $pageConfig['controller'];
    if (file_exists($controller_path)) require_once $controller_path;

    // Mappa controller->funzione
    $function_name = str_replace('_controller.php', '_data', $pageConfig['controller']);
    if (function_exists($function_name)) {
        $data = $function_name($conn, $pageConfig);
    }
}

// Render pagina
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS, $FIELD_HELP);

// Pulizia
if ($conn) {
    @pg_close($conn);
}

<?php
// /public/index.php

session_start();

// --- Caricamento Configurazioni e Librerie ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
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

// --- Gestione Redirect Legacy (da page_view a page) ---
if (isset($_GET['page_view'])) {
    $_GET['page'] = $_GET['page_view'];
    unset($_GET['page_view']);
    if ($_GET['page'] === 'concessioni_f24') {
        $_GET['page'] = 'concessioni';
    }
    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
    $new_url = $base_url . (!empty($_GET) ? '?' . http_build_query($_GET) : '');
    header('Location: ' . $new_url, true, 301);
    exit;
}

// --- Determina la pagina corrente prima di ogni altra logica ---
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni'; // Pagina di fallback
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// ==========================================================
// --- [BLOCCO LOGICA IMPORTAZIONE DATI SID] ---
// ==========================================================
if ($currentPageKey === 'importa') {
    $host_import = DB_HOST; $port_import = DB_PORT; $dbname_import = DB_NAME; $user_import = "sitadm"; $password_import = "Superuser60019!"; $schema_import = DB_SCHEMA;
    if (!function_exists('getUploadError')) { function getUploadError($errorCode) { $errors = [ UPLOAD_ERR_INI_SIZE => 'Il file supera la dimensione massima consentita.', UPLOAD_ERR_FORM_SIZE => 'Il file supera la dimensione massima del form.', UPLOAD_ERR_PARTIAL => 'Caricamento parziale.', UPLOAD_ERR_NO_FILE => 'Nessun file caricato.', UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante.', UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file.', UPLOAD_ERR_EXTENSION => 'Estensione PHP ha bloccato il caricamento.', ]; return $errors[$errorCode] ?? 'Errore sconosciuto (' . $errorCode . ')'; } }
    if (!function_exists('formatBytes')) { function formatBytes($bytes, $precision = 2) { if ($bytes <= 0) return '0 B'; $units = ['B', 'KB', 'MB', 'GB', 'TB']; $pow = floor(log($bytes) / log(1024)); return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow]; } }
    if (!function_exists('findJsonFile')) { function findJsonFile($directory) { foreach (scandir($directory) as $file) { if (pathinfo($file, PATHINFO_EXTENSION) === 'json') return $file; } return false; } }
    if (!function_exists('deleteDirectory')) { function deleteDirectory($dir) { if (!file_exists($dir)) return true; if (!is_dir($dir)) return unlink($dir); foreach (scandir($dir) as $item) { if ($item == '.' || $item == '..') continue; if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false; } return rmdir($dir); } }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) { header('Content-Type: application/json'); $processId = uniqid('sid_import_', true); $workDir = sys_get_temp_dir() . '/' . $processId; if (!mkdir($workDir, 0700, true)) { echo json_encode(['success' => false, 'error' => 'Impossibile creare la directory di lavoro.']); exit; } file_put_contents($workDir . '/process_info.json', json_encode(['start_time' => time(), 'file_name' => $_FILES['zipfile']['name']])); $zipPath = $workDir . '/' . basename($_FILES['zipfile']['name']); if (!move_uploaded_file($_FILES['zipfile']['tmp_name'], $zipPath)) { echo json_encode(['success' => false, 'error' => getUploadError($_FILES['zipfile']['error'])]); deleteDirectory($workDir); exit; } echo json_encode(['success' => true, 'processId' => $processId]); exit; }
    if (isset($_GET['action']) && $_GET['action'] === 'process' && isset($_GET['id'])) { date_default_timezone_set('Europe/Rome'); header('Content-Type: text/event-stream'); header('Cache-Control: no-cache'); header('Connection: keep-alive'); header('X-Accel-Buffering: no'); while (ob_get_level() > 0) ob_end_clean(); function send_sse($event, $data) { echo "event: $event\n"; echo "data: " . json_encode($data) . "\n\n"; if (function_exists('ob_flush')) ob_flush(); flush(); } $processId = $_GET['id']; $workDir = sys_get_temp_dir() . '/' . $processId; send_sse('log', ['status' => 'info', 'message' => 'Inizializzazione processo...']); if (!file_exists($workDir . '/process_info.json')) { send_sse('close', ['status' => 'error', 'message' => 'Processo non valido']); exit; } $fileName = json_decode(file_get_contents($workDir . '/process_info.json'), true)['file_name'] ?? ''; $zipPath = $workDir . '/' . $fileName; send_sse('progress', ['value' => 10, 'text' => 'Fase 1/5: File verificato']); send_sse('log', ['status' => 'success', 'message' => 'File ZIP verificato: ' . htmlspecialchars($fileName)]); send_sse('progress', ['value' => 15, 'text' => 'Fase 2/5: Estrazione...']); $zip = new ZipArchive; $zip->open($zipPath); $zip->extractTo($workDir); $zip->close(); unlink($zipPath); send_sse('progress', ['value' => 40, 'text' => 'Fase 2/5: Estrazione completata']); send_sse('log', ['status' => 'success', 'message' => 'Estrazione completata.']); send_sse('progress', ['value' => 45, 'text' => 'Fase 3/5: Conversione...']); $jsonFile = findJsonFile($workDir); if (!$jsonFile) { send_sse('close', ['status' => 'error', 'message' => 'File .json non trovato']); exit; } $jsonFilePath = $workDir . '/' . $jsonFile; send_sse('log', ['status' => 'success', 'message' => 'File dati trovato: ' . htmlspecialchars($jsonFile)]); $convertedJsonPath = $workDir . '/demanio.json'; exec("jq -c '.[]' " . escapeshellarg($jsonFilePath) . " > " . escapeshellarg($convertedJsonPath), $output, $returnCode); if ($returnCode !== 0) { send_sse('close', ['status' => 'error', 'message' => 'Conversione dati fallita']); exit; } send_sse('log', ['status' => 'success', 'message' => 'Conversione formato JSON completata.']); $pgTargetPath = '/var/lib/postgresql/demanio.json'; exec("sudo /bin/mv " . escapeshellarg($convertedJsonPath) . " " . escapeshellarg($pgTargetPath), $output, $returnCode); if ($returnCode !== 0) { send_sse('close', ['status' => 'error', 'message' => 'Impossibile preparare i dati']); exit; } send_sse('progress', ['value' => 70, 'text' => 'Fase 3/5: Dati preparati']); send_sse('log', ['status' => 'success', 'message' => 'File dati posizionato per PostgreSQL.']); send_sse('progress', ['value' => 75, 'text' => 'Fase 4/5: Aggiornamento DB...']); $conn_import = pg_connect("host=$host_import port=$port_import dbname=$dbname_import user=$user_import password=$password_import"); if (!$conn_import) { send_sse('close', ['status' => 'error', 'message' => 'Connessione DB fallita']); exit; } pg_query($conn_import, "SET search_path TO " . pg_escape_identifier($conn_import, $schema_import)); send_sse('log', ['status' => 'success', 'message' => 'Connessione al DB stabilita.']); $result = pg_query($conn_import, "SELECT matviewname FROM pg_matviews WHERE schemaname=" . pg_escape_literal($conn_import, $schema_import) . " ORDER BY matviewname"); $views = pg_fetch_all_columns($result, 0) ?: []; $totalViews = count($views); send_sse('log', ['status' => 'info', 'message' => "Trovate $totalViews viste. Inizio aggiornamento..."]); $errorCount = 0; foreach ($views as $index => $view) { send_sse('progress', ['value' => 85 + (($index + 1) / $totalViews) * 13, 'text' => "Fase 4/5: Aggiornamento vista " . ($index + 1) . "/$totalViews..."]); if (!pg_query($conn_import, "REFRESH MATERIALIZED VIEW " . pg_escape_identifier($conn_import, $schema_import) . "." . pg_escape_identifier($conn_import, $view))) { $errorCount++; } } pg_close($conn_import); send_sse('progress', ['value' => 98, 'text' => 'Fase 5/5: Pulizia...']); deleteDirectory($workDir); send_sse('log', ['status' => 'info', 'message' => 'Pulizia completata.']); if ($errorCount > 0) { send_sse('close', ['status' => 'warning', 'message' => "Completato con $errorCount errori"]); } else { send_sse('close', ['status' => 'success', 'message' => 'Importazione completata']); } exit; }
}

// --- [INIZIO CORREZIONE]: Reintegrazione della funzionalità di Esportazione CSV ---
if (isset($_GET['export_csv'])) {
    $conn = get_db_connection();
    $table = $pageConfig['table'] ?? 'concessioni_unione_v';
    $full_view_export = $_SESSION['full_view'] ?? ($currentPageKey === 'concessioni');

    if ($currentPageKey === 'calcolo_canoni') {
        $selected_year = $_SESSION['selected_year'] ?? date('Y');
        @pg_query($conn, "SELECT set_config('demanio.anno', '" . pg_escape_string($conn, (string)$selected_year) . "', false)");
    }
    
    // Funzione helper locale per la clausola WHERE, identica all'originale per fedeltà 1:1
    function buildFilterWhereClause_forExport($conn, $column_filters, $columns, $column_types) {
        $where = []; $params = []; $i = 1;
        foreach ($column_filters as $col => $value) {
            if (!empty($value) && in_array($col, $columns)) {
                $c = pg_escape_identifier($conn, $col);
                $t = strtolower($column_types[$col] ?? '');
                if ($value === 'NULL') $where[] = "$c IS NULL";
                elseif ($value === 'NOT_NULL') $where[] = "$c IS NOT NULL";
                else {
                    $op = (in_array($t, ['text','varchar','bpchar'])) ? 'ILIKE' : 'LIKE';
                    $where[] = "CAST($c AS TEXT) $op $" . $i; $params[] = '%' . $value . '%'; $i++;
                }
            }
        }
        return [implode(" AND ", $where), $params];
    }

    $result_meta = pg_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0");
    $all_columns = []; $column_types = [];
    for ($i = 0; $i < pg_num_fields($result_meta); $i++) {
        $col_name = pg_field_name($result_meta, $i);
        $all_columns[] = $col_name;
        $column_types[$col_name] = pg_field_type($result_meta, $i);
    }

    $saved_order = $_SESSION['column_order'] ?? [];
    $columns = array_values(array_intersect($saved_order, $all_columns));
    foreach ($all_columns as $c) {
        if (!in_array($c, $columns)) $columns[] = $c;
    }

    $hidden_columns = $_SESSION['hidden_columns'] ?? [];
    $visible_columns = array_diff($columns, $hidden_columns);
    list($where_clause, $filter_params) = buildFilterWhereClause_forExport($conn, $_SESSION['column_filters'] ?? [], $all_columns, $column_types);
    
    $query = "SELECT " . (empty($visible_columns) ? '*' : '"' . implode('", "', array_map('pg_escape_identifier', $visible_columns)) . '"') . " FROM " . pg_escape_identifier($conn, $table);
    if (!empty($where_clause)) $query .= " WHERE $where_clause";

    $order_column = $_GET['order'] ?? (in_array('denominazione ditta concessionario', $all_columns) ? 'denominazione ditta concessionario' : ($all_columns[0] ?? ''));
    $order_direction = $_GET['dir'] ?? 'ASC';
    if (!empty($order_column) && in_array($order_column, $all_columns)) {
        $qcol = pg_escape_identifier($conn, $order_column);
        $qdir = strtoupper($order_direction) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $qcol $qdir NULLS LAST";
    }

    if (!$full_view_export) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * RECORDS_PER_PAGE;
        $query .= " LIMIT " . intval(RECORDS_PER_PAGE) . " OFFSET " . intval($offset);
    }
    
    $result = pg_query_params($conn, $query, $filter_params);
    if (!$result) die("Errore nella query di esportazione: " . pg_last_error($conn));

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="export_' . $currentPageKey . '_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM for UTF-8 Excel compatibility
    $out = fopen('php://output', 'w');
    fputcsv($out, $visible_columns, ';');
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}
// --- [FINE CORREZIONE] ---

// --- Gestione Azioni Globali via GET ---
if (isset($_GET['logout'])) { session_destroy(); header('Location: ' . APP_URL . '/index.php'); exit; }
if (isset($_GET['show_all'])) { $_SESSION['hidden_columns'] = []; $_SESSION['column_filters'] = []; header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_GET['hide_all'])) { $conn_temp = get_db_connection(); $table_temp = $PAGES[$currentPageKey]['table'] ?? null; if ($conn_temp && $table_temp) { $result = pg_query($conn_temp, "SELECT * FROM " . pg_escape_identifier($conn_temp, $table_temp) . " LIMIT 0"); if ($result) { $all_columns = []; for ($i = 0; $i < pg_num_fields($result); $i++) { $all_columns[] = pg_field_name($result, $i); } $_SESSION['hidden_columns'] = $all_columns; $_SESSION['column_filters'] = []; } } header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_GET['reset_order'])) { unset($_SESSION['column_order'], $_SESSION['column_widths']); header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_GET['toggle_view'])) { $_SESSION['full_view'] = !isset($_SESSION['full_view']) || !$_SESSION['full_view']; header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_GET['clear_filters'])) { if (!empty($_SESSION['column_filters'] ?? [])) { $_SESSION['column_filters'] = []; } $current_url = strtok($_SERVER["REQUEST_URI"], '?'); $query_params = $_GET; unset($query_params['clear_filters']); $redirect_url = $current_url . (!empty($query_params) ? '?' . http_build_query($query_params) : ''); header('Location: ' . $redirect_url); exit; }
if (isset($_GET['reset_view'])) { unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']); header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_GET['filter_type'])) { $filter_type = $_GET['filter_type']; $new_filters = []; if ($currentPageKey === 'concessioni') { switch ($filter_type) { case 'verifica_not_null_pec_null': $new_filters['verifica'] = 'NOT_NULL'; $new_filters['pec inviata'] = 'NULL'; break; case 'verifica_not_null_pec_not_null': $new_filters['verifica'] = 'NOT_NULL'; $new_filters['pec inviata'] = 'NOT_NULL'; break; case 'verifica_null_pec_null': $new_filters['verifica'] = 'NULL'; $new_filters['pec inviata'] = 'NULL'; break; } } $_SESSION['column_filters'] = $new_filters; header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); exit; }

// --- Gestione Richieste AJAX ---
handle_ajax_request($FIELD_HELP);

// --- Routing e Preparazione Dati ---
$conn = get_db_connection();
$controllerFile = __DIR__ . '/../src/controllers/' . ($pageConfig['controller'] ?? '');
$data = [];
if (isset($pageConfig['controller']) && file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerFunctionName = str_replace('.php', '_data', basename($pageConfig['controller']));
    if (function_exists($controllerFunctionName)) {
        $data = $controllerFunctionName($conn, $pageConfig);
    }
}

// --- Rendering della Pagina ---
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

// --- Chiusura Connessione ---
if ($conn) {
    pg_close($conn);
}

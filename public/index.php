<?php
// =====================================================================
// FILE: /public/index.php (COMPLETO E CORRETTO)
// =====================================================================

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Configurazione e Inclusione File Essenziali ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/helpers.php';
require_once __DIR__ . '/../src/lib/request_handler.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/template.php';

// --- Gestione Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_form.php");
    exit;
}

// --- Gestione Login ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login_form.php");
    exit;
}

// --- Gestione Richieste AJAX ---
handle_ajax_request($FIELD_HELP);

// --- Logica di Importazione (con correzione messaggio) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    header('Content-Type: application/json');
    $uploadDir = sys_get_temp_dir();
    $processId = uniqid('import_');
    $workDir = $uploadDir . '/' . $processId;
    if (!mkdir($workDir, 0777, true)) {
        echo json_encode(['success' => false, 'error' => 'Impossibile creare la directory di lavoro.']);
        exit;
    }
    $fileName = basename($_FILES['zipfile']['name']);
    $targetPath = $workDir . '/' . $fileName;
    if (move_uploaded_file($_FILES['zipfile']['tmp_name'], $targetPath)) {
        file_put_contents($workDir . '/process_info.json', json_encode(['file_name' => $fileName]));
        echo json_encode(['success' => true, 'processId' => $processId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Caricamento del file fallito.']);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'process' && isset($_GET['id'])) {
    date_default_timezone_set('Europe/Rome');
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) ob_end_clean();
    function send_sse($event, $data) {
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (function_exists('ob_flush')) ob_flush();
        flush();
    }

    $processId = $_GET['id'];
    $workDir = sys_get_temp_dir() . '/' . $processId;
    if (!file_exists($workDir . '/process_info.json')) {
        send_sse('close', ['status' => 'error', 'message' => 'Processo non valido']);
        exit;
    }
    
    $processInfo = json_decode(file_get_contents($workDir . '/process_info.json'), true);
    $fileName = $processInfo['file_name'] ?? '';
    $zipPath = $workDir . '/' . $fileName;

    send_sse('progress', ['value' => 10, 'text' => 'Fase 1/6: File Verificato']);
    send_sse('log', ['status' => 'info', 'message' => 'File ZIP verificato: ' . htmlspecialchars($fileName)]);
    send_sse('progress', ['value' => 15, 'text' => 'Fase 2/6: Estrazione...']);
    $zip = new ZipArchive;
    if ($zip->open($zipPath) === TRUE) {
        $zip->extractTo($workDir);
        $zip->close();
        unlink($zipPath);
        send_sse('log', ['status' => 'success', 'message' => 'Estrazione completata.']);
    } else {
        send_sse('close', ['status' => 'error', 'message' => 'Impossibile aprire il file ZIP.']);
        exit;
    }
    
    send_sse('progress', ['value' => 30, 'text' => 'Fase 3/6: Preparazione Dati...']);
    $jsonFile = findJsonFile($workDir);
    if (!$jsonFile) { send_sse('close', ['status' => 'error', 'message' => 'File .json non trovato nell\'archivio']); exit; }

    $jsonFilePath = $workDir . '/' . $jsonFile;
    $convertedJsonPath = $workDir . '/demanio.json';
    exec("jq -c '.[]' " . escapeshellarg($jsonFilePath) . " > " . escapeshellarg($convertedJsonPath), $output, $returnCode);
    if ($returnCode !== 0) { send_sse('close', ['status' => 'error', 'message' => 'Conversione dati fallita (jq).']); exit; }
    
    $pgTargetPath = '/var/lib/postgresql/demanio.json';
    exec("sudo /bin/mv " . escapeshellarg($convertedJsonPath) . " " . escapeshellarg($pgTargetPath), $output, $returnCode);
    if ($returnCode !== 0) { send_sse('close', ['status' => 'error', 'message' => 'Spostamento dati fallito. Controllare i permessi sudo.']); exit; }
    send_sse('log', ['status' => 'success', 'message' => 'Dati preparati per PostgreSQL.']);
    send_sse('progress', ['value' => 55, 'text' => 'Fase 4/5: Aggiornamento Viste SID...']);
    $conn_import = pg_connect("host=$host_import port=$port_import dbname=$dbname_import user=$user_import password=$password_import");
    if (!$conn_import) { send_sse('close', ['status' => 'error', 'message' => 'Connessione DB per import fallita']); exit; }
    pg_query($conn_import, "SET search_path TO " . pg_escape_identifier($conn_import, $schema_import));
    $result = pg_query($conn_import, "SELECT matviewname FROM pg_matviews WHERE schemaname=" . pg_escape_literal($conn_import, $schema_import));
    $views = pg_fetch_all_columns($result, 0) ?: [];
    foreach ($views as $view) {
        pg_query($conn_import, "REFRESH MATERIALIZED VIEW CONCURRENTLY " . pg_escape_identifier($conn_import, $schema_import) . "." . pg_escape_identifier($conn_import, $view));
    }
    send_sse('log', ['status' => 'success', 'message' => 'Viste materializzate SID aggiornate.']);
    send_sse('progress', ['value' => 85, 'text' => 'Fase 5/5: Sincronizzazione dati contabili...']);
    $sync_result = pg_query($conn_import, "SELECT demanio.sincronizza_dati_contabili();");
    if ($sync_result) {
        $sync_message = pg_fetch_result($sync_result, 0, 0);
        // !!! CORREZIONE: Aggiunto invio del messaggio di log per la sincronizzazione !!!
        send_sse('log', ['status' => 'success', 'message' => $sync_message]);
    } else {
        send_sse('log', ['status' => 'error', 'message' => 'Sincronizzazione dati contabili fallita: ' . pg_last_error($conn_import)]);
    }
    pg_close($conn_import);

    send_sse('progress', ['value' => 100, 'text' => 'Fase 6/6: Completato.']);
    deleteDirectory($workDir);
    send_sse('log', ['status' => 'info', 'message' => 'Processo terminato.']);
    send_sse('close', ['status' => 'success', 'message' => 'Importazione e sincronizzazione completate con successo']);
    exit;
}

// --- Routing e Caricamento Pagina ---
$_SESSION['current_page_key'] = $_GET['page'] ?? 'concessioni';
if (!array_key_exists($_SESSION['current_page_key'], $PAGES)) {
    $_SESSION['current_page_key'] = 'concessioni';
}
$pageConfig = $PAGES[$_SESSION['current_page_key']];

$data = [];
if (!empty($pageConfig['controller'])) {
    $controller_path = __DIR__ . '/../src/controllers/' . $pageConfig['controller'];
    if (file_exists($controller_path)) {
        require_once $controller_path;
        $controller_function = str_replace('.php', '', $pageConfig['controller']) . '_data';
        if (function_exists($controller_function)) {
            $controller_function($data, $pageConfig);
        }
    }
}

render_page($_SESSION['current_page_key'], $pageConfig, $data, $PAGES, $MENU_GROUPS);

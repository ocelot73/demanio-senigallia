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

// --- [INIZIO CORREZIONE]: Reintegrazione del redirect legacy ---
// Questa logica, presente nell'originale, gestisce un vecchio URL e lo reindirizza.
// La sua assenza costituiva una regressione funzionale per eventuali link salvati.
if (isset($_GET['page_view']) && $_GET['page_view'] === 'concessioni_f24') {
    $_GET['page'] = 'concessioni'; // Allinea il parametro al nuovo sistema di routing
    unset($_GET['page_view']);
    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
    $new_url = $base_url . (!empty($_GET) ? '?' . http_build_query($_GET) : '');
    header('Location: ' . $new_url);
    exit;
}
// --- [FINE CORREZIONE] ---

// --- Determina la pagina corrente prima di ogni altra logica ---
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni'; // Pagina di fallback
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// ==========================================================
// --- [BLOCCO LOGICA IMPORTAZIONE DATI SID] ---
// Questa sezione è stata verificata ed è una replica fedele della logica asincrona
// presente nel file originale, necessaria per il corretto funzionamento della pagina di importazione.
// ==========================================================
if ($currentPageKey === 'importa') {
    // --- CONFIGURAZIONE SPECIFICA PER L'IMPORTAZIONE ---
    $host_import     = DB_HOST;
    $port_import     = DB_PORT;
    $dbname_import   = DB_NAME;
    $user_import     = "sitadm"; // Utente con privilegi elevati
    $password_import = "Superuser60019!";
    $schema_import   = DB_SCHEMA;

    // --- FUNZIONI DI SUPPORTO PER L'IMPORTAZIONE ---
    if (!function_exists('getUploadError')) {
        function getUploadError($errorCode) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Il file supera la dimensione massima consentita dal server (php.ini).',
                UPLOAD_ERR_FORM_SIZE => 'Il file supera la dimensione massima specificata nel form.',
                UPLOAD_ERR_PARTIAL => 'Il caricamento del file è stato interrotto.',
                UPLOAD_ERR_NO_FILE => 'Nessun file è stato caricato.',
                UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea del server mancante.',
                UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco.',
                UPLOAD_ERR_EXTENSION => 'Un\'estensione PHP ha bloccato il caricamento.',
            ];
            return $errors[$errorCode] ?? 'Errore di caricamento sconosciuto (' . $errorCode . ')';
        }
    }
    if (!function_exists('formatBytes')) {
        function formatBytes($bytes, $precision = 2) {
            if ($bytes <= 0) return '0 B';
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $pow = floor(log($bytes) / log(1024));
            return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
        }
    }
    if (!function_exists('findJsonFile')) {
        function findJsonFile($directory) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    return $file;
                }
            }
            return false;
        }
    }
    if (!function_exists('deleteDirectory')) {
        function deleteDirectory($dir) {
            if (!file_exists($dir)) return true;
            if (!is_dir($dir)) return unlink($dir);
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') continue;
                if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
            }
            return rmdir($dir);
        }
    }

    // --- GESTIONE UPLOAD (AJAX POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
        header('Content-Type: application/json');
        $processId = uniqid('sid_import_', true);
        $workDir = sys_get_temp_dir() . '/' . $processId;
        if (!mkdir($workDir, 0700, true)) {
            echo json_encode(['success' => false, 'error' => 'Impossibile creare la directory di lavoro temporanea.']);
            exit;
        }

        file_put_contents($workDir . '/process_info.json', json_encode([
            'start_time' => time(),
            'file_name' => $_FILES['zipfile']['name']
        ]));

        $zipPath = $workDir . '/' . basename($_FILES['zipfile']['name']);
        if (!move_uploaded_file($_FILES['zipfile']['tmp_name'], $zipPath)) {
            echo json_encode(['success' => false, 'error' => getUploadError($_FILES['zipfile']['error'])]);
            deleteDirectory($workDir);
            exit;
        }

        echo json_encode(['success' => true, 'processId' => $processId]);
        exit;
    }

    // --- GESTIONE PROCESSO (SERVER-SENT EVENTS) ---
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

        send_sse('progress', ['value' => 0, 'text' => 'In attesa di avvio...']);
        send_sse('log', ['status' => 'info', 'message' => 'Inizializzazione processo di importazione']);

        $processInfoFile = $workDir . '/process_info.json';
        if (!file_exists($processInfoFile)) {
            send_sse('log', ['status' => 'error', 'message' => 'Processo non trovato o scaduto.']);
            send_sse('close', ['status' => 'error', 'message' => 'Processo non valido']);
            exit;
        }

        $processInfo = json_decode(file_get_contents($processInfoFile), true);
        $fileName = $processInfo['file_name'] ?? '';

        // FASE 1: Verifica del file
        send_sse('progress', ['value' => 5, 'text' => 'Fase 1/5: Verifica file...']);
        send_sse('log', ['status' => 'info', 'message' => 'Verifica del file ZIP in corso']);
        $zipPath = $workDir . '/' . $fileName;
        if (!file_exists($zipPath)) {
            send_sse('log', ['status' => 'error', 'message' => 'File non trovato nella directory di lavoro.']);
            send_sse('close', ['status' => 'error', 'message' => 'File non trovato']);
            exit;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE); $realMime = finfo_file($finfo, $zipPath); finfo_close($finfo);
        if (!in_array($realMime, ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])) {
            send_sse('log', ['status' => 'error', 'message' => 'Tipo di file non valido. È richiesto un archivio ZIP.']);
            send_sse('close', ['status' => 'error', 'message' => 'Tipo file non supportato']);
            exit;
        }
        send_sse('progress', ['value' => 10, 'text' => 'Fase 1/5: File verificato']);
        send_sse('log', ['status' => 'success', 'message' => 'File ZIP verificato: ' . htmlspecialchars($fileName) . ' (' . formatBytes(filesize($zipPath)) . ')']);

        // FASE 2: Estrazione archivio ZIP
        send_sse('progress', ['value' => 15, 'text' => 'Fase 2/5: Estrazione file ZIP...']);
        send_sse('log', ['status' => 'info', 'message' => 'Inizio estrazione archivio ZIP']);
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== TRUE) {
            send_sse('log', ['status' => 'error', 'message' => 'Impossibile aprire l\'archivio ZIP.']);
            send_sse('close', ['status' => 'error', 'message' => 'Archivio ZIP danneggiato o non valido']);
            exit;
        }
        $totalFiles = $zip->numFiles;
        send_sse('log', ['status' => 'info', 'message' => "Archivio ZIP contiene $totalFiles file. Inizio estrazione..."]);
        for ($i = 0; $i < $totalFiles; $i++) {
            $zip->extractTo($workDir, $zip->getNameIndex($i));
            if ($i % 10 === 0 || $i === $totalFiles - 1) {
                $progress = 15 + (($i + 1) / $totalFiles) * 25;
                send_sse('progress', ['value' => $progress, 'text' => "Fase 2/5: Estrazione file (" . ($i + 1) . "/$totalFiles)..."]);
            }
        }
        $zip->close(); unlink($zipPath);
        send_sse('progress', ['value' => 40, 'text' => 'Fase 2/5: Estrazione completata']);
        send_sse('log', ['status' => 'success', 'message' => 'Estrazione archivio ZIP completata con successo']);

        // FASE 3: Ricerca e conversione file JSON
        send_sse('progress', ['value' => 45, 'text' => 'Fase 3/5: Ricerca file dati...']);
        send_sse('log', ['status' => 'info', 'message' => 'Ricerca file JSON tra i file estratti']);
        $jsonFile = findJsonFile($workDir);
        if (!$jsonFile) {
            send_sse('log', ['status' => 'error', 'message' => 'Nessun file JSON trovato nell\'archivio.']);
            send_sse('close', ['status' => 'error', 'message' => 'File dati non trovato']);
            exit;
        }
        $jsonFilePath = $workDir . '/' . $jsonFile;
        send_sse('progress', ['value' => 50, 'text' => 'Fase 3/5: File dati trovato']);
        send_sse('log', ['status' => 'success', 'message' => 'File dati trovato: ' . htmlspecialchars($jsonFile)]);
        send_sse('progress', ['value' => 55, 'text' => 'Fase 3/5: Conversione formato...']);
        send_sse('log', ['status' => 'info', 'message' => 'Conversione formato JSON in corso (jq)']);
        $convertedJsonPath = $workDir . '/demanio.json';
        $cmd = "jq -c '.[]' " . escapeshellarg($jsonFilePath) . " > " . escapeshellarg($convertedJsonPath);
        exec($cmd, $output, $returnCode);
        if ($returnCode !== 0) {
            send_sse('log', ['status' => 'error', 'message' => 'Errore nella conversione del file JSON: ' . implode("\n", $output)]);
            send_sse('close', ['status' => 'error', 'message' => 'Conversione dati fallita']);
            exit;
        }
        send_sse('progress', ['value' => 60, 'text' => 'Fase 3/5: Conversione completata']);
        send_sse('log', ['status' => 'success', 'message' => 'Conversione formato JSON completata']);

        send_sse('progress', ['value' => 65, 'text' => 'Fase 3/5: Preparazione dati...']);
        send_sse('log', ['status' => 'info', 'message' => 'Spostamento file convertito nella directory di PostgreSQL tramite sudo']);
        $pgTargetPath = '/var/lib/postgresql/demanio.json';
        $move_cmd = "sudo /bin/mv " . escapeshellarg($convertedJsonPath) . " " . escapeshellarg($pgTargetPath);
        exec($move_cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            $error_message = 'Impossibile spostare il file nella directory di PostgreSQL. Codice di errore: ' . $returnCode;
            if (!empty($output)) {
                $error_message .= ' | Output: ' . implode('; ', $output);
            }
            send_sse('log', ['status' => 'error', 'message' => $error_message]);
            send_sse('close', ['status' => 'error', 'message' => 'Impossibile preparare i dati']);
            exit;
        }

        send_sse('progress', ['value' => 70, 'text' => 'Fase 3/5: Dati preparati']);
        send_sse('log', ['status' => 'success', 'message' => 'File dati posizionato correttamente per PostgreSQL']);

        // FASE 4: Aggiornamento database
        send_sse('progress', ['value' => 75, 'text' => 'Fase 4/5: Connessione al database...']);
        send_sse('log', ['status' => 'info', 'message' => 'Connessione al database PostgreSQL in corso']);
        $conn = pg_connect("host=$host_import port=$port_import dbname=$dbname_import user=$user_import password=$password_import");
        if (!$conn) {
            send_sse('log', ['status' => 'error', 'message' => 'Impossibile connettersi al database: ' . pg_last_error()]);
            send_sse('close', ['status' => 'error', 'message' => 'Connessione database fallita']);
            exit;
        }
        pg_query($conn, "SET search_path TO " . pg_escape_identifier($conn, $schema_import));
        send_sse('progress', ['value' => 80, 'text' => 'Fase 4/5: Connesso al database']);
        send_sse('log', ['status' => 'success', 'message' => 'Connessione al database stabilita con successo']);
        send_sse('progress', ['value' => 82, 'text' => 'Fase 4/5: Recupero viste...']);
        send_sse('log', ['status' => 'info', 'message' => 'Recupero elenco viste materializzate']);
        $result = pg_query($conn, "SELECT matviewname FROM pg_matviews WHERE schemaname=" . pg_escape_literal($conn, $schema_import) . " ORDER BY matviewname");
        if (!$result) {
            send_sse('log', ['status' => 'error', 'message' => 'Errore nel recupero viste: ' . pg_last_error($conn)]);
            send_sse('close', ['status' => 'error', 'message' => 'Recupero viste fallito']);
            pg_close($conn);
            exit;
        }
        $views = pg_fetch_all_columns($result, 0) ?: [];
        $totalViews = count($views);
        if ($totalViews === 0) {
            send_sse('log', ['status' => 'warning', 'message' => 'Nessuna vista materializzata trovata nello schema ' . htmlspecialchars($schema_import)]);
        } else {
            send_sse('log', ['status' => 'info', 'message' => "Trovate $totalViews viste materializzate. Inizio aggiornamento..."]);
            send_sse('log', ['status' => 'info', 'message' => "Viste da aggiornare: " . implode(", ", $views)]);
        }
        $data_aggiornamento = date('d/m/Y H:i:s');
        $successCount = 0; $errorCount = 0; $progressPerView = (98 - 85) / max($totalViews, 1);
        foreach ($views as $index => $view) {
            $currentProgress = 85 + ($index * $progressPerView);
            $viewName = pg_escape_identifier($conn, $view);
            send_sse('progress', ['value' => $currentProgress, 'text' => "Fase 4/5: Aggiornamento vista " . ($index + 1) . "/$totalViews..."]);
            send_sse('log', ['status' => 'info', 'message' => "--> Inizio aggiornamento vista: $view"]);
            if (pg_query($conn, "REFRESH MATERIALIZED VIEW $schema_import.$viewName")) {
                send_sse('log', ['status' => 'success', 'message' => "Vista $view aggiornata con successo."]);
                $commento = "Aggiornamento: $data_aggiornamento";
                $commentQuery = "COMMENT ON MATERIALIZED VIEW $schema_import.$viewName IS " . pg_escape_literal($conn, $commento);
                if (pg_query($conn, $commentQuery)) {
                    send_sse('log', ['status' => 'success', 'message' => "Commento aggiunto correttamente alla vista $view."]);
                } else {
                    send_sse('log', ['status' => 'warning', 'message' => "Vista $view aggiornata, ma IMPOSSIBILE aggiungere il commento: " . pg_last_error($conn)]);
                }
                $successCount++;
            } else {
                $errorCount++;
                send_sse('log', ['status' => 'error', 'message' => "ERRORE durante l'aggiornamento della vista $view: " . pg_last_error($conn)]);
            }
        }
        pg_close($conn);
        send_sse('progress', ['value' => 98, 'text' => 'Fase 5/5: Pulizia finale...']);
        send_sse('log', ['status' => 'info', 'message' => "Aggiornamento viste completato: $successCount successi, $errorCount errori."]);

        // FASE 5: Pulizia finale
        send_sse('log', ['status' => 'info', 'message' => 'Pulizia file temporanei in corso...']);
        deleteDirectory($workDir);
        send_sse('log', ['status' => 'info', 'message' => 'Pulizia completata.']);
        send_sse('progress', ['value' => 100, 'text' => 'Operazione completata']);
        if ($errorCount > 0) {
            send_sse('log', ['status' => 'warning', 'message' => 'Importazione completata con alcuni errori nelle viste.']);
            send_sse('close', ['status' => 'warning', 'message' => "Completato con $errorCount errori su $totalViews viste"]);
        } else {
            send_sse('log', ['status' => 'success', 'message' => 'Importazione completata con successo!']);
            send_sse('close', ['status' => 'success', 'message' => 'Importazione completata correttamente']);
        }
        exit;
    }
}
// ==========================================================
// --- [FINE BLOCCO LOGICA IMPORTAZIONE] ---
// ==========================================================

// --- Gestione Azioni Globali via GET (per la visualizzazione tabelle) ---
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
    $conn_temp = get_db_connection();
    $table_temp = $PAGES[$currentPageKey]['table'] ?? null;
    if ($conn_temp && $table_temp) {
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
if (isset($_GET['filter_type'])) {
    $filter_type = $_GET['filter_type'];
    $new_filters = [];
    if ($currentPageKey === 'concessioni') {
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
handle_ajax_request($FIELD_HELP);

// --- Routing e Preparazione Dati ---
$conn = get_db_connection();
$controllerFile = __DIR__ . '/../src/controllers/' . $pageConfig['controller'];

$data = [];
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerFunctionName = str_replace('.php', '_data', basename($pageConfig['controller']));
    if (function_exists($controllerFunctionName)) {
        $data = $controllerFunctionName($conn, $pageConfig);
    } else {
        die("Errore: Funzione controller '{$controllerFunctionName}' non trovata.");
    }
} else {
    // Se la pagina non ha un controller (es. pagina statica), non è un errore.
    // L'unica pagina senza controller è `importa`, gestita dal blocco logico sopra.
    if($currentPageKey !== 'importa') {
        die("Errore: Controller '{$pageConfig['controller']}' non trovato.");
    }
}

// --- Rendering della Pagina ---
render_page($currentPageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS);

// --- Chiusura Connessione ---
if ($conn) {
    pg_close($conn);
}

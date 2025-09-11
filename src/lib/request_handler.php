<?php
// /src/lib/request_handler.php

/**
 * ==========================================================
 * FUNZIONI DI SUPPORTO PER L'IMPORTAZIONE (dal file index.php originale)
 * ==========================================================
 */
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

function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow = floor(log($bytes) / log(1024));
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function findJsonFile($directory) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            return $file;
        }
    }
    return false;
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

function send_sse($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (function_exists('ob_flush')) ob_flush();
    flush();
}


/**
 * Gestisce tutte le richieste AJAX dell'applicazione.
 */
function handle_ajax_request(&$FIELD_HELP) {
    $action = $_REQUEST['action'] ?? null;
    if (!$action) {
        return; // Non è una richiesta AJAX per noi
    }

    // Gestione speciale per Server-Sent Events, che non restituisce JSON
    if ($action === 'process_import') {
        handle_sse_process(); // Questa funzione conterrà la logica e terminerà lo script
    }

    // Per tutte le altre azioni, procedi con la risposta JSON standard
    header('Content-Type: application/json; charset=utf-8');
    $conn = get_db_connection();
    $response = [];

    require_once __DIR__ . '/../models/concessione.php';
    require_once __DIR__ . '/../models/sollecito.php';

    try {
        switch ($action) {
            case 'get_sid_details':
                $idf24 = $_POST['idf24'] ?? null;
                if (!$idf24) throw new Exception('ID F24 non fornito.');
                $response = get_sid_details_model($conn, $idf24);
                break;

            case 'get_concessione_edit':
                $idf24 = $_POST['idf24'] ?? null;
                if ($idf24 === null) throw new Exception('ID F24 non fornito.');
                $response = get_concessione_for_edit_model($conn, $idf24);
                break;

            case 'save_concessione_edit':
                $original_idf24 = $_POST['original_idf24'] ?? null;
                $updates = json_decode($_POST['updates'] ?? '{}', true);
                if (!$original_idf24) throw new Exception('ID F24 originale non fornito.');
                $response = save_concessione_model($conn, $original_idf24, $updates);
                break;

            case 'get_calendar_events':
                $start = $_GET['start'] ?? date('Y-m-01');
                $end = $_GET['end'] ?? date('Y-m-t');
                $response = get_solleciti_as_events_model($conn, $start, $end);
                break;

            case 'import_zip':
                if (!isset($_FILES['zipfile'])) {
                    throw new Exception('Nessun file ZIP ricevuto.');
                }
                $processId = uniqid('sid_import_', true);
                $workDir = sys_get_temp_dir() . '/' . $processId;
                if (!mkdir($workDir, 0700, true)) {
                    throw new Exception('Impossibile creare la directory di lavoro temporanea.');
                }
                
                file_put_contents($workDir . '/process_info.json', json_encode([
                    'start_time' => time(),
                    'file_name' => $_FILES['zipfile']['name']
                ]));

                $zipPath = $workDir . '/' . basename($_FILES['zipfile']['name']);
                if (!move_uploaded_file($_FILES['zipfile']['tmp_name'], $zipPath)) {
                    $error_message = getUploadError($_FILES['zipfile']['error']);
                    deleteDirectory($workDir);
                    throw new Exception($error_message);
                }
                
                $response = ['success' => true, 'processId' => $processId];
                break;

            case 'set_filter':
                $_SESSION['column_filters'][$_POST['set_filter']] = $_POST['filter_value'];
                $response = ['success' => true];
                break;
            
            case 'toggle_column':
                 if (!isset($_SESSION['hidden_columns'])) $_SESSION['hidden_columns'] = [];
                 $column = $_POST['toggle_column'];
                 if (in_array($column, $_SESSION['hidden_columns'])) {
                    $_SESSION['hidden_columns'] = array_values(array_diff($_SESSION['hidden_columns'], [$column]));
                 } else {
                    $_SESSION['hidden_columns'][] = $column;
                 }
                 $response = ['success' => true];
                 break;

            default:
                throw new Exception('Azione non valida');
        }
    } catch (Exception $e) {
        http_response_code(400);
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    if ($conn) {
        pg_close($conn);
    }
    
    echo json_encode($response);
    exit;
}


/**
 * Gestisce il processo di importazione tramite Server-Sent Events (SSE).
 */
function handle_sse_process() {
    $processId = $_GET['id'] ?? null;
    if (!$processId) {
        header("HTTP/1.1 400 Bad Request");
        echo "ID processo non fornito.";
        exit;
    }

    // Impostazioni per SSE
    date_default_timezone_set('Europe/Rome');
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) ob_end_clean();

    // Configurazione specifica per l'importazione
    $host_import     = "localhost";
    $port_import     = "5432";
    $dbname_import   = "area11";
    $user_import     = "sitadm";
    $password_import = "Superuser60019!";
    $schema_import   = "demanio";

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
    $conn_import = @pg_connect("host=$host_import port=$port_import dbname=$dbname_import user=$user_import password=$password_import");
    if (!$conn_import) {
        send_sse('log', ['status' => 'error', 'message' => 'Impossibile connettersi al database: ' . pg_last_error()]);
        send_sse('close', ['status' => 'error', 'message' => 'Connessione database fallita']);
        exit;
    }
    pg_query($conn_import, "SET search_path TO " . pg_escape_identifier($conn_import, $schema_import));
    send_sse('progress', ['value' => 80, 'text' => 'Fase 4/5: Connesso al database']);
    send_sse('log', ['status' => 'success', 'message' => 'Connessione al database stabilita con successo']);
    send_sse('progress', ['value' => 82, 'text' => 'Fase 4/5: Recupero viste...']);
    send_sse('log', ['status' => 'info', 'message' => 'Recupero elenco viste materializzate']);
    $result = pg_query($conn_import, "SELECT matviewname FROM pg_matviews WHERE schemaname=" . pg_escape_literal($conn_import, $schema_import) . " ORDER BY matviewname");
    if (!$result) {
        send_sse('log', ['status' => 'error', 'message' => 'Errore nel recupero viste: ' . pg_last_error($conn_import)]);
        send_sse('close', ['status' => 'error', 'message' => 'Recupero viste fallito']);
        pg_close($conn_import);
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
        $viewName = pg_escape_identifier($conn_import, $view);
        send_sse('progress', ['value' => $currentProgress, 'text' => "Fase 4/5: Aggiornamento vista " . ($index + 1) . "/$totalViews..."]);
        send_sse('log', ['status' => 'info', 'message' => "--> Inizio aggiornamento vista: $view"]);
        if (pg_query($conn_import, "REFRESH MATERIALIZED VIEW $schema_import.$viewName")) {
            send_sse('log', ['status' => 'success', 'message' => "Vista $view aggiornata con successo."]);
            $commento = "Aggiornamento: $data_aggiornamento";
            $commentQuery = "COMMENT ON MATERIALIZED VIEW $schema_import.$viewName IS " . pg_escape_literal($conn_import, $commento);
            if (pg_query($conn_import, $commentQuery)) {
                send_sse('log', ['status' => 'success', 'message' => "Commento aggiunto correttamente alla vista $view."]);
            } else {
                send_sse('log', ['status' => 'warning', 'message' => "Vista $view aggiornata, ma IMPOSSIBILE aggiungere il commento: " . pg_last_error($conn_import)]);
            }
            $successCount++;
        } else {
            $errorCount++;
            send_sse('log', ['status' => 'error', 'message' => "ERRORE durante l'aggiornamento della vista $view: " . pg_last_error($conn_import)]);
        }
    }
    pg_close($conn_import);
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

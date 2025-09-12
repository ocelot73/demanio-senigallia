<?php
// /src/lib/request_handler.php

// Le funzioni di supporto per l'importazione (getUploadError, formatBytes, etc.) rimangono invariate.
// ...

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
// ... (le altre funzioni helper come formatBytes, findJsonFile, deleteDirectory, send_sse)

/**
 * Gestisce tutte le richieste AJAX dell'applicazione.
 */
function handle_ajax_request(&$FIELD_HELP) {
    $action = $_REQUEST['action'] ?? null;
    if (!$action) {
        return; // Non è una richiesta AJAX per noi
    }

    // Gestione speciale per Server-Sent Events
    if ($action === 'process') {
        // La logica di import_controller.php o simile gestirà questo
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false];

    try {
        switch ($action) {
            // Casi per get_sid_details, get_concessione_edit, save_concessione_edit...
            // ... (logica invariata)

            case 'set_filter':
                $column = $_POST['set_filter'] ?? null;
                $value = $_POST['filter_value'] ?? '';
                if ($column) {
                    if (!isset($_SESSION['column_filters'])) $_SESSION['column_filters'] = [];
                    if (empty($value)) unset($_SESSION['column_filters'][$column]);
                    else $_SESSION['column_filters'][$column] = $value;
                }
                $response = ['success' => true];
                break;

            case 'toggle_column':
                $column = $_POST['toggle_column'] ?? null;
                if ($column) {
                    if (!isset($_SESSION['hidden_columns'])) $_SESSION['hidden_columns'] = [];
                    if (in_array($column, $_SESSION['hidden_columns'])) {
                        $_SESSION['hidden_columns'] = array_values(array_diff($_SESSION['hidden_columns'], [$column]));
                    } else {
                        $_SESSION['hidden_columns'][] = $column;
                    }
                }
                $response = ['success' => true];
                break;

            // ***** INIZIO CODICE CORRETTO/AGGIUNTO *****
            case 'save_column_order':
                if (isset($_POST['column_order']) && is_array($_POST['column_order'])) {
                    $_SESSION['column_order'] = $_POST['column_order'];
                }
                $response = ['success' => true];
                break;

            case 'save_column_widths':
                if (isset($_POST['column_widths']) && is_array($_POST['column_widths'])) {
                    $_SESSION['column_widths'] = $_POST['column_widths'];
                }
                $response = ['success' => true];
                break;
            // ***** FINE CODICE CORRETTO/AGGIUNTO *****

            default:
                $response['error'] = 'Azione non riconosciuta.';
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// La funzione handle_sse_process rimane invariata.
// ...

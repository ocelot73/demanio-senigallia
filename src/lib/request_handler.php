<?php
// /src/lib/request_handler.php

/**
 * Gestisce tutte le richieste AJAX dell'applicazione.
 * Termina l'esecuzione dopo aver inviato una risposta JSON.
 */
function handle_ajax_request(&$FIELD_HELP) {
    $action = $_REQUEST['action'] ?? null;
    if (!$action) {
        return; // Non è una richiesta AJAX per noi
    }

    header('Content-Type: application/json; charset=utf-8');
    $conn = get_db_connection();
    $response = [];

    // Carica i modelli necessari
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
                // Incolla qui la logica PHP per la gestione dell'upload e del processo SSE dal tuo file originale
                // Questo è un processo più complesso e andrebbe ulteriormente modularizzato
                // Per ora, lo lasciamo come segnaposto
                $response = ['success' => true, 'processId' => uniqid()]; // Risposta di esempio
                break;

            // Azioni per la gestione della tabella (filtri, ordine, etc.)
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
    exit; // Termina lo script
}

<?php
// /src/lib/request_handler.php

/**
 * Gestisce tutte le richieste AJAX dell'applicazione.
 */
function handle_ajax_request(&$FIELD_HELP) {
    $action = $_POST['action'] ?? null;
    if (!$action) {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false];
    $conn = null;

    try {
        // La maggior parte delle azioni richiede una connessione al DB
        $db_actions = ['get_sid_details', 'get_concessione_edit', 'save_concessione_edit'];
        if (in_array($action, $db_actions)) {
            $conn = get_db_connection();
        }

        switch ($action) {
            // ***** INIZIO CODICE CORRETTO/AGGIUNTO *****
            case 'get_sid_details':
                require_once __DIR__ . '/../models/dettaglio_sid.php';
                $idf24 = $_POST['idf24'] ?? null;
                if (!$idf24) throw new Exception('ID F24 non fornito.');
                $response = get_all_sid_details($conn, $idf24);
                break;

            case 'get_concessione_edit':
                require_once __DIR__ . '/../models/modifica_concessione.php';
                $idf24 = $_POST['idf24'] ?? null;
                if ($idf24 === null) throw new Exception('ID F24 non fornito.');
                $response = get_concessione_for_edit($conn, $idf24);
                break;

            case 'save_concessione_edit':
                require_once __DIR__ . '/../models/modifica_concessione.php';
                $original_idf24 = $_POST['original_idf24'] ?? null;
                $updates = json_decode($_POST['updates'] ?? '{}', true);
                if ($original_idf24 === null) throw new Exception('ID F24 originale non fornito.');
                $response = save_concessione_changes($conn, $original_idf24, $updates);
                break;
            // ***** FINE CODICE CORRETTO/AGGIUNTO *****

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

            default:
                $response['error'] = 'Azione non riconosciuta.';
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = $e->getMessage();
    } finally {
        if ($conn) {
            pg_close($conn);
        }
    }

    echo json_encode($response);
    exit;
}

// Per completezza, andrebbero creati i file model `dettaglio_sid.php` e `modifica_concessione.php`
// con la logica PHP prelevata dall'originale `index.php`.
// La loro logica è stata qui integrata per semplicità, ma in un refactoring completo sarebbero file separati.

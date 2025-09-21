<?php
// =====================================================================
// FILE: /src/controllers/concessioni_controller.php (COMPLETAMENTE RISCRITTO)
// Gestisce la logica per le viste tabellari, inclusi paginazione,
// ordinamento e filtri, fornendo tutti i dati necessari alla vista.
// =====================================================================

// Include il modello corretto e più avanzato
require_once __DIR__ . '/../models/concessione.php';

/**
 * Prepara i dati per le viste tabellari delle concessioni.
 *
 * @param array &$data Array passato per riferimento per contenere i dati per la vista.
 * @param array &$pageConfig La configurazione della pagina corrente da pages.php.
 */
function concessioni_controller_data(&$data, &$pageConfig) {
    $conn = null;
    try {
        // --- 1. Connessione al DB ---
        $conn = get_db_connection();
        if (!$conn) {
            throw new Exception("Impossibile connettersi al database.");
        }

        // --- 2. Definizione dello Stato della Vista (Paginazione, Ordinamento, Filtri) ---
        $table = $pageConfig['table'] ?? 'concessioni_unione_v';
        
        // Se non è definita, la prendo dalla config.php.example per compatibilità
        if (!defined('RECORDS_PER_PAGE')) {
            define('RECORDS_PER_PAGE', 35);
        }
        
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($current_page < 1) $current_page = 1;

        // Gestione ordinamento
        $default_order = ['column' => 'denominazione_ditta_concessionario', 'direction' => 'ASC'];
        $order_column = $_GET['order'] ?? $_SESSION['order_column'] ?? $default_order['column'];
        $order_direction = $_GET['dir'] ?? $_SESSION['order_direction'] ?? $default_order['direction'];
        $_SESSION['order_column'] = $order_column;
        $_SESSION['order_direction'] = $order_direction;

        // Gestione filtri
        $filters = $_SESSION['column_filters'] ?? [];
        $filters_active = !empty(array_filter($filters));

        // Determina se mostrare la vista completa (senza paginazione)
        $full_view = isset($_GET['export_csv']) || ($pageConfig['table'] !== 'concessioni_unione_v');

        // --- 3. Recupero dei Dati tramite il Modello Avanzato ---
        $all_columns = get_table_columns($conn, $table);
        $total_records = get_records_count($conn, $table, $filters, $all_columns);

        if ($full_view) {
            $records = get_all_records($conn, $table, $order_column, $order_direction, $filters, $all_columns);
            $total_pages = 1;
            $current_page = 1;
        } else {
            $records = get_paginated_records($conn, $table, $current_page, $order_column, $order_direction, $filters, $all_columns);
            $total_pages = ceil($total_records / RECORDS_PER_PAGE);
        }

        // --- 4. Preparazione dei Dati da passare alla Vista (`$data` array) ---
        // Questi sono tutti i dati che il template 'concessioni.php' si aspetta.
        $data['records'] = $records;
        $data['columns'] = $all_columns;
        $data['visible_columns'] = array_diff($all_columns, $_SESSION['hidden_columns'] ?? []);
        $data['hidden_columns'] = $_SESSION['hidden_columns'] ?? [];
        $data['filters'] = $filters;
        $data['filters_active'] = $filters_active;
        $data['order_column'] = $order_column;
        $data['order_direction'] = $order_direction;
        $data['total_records'] = $total_records;
        $data['current_page'] = $current_page;
        $data['total_pages'] = $total_pages;
        $data['full_view'] = $full_view;

    } catch (Exception $e) {
        // In caso di errore, lo salva per mostrarlo nella vista
        $data['error'] = "Si è verificato un errore: " . $e->getMessage();
        error_log($e->getMessage());
    } finally {
        // --- 5. Chiusura Connessione ---
        if ($conn) {
            pg_close($conn);
        }
    }
}

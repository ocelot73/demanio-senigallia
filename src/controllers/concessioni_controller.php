<?php
// /src/controllers/concessioni_controller.php

require_once __DIR__ . '/../models/concessione.php';

/**
 * Prepara i dati per le viste a tabella (concessioni, calcolo canoni, etc.).
 * Replica la logica di filtraggio/ordinamento/paginazione dell'index.php originale.
 */
function concessioni_data($conn, $pageConfig) {
    $table = $pageConfig['table'] ?? null;
    if (!$table) return ['error' => 'Tabella non configurata'];

    // Imposta parametro di sessione DB per l'anno nel caso del calcolo canoni
    if ($table === 'calcolo_canoni_v') {
        $selected_year = $_GET['anno'] ?? date('Y');
        @pg_query($conn, "SELECT set_config('demanio.anno', " . pg_escape_literal($conn, (string)$selected_year) . ", false)");
    }

    // Stato della vista
    $filters = $_SESSION['column_filters'] ?? [];
    $page = max(1, (int)($_GET['p'] ?? 1));
    $order_column = $_GET['order'] ?? 'denominazione ditta concessionario';
    $order_direction = $_GET['dir'] ?? 'ASC';

    // Dati
    $records = get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters);
    $total_records = get_records_count($conn, $table, $filters);
    $columns = get_table_columns($conn, $table);
    $total_pages = ($total_records > 0) ? ceil($total_records / RECORDS_PER_PAGE) : 1;

    return [
        'records' => $records,
        'columns' => $columns,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'order_column' => $order_column,
        'order_direction' => $order_direction,
        'filters' => $filters,
        'hidden_columns' => $_SESSION['hidden_columns'] ?? []
    ];
}

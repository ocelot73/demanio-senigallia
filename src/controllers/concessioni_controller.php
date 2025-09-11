<?php
// /src/controllers/concessioni_controller.php

require_once __DIR__ . '/../models/concessione.php'; // <-- AGGIUNGI QUESTA RIGA

/**
 * Prepara i dati per le viste a tabella (concessioni, calcolo canoni, etc.).
 */
function concessioni_data($conn, $pageConfig) {
    $table = $pageConfig['table'] ?? null;
    if (!$table) return ['error' => 'Tabella non configurata'];

    // Gestione filtri e ordinamento da sessione/richiesta
    $filters = $_SESSION['column_filters'] ?? [];
    $page = max(1, (int)($_GET['p'] ?? 1)); // Ho corretto 'page' in 'p' per coerenza con il template di paginazione
    $order_column = $_GET['order'] ?? 'denominazione ditta concessionario';
    $order_direction = $_GET['dir'] ?? 'ASC';

    // Recupero dati dal modello
    $records = get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters);
    $total_records = get_records_count($conn, $table, $filters);
    $columns = get_table_columns($conn, $table);
    
    $total_pages = ($total_records > 0) ? ceil($total_records / RECORDS_PER_PAGE) : 1;

    // Ritorna un array di dati che la vista potrà utilizzare
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

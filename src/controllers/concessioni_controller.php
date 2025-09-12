<?php
// /src/controllers/concessioni_controller.php
require_once __DIR__ . '/../models/concessione.php';

/**
 * Prepara i dati per la pagina "Concessioni".
 */
function concessioni_data($conn, $pageConfig) {
    $table = $pageConfig['table'] ?? 'concessioni_unione_v';

    // Toggle vista (Parziale/Completa)
    if (isset($_GET['toggle_view'])) {
        $_SESSION['full_view'] = !($_SESSION['full_view'] ?? true);
    }

    // Default: avvio in vista completa
    $full_view = $_SESSION['full_view'] ?? true;

    // Stato filtri e ordinamento
    $filters         = $_SESSION['column_filters'] ?? [];
    $page            = max(1, (int)($_GET['p'] ?? 1));
    $order_column    = $_GET['order'] ?? 'denominazione ditta concessionario';
    $order_direction = $_GET['dir']   ?? 'ASC';

    // Dati
    if ($full_view) {
        $records       = get_all_records($conn, $table, $order_column, $order_direction, $filters);
        $total_records = count($records);
        $total_pages   = 1;
        $page          = 1;
    } else {
        $records       = get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters);
        $total_records = get_records_count($conn, $table, $filters);
        $total_pages   = ($total_records > 0) ? (int)ceil($total_records / RECORDS_PER_PAGE) : 1;
    }

    $columns = get_table_columns($conn, $table);

    return [
        'records'         => $records,
        'columns'         => $columns,
        'total_pages'     => $total_pages,
        'current_page'    => $page,
        'order_column'    => $order_column,
        'order_direction' => $order_direction,
        'filters'         => $filters,
        'hidden_columns'  => $_SESSION['hidden_columns'] ?? [],
        'full_view'       => $full_view,
    ];
}

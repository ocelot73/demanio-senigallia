<?php
// /src/controllers/concessioni_controller.php
require_once __DIR__ . '/../models/concessione.php';

/**
 * Prepara i dati per la pagina "Concessioni".
 */
function concessioni_controller_data($conn, $pageConfig) {
    $table = $pageConfig['table'] ?? 'concessioni_unione_v';
    // Gestione anno per la vista 'calcolo_canoni' (dall'originale)
    if ($pageConfig['label'] === 'Calcolo Canoni') {
        $selected_year = $_GET['anno'] ?? $_SESSION['selected_year'] ?? date('Y');
        $_SESSION['selected_year'] = $selected_year;
        $escaped_year = pg_escape_string($conn, (string)$selected_year);
        @pg_query($conn, "SELECT set_config('demanio.anno', '$escaped_year', false)");
    }
    
    // Toggle vista (Parziale/Completa)
    $full_view = $_SESSION['full_view'] ?? true;
    // Stato filtri e ordinamento
    $filters         = $_SESSION['column_filters'] ?? [];
    $filters_active  = !empty($filters);
    $page            = max(1, (int)($_GET['p'] ?? 1));
    $order_column    = $_GET['order'] ?? 'denominazione ditta concessionario';
    $order_direction = $_GET['dir']   ?? 'ASC';
    // Colonne
    $all_columns = get_table_columns($conn, $table);

    // Gestione ordinamento colonne salvato in sessione (dall'originale)
    if (isset($_SESSION['column_order'])) {
        $saved_order = $_SESSION['column_order'];
        $columns = array_values(array_intersect($saved_order, $all_columns));
        foreach ($all_columns as $col) {
            if (!in_array($col, $columns)) {
                $columns[] = $col;
            }
        }
    } else {
        $columns = $all_columns;
    }

    // Se la colonna richiesta non esiste, ripiega sulla prima disponibile
    if (empty($columns) || !in_array($order_column, $columns, true)) {
        $order_column = $columns[0] ?? '';
    }

    // Colonne visibili
    $hidden_columns  = $_SESSION['hidden_columns'] ?? [];
    $visible_columns = array_values(array_diff($columns, $hidden_columns));

    // Dati
    if ($full_view) {
        $records       = get_all_records($conn, $table, $order_column, $order_direction, $filters);
        $total_records = count($records);
        $total_pages   = 1;
        $page          = 1;
    } else {
        $records       = get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters);
        $total_records = get_records_count($conn, $table, $filters);
        $total_pages   = ($total_records > 0) ? (int)ceil($total_records / (int)RECORDS_PER_PAGE) : 1;
    }

    return [
        'records'         => $records,
        'columns'         => $columns,
        'all_columns'     => $all_columns, // Aggiunto per riferimento
        'visible_columns' => $visible_columns,
        'hidden_columns'  => $hidden_columns,
        'total_pages'     => $total_pages,
        'current_page'    => $page,
        'order_column'    => $order_column,
        'order_direction' => $order_direction,
        'filters'         => $filters,
        'full_view'       => $full_view,
        'filters_active'  => $filters_active,
        'total_records'   => $total_records
    ];
}

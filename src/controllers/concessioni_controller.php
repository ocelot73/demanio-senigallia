<?php
// /src/controllers/concessioni_controller.php
require_once __DIR__ . '/../models/concessione.php';

/**
 * Prepara i dati per la visualizzazione delle tabelle delle concessioni.
 * Logica allineata a index.php per gestire correttamente lo stato della sessione,
 * la paginazione, i filtri e l'ordinamento.
 */
function concessioni_controller_data($conn, $pageConfig) {
    $table = $pageConfig['table'] ?? 'concessioni_unione_v';
    $data_to_return = [];

    // Gestione anno per la vista 'calcolo_canoni' (dall'originale)
    if ($pageConfig['label'] === 'Calcolo Canoni') {
        $selected_year = $_GET['anno'] ?? $_SESSION['selected_year'] ?? date('Y');
        $_SESSION['selected_year'] = $selected_year;
        $data_to_return['selected_year'] = $selected_year; // Passa l'anno alla vista
        $escaped_year = pg_escape_string($conn, (string)$selected_year);
        @pg_query($conn, "SELECT set_config('demanio.anno', '$escaped_year', false)");
    }

    $full_view = $_SESSION['full_view'] ?? ($GLOBALS['currentPageKey'] === 'concessioni');
    $filters = $_SESSION['column_filters'] ?? [];
    $page = max(1, (int)($_GET['page'] ?? 1)); // CORREZIONE: Usa 'page' invece di 'p'

    $all_columns = get_table_columns($conn, $table);
    // CORREZIONE: Logica di fallback per l'ordinamento identica all'originale
    $order_column = $_GET['order'] ?? (in_array('denominazione ditta concessionario', $all_columns) ? 'denominazione ditta concessionario' : ($all_columns[0] ?? ''));
    $order_direction = $_GET['dir'] ?? 'ASC';

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

    if (empty($columns) || !in_array($order_column, $columns)) {
        $order_column = $columns[0] ?? '';
    }

    $total_records = get_records_count($conn, $table, $filters, $all_columns);

    if ($full_view) {
        $records = get_all_records($conn, $table, $order_column, $order_direction, $filters, $all_columns);
        $total_pages = 1;
        $page = 1;
    } else {
        $records = get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters, $all_columns);
        $total_pages = ($total_records > 0) ? (int)ceil($total_records / (int)RECORDS_PER_PAGE) : 1;
    }

    // Unisce i dati comuni con quelli specifici (es. selected_year)
    return array_merge($data_to_return, [
        'records'         => $records,
        'columns'         => $columns,
        'all_columns'     => $all_columns,
        'visible_columns' => array_values(array_diff($columns, $_SESSION['hidden_columns'] ?? [])),
        'hidden_columns'  => $_SESSION['hidden_columns'] ?? [],
        'total_pages'     => $total_pages,
        'current_page'    => $page,
        'order_column'    => $order_column,
        'order_direction' => $order_direction,
        'filters'         => $filters,
        'full_view'       => $full_view,
        'filters_active'  => !empty($filters),
        'total_records'   => $total_records
    ]);
}

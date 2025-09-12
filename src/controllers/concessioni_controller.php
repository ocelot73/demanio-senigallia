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

    // Elenco colonne disponibili dalla tabella/vista
    $columns = get_table_columns($conn, $table);

    // Se la colonna richiesta non esiste nella vista corrente, ripiega sulla prima disponibile
    if (empty($columns)) {
        $order_column = ''; // nessuna colonna
    } elseif (!in_array($order_column, $columns, true)) {
        $order_column = $columns[0];
    }

    // Calcolo colonne visibili (tutte meno quelle nascoste a sessione)
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
        $total_pages   = ($total_records > 0) ? (int)ceil($total_records / RECORDS_PER_PAGE) : 1;
    }

    return [
        'records'         => $records,
        'columns'         => $columns,
        'visible_columns' => $visible_columns, // <-- ora disponibile per la vista
        'hidden_columns'  => $hidden_columns,
        'total_pages'     => $total_pages,
        'current_page'    => $page,
        'order_column'    => $order_column,
        'order_direction' => $order_direction,
        'filters'         => $filters,
        'full_view'       => $full_view,
    ];
}

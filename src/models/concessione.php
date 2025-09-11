<?php
// /src/models/concessione.php

/**
 * Recupera un elenco paginato di record da una tabella/vista.
 */
function get_paginated_records($conn, $table, $page, $order_col, $order_dir, $filters) {
    $offset = ($page - 1) * RECORDS_PER_PAGE;
    
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);

    $sql = sprintf(
        'SELECT * FROM %s %s ORDER BY %s %s NULLS LAST LIMIT %d OFFSET %d',
        pg_escape_identifier($conn, $table),
        $where_clause ? "WHERE {$where_clause}" : '',
        pg_escape_identifier($conn, $order_col),
        strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC',
        RECORDS_PER_PAGE,
        $offset
    );

    $result = db_query($conn, $sql, $params);
    return pg_fetch_all($result) ?: [];
}

/**
 * Conta il numero totale di record in una tabella/vista, applicando i filtri.
 */
function get_records_count($conn, $table, $filters) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);
    
    $sql = sprintf(
        'SELECT COUNT(*) as total FROM %s %s',
        pg_escape_identifier($conn, $table),
        $where_clause ? "WHERE {$where_clause}" : ''
    );
    
    $result = db_query($conn, $sql, $params);
    $row = pg_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

/**
 * Recupera i nomi delle colonne di una tabella/vista.
 */
function get_table_columns($conn, $table) {
    $sql = sprintf('SELECT * FROM %s LIMIT 0', pg_escape_identifier($conn, $table));
    $result = db_query($conn, $sql);
    $columns = [];
    if ($result) {
        for ($i = 0; $i < pg_num_fields($result); $i++) {
            $columns[] = pg_field_name($result, $i);
        }
    }
    return $columns;
}

/**
 * Costruisce la clausola WHERE e i parametri per i filtri.
 * Logica estratta dall'originale.
 */
function build_filter_where_clause($conn, $filters) {
    $where = [];
    $params = [];
    $i = 1;
    foreach ($filters as $col => $value) {
        if (!empty($value)) {
            $c = pg_escape_identifier($conn, $col);
            if ($value === 'NULL') {
                $where[] = "$c IS NULL";
            } elseif ($value === 'NOT_NULL') {
                $where[] = "$c IS NOT NULL";
            } else {
                $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                $params[] = '%' . $value . '%';
                $i++;
            }
        }
    }
    return [implode(" AND ", $where), $params];
}


/**
 * Recupera tutti i dettagli SID per un dato idf24.
 * Logica estratta dall'originale.
 */
function get_sid_details_model($conn, $idf24) {
    // Definizione delle viste, potrebbe essere spostata in un file di configurazione
    $detail_views = [
        'sintesi_atti' => ['label' => 'SINTESI ATTI', 'view' => 'sintesi_atti_mv', 'icon' => 'fas fa-file-invoice'],
        // ... Aggiungi tutte le altre viste dall'array $detail_views originale ...
    ];

    $details_data = [];
    foreach ($detail_views as $key => $config) {
        $view_name = pg_escape_identifier($conn, $config['view']);
        $query = "SELECT * FROM {$view_name} WHERE idf24 = $1";
        $result = @db_query($conn, $query, [$idf24]);
        
        $data = [];
        $query_error = null;
        if ($result) {
            $data = pg_fetch_all($result) ?: [];
        } else {
            $query_error = "Errore nella vista: " . $config['view'];
        }

        $details_data[$key] = [
            'label' => $config['label'],
            'icon' => $config['icon'] ?? 'fas fa-question-circle',
            'data' => $data,
            'count' => count($data),
            'error' => $query_error
        ];
    }
    return $details_data;
}

// ... Le funzioni get_concessione_for_edit_model e save_concessione_model
// andrebbero implementate qui, estraendo la logica dal file originale.

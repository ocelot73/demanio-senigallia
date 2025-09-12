<?php
// /src/models/concessione.php

/**
 * Restituisce i nomi colonna della tabella/vista.
 */
function get_table_columns($conn, $table) {
    $res = db_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0");
    $cols = [];
    for ($i = 0; $i < pg_num_fields($res); $i++) {
        $cols[] = pg_field_name($res, $i);
    }
    return $cols;
}

/**
 * Conta i record dopo l’applicazione dei filtri.
 */
function get_records_count($conn, $table, $filters) {
    list($where, $params) = build_filter_where_clause($conn, $filters);
    $sql = "SELECT COUNT(*) AS total FROM " . pg_escape_identifier($conn, $table) . " " . ($where ? "WHERE $where" : "");
    $res = db_query($conn, $sql, $params);
    return (int)pg_fetch_result($res, 0, 'total');
}

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
 * Recupera TUTTI i record (vista completa, nessun LIMIT).
 */
function get_all_records($conn, $table, $order_col, $order_dir, $filters) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);

    $sql = sprintf(
        'SELECT * FROM %s %s ORDER BY %s %s NULLS LAST',
        pg_escape_identifier($conn, $table),
        $where_clause ? "WHERE {$where_clause}" : '',
        pg_escape_identifier($conn, $order_col),
        strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC'
    );

    $result = db_query($conn, $sql, $params);
    return pg_fetch_all($result) ?: [];
}

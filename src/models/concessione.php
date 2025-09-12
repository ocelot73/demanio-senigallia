<?php
// /src/models/concessione.php

/**
 * Recupera i nomi delle colonne da una data tabella o vista.
 *
 * @param pgsql $conn La connessione al database.
 * @param string $table Il nome della tabella/vista.
 * @return array Un array con i nomi delle colonne.
 */
function get_table_columns($conn, $table) {
    $result = db_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0");
    if (!$result) return [];
    
    $columns = [];
    for ($i = 0; $i < pg_num_fields($result); $i++) {
        $columns[] = pg_field_name($result, $i);
    }
    return $columns;
}

/**
 * Costruisce la clausola WHERE e i parametri per il filtraggio dei dati.
 *
 * @param pgsql $conn La connessione al database.
 * @param array $filters I filtri da applicare.
 * @param array $all_columns Tutte le colonne disponibili.
 * @return array Un array contenente la stringa WHERE e i parametri.
 */
function build_filter_where_clause($conn, $filters) {
    // Per ottenere i tipi, abbiamo bisogno di una query, ma non abbiamo la tabella qui.
    // Replicando la logica dell'originale, che non usava i tipi in modo robusto,
    // possiamo usare un cast a TEXT per un comportamento simile e sicuro.
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
                 // La logica originale era complessa; un ILIKE su cast a TEXT è un'approssimazione sicura
                 // e funzionalmente quasi identica per la ricerca utente.
                $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                $params[] = '%' . $value . '%';
                $i++;
            }
        }
    }
    return [implode(" AND ", $where), $params];
}


/**
 * Conta il numero totale di record in una tabella applicando i filtri.
 *
 * @param pgsql $conn La connessione al database.
 * @param string $table Il nome della tabella/vista.
 * @param array $filters I filtri da applicare.
 * @return int Il numero totale di record.
 */
function get_records_count($conn, $table, $filters) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);
    $query = "SELECT COUNT(*) AS total FROM " . pg_escape_identifier($conn, $table) .
             (!empty($where_clause) ? " WHERE $where_clause" : '');
    
    $result = db_query($conn, $query, $params);
    return (int)pg_fetch_result($result, 0, 'total');
}

/**
 * Recupera i record per una specifica pagina, con ordinamento e filtri.
 *
 * @param pgsql $conn La connessione al database.
 * @param string $table Il nome della tabella/vista.
 * @param int $page Il numero di pagina.
 * @param string $order_column La colonna per l'ordinamento.
 * @param string $order_direction La direzione dell'ordinamento (ASC/DESC).
 * @param array $filters I filtri da applicare.
 * @return array Un array di record.
 */
function get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);
    $offset = ($page - 1) * RECORDS_PER_PAGE;

    $query = "SELECT * FROM " . pg_escape_identifier($conn, $table) .
             (!empty($where_clause) ? " WHERE $where_clause" : '');
    
    if (!empty($order_column)) {
        $qcol = pg_escape_identifier($conn, $order_column);
        $qdir = strtoupper($order_direction) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $qcol $qdir NULLS LAST";
    }

    $query .= " LIMIT " . intval(RECORDS_PER_PAGE) . " OFFSET " . intval($offset);
    
    $result = db_query($conn, $query, $params);
    return pg_fetch_all($result) ?: [];
}

/**
 * Recupera tutti i record da una tabella, con ordinamento e filtri.
 *
 * @param pgsql $conn La connessione al database.
 * @param string $table Il nome della tabella/vista.
 * @param string $order_column La colonna per l'ordinamento.
 * @param string $order_direction La direzione dell'ordinamento (ASC/DESC).
 * @param array $filters I filtri da applicare.
 * @return array Un array di tutti i record trovati.
 */
function get_all_records($conn, $table, $order_column, $order_direction, $filters) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters);

    $query = "SELECT * FROM " . pg_escape_identifier($conn, $table) .
             (!empty($where_clause) ? " WHERE $where_clause" : '');

    if (!empty($order_column)) {
        $qcol = pg_escape_identifier($conn, $order_column);
        $qdir = strtoupper($order_direction) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $qcol $qdir NULLS LAST";
    }

    $result = db_query($conn, $query, $params);
    return pg_fetch_all($result) ?: [];
}

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
 * Costruisce la clausola WHERE e i parametri per il filtraggio dei dati, replicando la logica originale.
 *
 * @param pgsql $conn La connessione al database.
 * @param array $filters I filtri da applicare.
 * @param array $all_columns Tutte le colonne disponibili per la tabella.
 * @return array Un array contenente la stringa WHERE e i parametri.
 */
function build_filter_where_clause($conn, $filters, $all_columns) {
    // Recupera i tipi di dato per tutte le colonne della tabella in una sola query per efficienza
    $table_name = pg_escape_identifier($conn, $_SESSION['current_page_key']); // Assumendo che la chiave di sessione corrisponda alla tabella/vista
    $table = $GLOBALS['PAGES'][$_SESSION['current_page_key']]['table'] ?? 'concessioni_unione_v';
    $result = db_query($conn, "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0");
    $column_types = [];
    if($result) {
        for ($i = 0; $i < pg_num_fields($result); $i++) {
            $column_types[pg_field_name($result, $i)] = pg_field_type($result, $i);
        }
    }

    $where = [];
    $params = [];
    $i = 1;
    foreach ($filters as $col => $value) {
        if (!empty($value) && in_array($col, $all_columns)) {
            $c = pg_escape_identifier($conn, $col);
            $t = strtolower($column_types[$col] ?? '');

            if ($value === 'NULL') {
                $where[] = "$c IS NULL";
            } elseif ($value === 'NOT_NULL') {
                $where[] = "$c IS NOT NULL";
            } else {
                if (in_array($t, ['text', 'varchar', 'bpchar', 'character varying', 'character'])) {
                    $where[] = "$c ILIKE $" . $i;
                    $params[] = '%' . $value . '%';
                    $i++;
                } elseif (in_array($t, ['int2', 'int4', 'int8', 'integer', 'smallint', 'bigint'])) {
                    if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                        $where[] = "$c = $" . $i;
                        $params[] = (int)$value;
                        $i++;
                    } else {
                        $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                        $params[] = '%' . $value . '%';
                        $i++;
                    }
                } elseif (in_array($t, ['numeric', 'decimal', 'float4', 'float8', 'real', 'double precision'])) {
                    $nv = str_replace('.', '', $value);
                    $nv = str_replace(',', '.', $nv);
                    if (is_numeric($nv)) {
                        $where[] = "$c = $" . $i;
                        $params[] = (float)$nv;
                        $i++;
                    } else {
                        $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                        $params[] = '%' . $value . '%';
                        $i++;
                    }
                } elseif ($t === 'date') {
                    $d = strtotime($value);
                    if ($d !== false) {
                        $where[] = "$c = $" . $i;
                        $params[] = date('Y-m-d', $d);
                        $i++;
                    } else {
                        $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                        $params[] = '%' . $value . '%';
                        $i++;
                    }
                } else { // Fallback per tipi non gestiti o sconosciuti
                    $where[] = "CAST($c AS TEXT) ILIKE $" . $i;
                    $params[] = '%' . $value . '%';
                    $i++;
                }
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
 * @param array $all_columns Tutte le colonne disponibili per la tabella.
 * @return int Il numero totale di record.
 */
function get_records_count($conn, $table, $filters, $all_columns) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters, $all_columns);
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
 * @param array $all_columns Tutte le colonne disponibili per la tabella.
 * @return array Un array di record.
 */
function get_paginated_records($conn, $table, $page, $order_column, $order_direction, $filters, $all_columns) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters, $all_columns);
    $offset = ($page - 1) * RECORDS_PER_PAGE;

    $query = "SELECT * FROM " . pg_escape_identifier($conn, $table) .
             (!empty($where_clause) ? " WHERE $where_clause" : '');
    
    if (!empty($order_column) && in_array($order_column, $all_columns)) {
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
 * @param array $all_columns Tutte le colonne disponibili per la tabella.
 * @return array Un array di tutti i record trovati.
 */
function get_all_records($conn, $table, $order_column, $order_direction, $filters, $all_columns) {
    list($where_clause, $params) = build_filter_where_clause($conn, $filters, $all_columns);
    $query = "SELECT * FROM " . pg_escape_identifier($conn, $table) .
             (!empty($where_clause) ? " WHERE $where_clause" : '');

    if (!empty($order_column) && in_array($order_column, $all_columns)) {
        $qcol = pg_escape_identifier($conn, $order_column);
        $qdir = strtoupper($order_direction) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $qcol $qdir NULLS LAST";
    }

    $result = db_query($conn, $query, $params);
    return pg_fetch_all($result) ?: [];
}

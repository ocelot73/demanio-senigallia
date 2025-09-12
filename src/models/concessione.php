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

/**
 * Restituisce un singolo record dalla vista/tabella per IDF24 (usata nella pagina Dettagli).
 */
function get_view_record_by_id($conn, $table, $idf24) {
    $sql = 'SELECT * FROM ' . pg_escape_identifier($conn, $table) . ' WHERE idf24::text = $1 LIMIT 1';
    $res = db_query($conn, $sql, [strval($idf24)]);
    return pg_fetch_assoc($res) ?: null;
}

/**
 * Restituisce i dettagli dal SID (modello specifico per la modale Dettagli).
 */
function get_sid_details_model($conn, $idf24) {
    $sql = "
        SELECT
            COALESCE(denominazione, denominazione_concessionario) AS denominazione,
            comune, localita, 'Licenza' AS tipo_atto, idf24, num_sid, stato_conc_sid
        FROM concessioni_unione_v
        WHERE idf24::text = $1
        LIMIT 1
    ";
    $res = db_query($conn, $sql, [strval($idf24)]);
    return pg_fetch_assoc($res) ?: null;
}

/**
 * Costruisce la WHERE in base ai filtri di colonna.
 * - Gestisce input vuoti, * e % come wildcard, e confronto case-insensitive (ILIKE).
 * - Converte "sì/si" e "no" per i campi boolean/semaforici (es. verifica).
 * - Per valori numerici consente prefissi >, <, >=, <=, =.
 * - Per date in formato gg/mm/aaaa fa un confronto testuale (coerente con l’originale).
 */
function build_filter_where_clause($conn, array $filters) {
    $clauses = [];
    $params  = [];
    foreach ($filters as $col => $raw) {
        $val = trim((string)$raw);
        if ($val === '') continue;

        $identifier = pg_escape_identifier($conn, $col);

        // Boolean/semafori "si/sì" o "no"
        $low = mb_strtolower($val, 'UTF-8');
        if (in_array($low, ['si','sì','sí','sì','no'], true)) {
            // Confronto testuale sul valore (molte viste espongono 'si'/'no')
            $params[] = $low;
            $clauses[] = "LOWER(CAST($identifier AS TEXT)) = $" . count($params);
            continue;
        }

        // Confronto numerico con operatori
        if (preg_match('/^(<=|>=|=|<|>)\s*([0-9]+(?:[.,][0-9]+)?)$/', $val, $m)) {
            $op = $m[1];
            $num = str_replace(',', '.', $m[2]);
            $params[] = $num;
            // Cast prudente a NUMERIC dove possibile, altrimenti confronto testuale
            $clauses[] = "CASE WHEN $identifier ~ '^[0-9]+(\\.[0-9]+)?$' THEN ($identifier::NUMERIC $op $" . count($params) . ")
                                ELSE (CAST($identifier AS TEXT) ILIKE $" . count($params) . ") END";
            continue;
        }

        // Date semplici gg/mm/aaaa: confronto testuale
        if (preg_match('/^\\d{1,2}\\/\\d{1,2}\\/\\d{4}$/', $val)) {
            $params[] = $val;
            $clauses[] = "CAST($identifier AS TEXT) ILIKE $" . count($params);
            continue;
        }

        // Wildcard * -> %  (ILIKE case-insensitive)
        $like = str_replace('*', '%', $val);
        if (strpos($like, '%') === false && strpos($like, '_') === false) {
            $like = '%' . $like . '%';
        }
        $params[] = $like;
        $clauses[] = "CAST($identifier AS TEXT) ILIKE $" . count($params);
    }

    return [implode(' AND ', $clauses), $params];
}

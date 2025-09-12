<?php
// /src/models/concessione.php
//
// Modello dati per le viste/operazioni su concessioni:
// - elenco paginato e completo
// - conteggio
// - colonne tabella
// - dettaglio record
// - dati per le modali (Dettagli/Modifica)
// - costruzione WHERE dai filtri di colonna (compatibile con l'index monolitico)
//
// Nota: si assume che APP_URL, RECORDS_PER_PAGE e la connessione $conn (pg)
//       siano già disponibili nel flusso che include questo file.
//       La funzione db_query($conn, $sql, $params) è definita in src/lib/database.php.
//       Per sicurezza, qui viene usata solo se esiste.

// -----------------------------------------------------------------------------
// Utilities sicure
// -----------------------------------------------------------------------------
if (!function_exists('db_query')) {
    // Se non è definita dal progetto, forniamo un fallback minimale.
    function db_query($conn, string $sql, array $params = [])
    {
        if ($params) return pg_query_params($conn, $sql, $params);
        return pg_query($conn, $sql);
    }
}

/**
 * Ritorna l'elenco dei nomi colonna per una tabella/vista.
 */
function get_table_columns($conn, string $table): array
{
    $sql = "SELECT * FROM " . pg_escape_identifier($conn, $table) . " LIMIT 0";
    $res = db_query($conn, $sql);
    if (!$res) return [];
    $cols = [];
    for ($i = 0; $i < pg_num_fields($res); $i++) {
        $cols[] = pg_field_name($res, $i);
    }
    return $cols;
}

/**
 * Verifica/normalizza la colonna di ordinamento.
 */
function normalize_order_column($conn, string $table, ?string $order_col): string
{
    $cols = get_table_columns($conn, $table);
    if (empty($cols)) return '1'; // fallback neutro
    if ($order_col && in_array($order_col, $cols, true)) return $order_col;
    return $cols[0];
}

/**
 * Conta i record dopo l’applicazione dei filtri.
 */
function get_records_count($conn, string $table, array $filters): int
{
    list($where, $params) = build_filter_where_clause($conn, $filters);
    $sql = "SELECT COUNT(*) AS total FROM " . pg_escape_identifier($conn, $table) . " " . ($where ? "WHERE $where" : "");
    $res = db_query($conn, $sql, $params);
    if (!$res) return 0;
    return (int)pg_fetch_result($res, 0, 'total');
}

/**
 * Recupera un elenco paginato di record da una tabella/vista.
 */
function get_paginated_records($conn, string $table, int $page, ?string $order_col, string $order_dir, array $filters): array
{
    $order_col = normalize_order_column($conn, $table, $order_col);
    $order_dir = (strtoupper($order_dir) === 'DESC') ? 'DESC' : 'ASC';
    $offset    = max(0, ($page - 1)) * (int)RECORDS_PER_PAGE;

    list($where, $params) = build_filter_where_clause($conn, $filters);

    $sql = sprintf(
        'SELECT * FROM %s %s ORDER BY %s %s NULLS LAST LIMIT %d OFFSET %d',
        pg_escape_identifier($conn, $table),
        $where ? "WHERE {$where}" : '',
        pg_escape_identifier($conn, $order_col),
        $order_dir,
        (int)RECORDS_PER_PAGE,
        (int)$offset
    );

    $res = db_query($conn, $sql, $params);
    return $res ? (pg_fetch_all($res) ?: []) : [];
}

/**
 * Recupera TUTTI i record (vista completa, nessun LIMIT).
 */
function get_all_records($conn, string $table, ?stri

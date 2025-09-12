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
function get_all_records($conn, string $table, ?string $order_col, string $order_dir, array $filters): array
{
    $order_col = normalize_order_column($conn, $table, $order_col);
    $order_dir = (strtoupper($order_dir) === 'DESC') ? 'DESC' : 'ASC';

    list($where, $params) = build_filter_where_clause($conn, $filters);

    $sql = sprintf(
        'SELECT * FROM %s %s ORDER BY %s %s NULLS LAST',
        pg_escape_identifier($conn, $table),
        $where ? "WHERE {$where}" : '',
        pg_escape_identifier($conn, $order_col),
        $order_dir
    );

    $res = db_query($conn, $sql, $params);
    return $res ? (pg_fetch_all($res) ?: []) : [];
}

/**
 * Restituisce un singolo record dalla vista/tabella per IDF24 (per modali/dettaglio).
 */
function get_view_record_by_id($conn, string $table, $idf24): ?array
{
    $sql = 'SELECT * FROM ' . pg_escape_identifier($conn, $table) . ' WHERE idf24::text = $1 LIMIT 1';
    $res = db_query($conn, $sql, [strval($idf24)]);
    if (!$res) return null;
    return pg_fetch_assoc($res) ?: null;
}

/**
 * Dati sintetici per la modale "Dettagli" (lente).
 * Adatta i campi a quelli esibiti dall’index monolitico.
 */
function get_sid_details_model($conn, $idf24): array
{
    // Qui usiamo la vista aggregata, con alcune denominazioni coerenti con l’originale.
    $sql = "
        SELECT
            COALESCE(denominazione, denominazione_concessionario) AS denominazione,
            comune,
            localita,
            COALESCE(tipo_atto_sid, 'Licenza') AS tipo_atto,
            idf24,
            num_sid,
            stato_conc_sid
        FROM demanio.concessioni_unione_v
        WHERE idf24::text = $1
        LIMIT 1
    ";
    $res = db_query($conn, $sql, [strval($idf24)]);
    $row = $res ? (pg_fetch_assoc($res) ?: []) : [];
    return $row;
}

/**
 * Dati per la modale "Modifica" (matita): record completo (sola lettura in questa vista).
 */
function get_concessione_for_edit_model($conn, $idf24): array
{
    $sql = 'SELECT * FROM demanio.concessioni_unione_v WHERE idf24::text = $1 LIMIT 1';
    $res = db_query($conn, $sql, [strval($idf24)]);
    $row = $res ? (pg_fetch_assoc($res) ?: []) : [];
    return ['record' => $row];
}

/**
 * Salvataggio da modale disabilitato in questa vista (si usa “Modifica Concessioni” dedicato).
 */
function save_concessione_model($conn, $original_idf24, array $updates): array
{
    return [
        'success' => false,
        'error'   => 'Il salvataggio diretto dalla modale non è attivo su questa vista. Usa "Modifica Concessioni".'
    ];
}

// -----------------------------------------------------------------------------
// WHERE dinamica a partire dai filtri di colonna (compatibile con monolitico)
// -----------------------------------------------------------------------------

/**
 * Costruisce la WHERE in base ai filtri di colonna.
 * Supporta:
 *  - stringhe con wildcard (* → %) e match case-insensitive (ILIKE)
 *  - valori speciali: 'NULL' / 'NOT_NULL'
 *  - boolean/semafori: 'si/sì' / 'no'
 *  - numeri con operatori: >, <, >=, <=, =
 *  - date semplici in forma gg/mm/aaaa (confronto testuale)
 *
 * Ritorna: [ 'clausola_where', [params] ]
 */
function build_filter_where_clause($conn, array $filters): array
{
    $clauses = [];
    $params  = [];

    foreach ($filters as $col => $raw) {
        // Sanifica nome colonna (può contenere spazi)
        $identifier = pg_escape_identifier($conn, (string)$col);

        // Normalizza valore
        $val = trim((string)$raw);
        if ($val === '') continue;

        // Valori speciali "NULL" / "NOT_NULL"
        $upper = strtoupper($val);
        if ($upper === 'NULL') {
            $clauses[] = "($identifier IS NULL OR NULLIF(CAST($identifier AS TEXT), '') IS NULL)";
            continue;
        }
        if ($upper === 'NOT_NULL') {
            $clauses[] = "($identifier IS NOT NULL AND NULLIF(CAST($identifier AS TEXT), '') IS NOT NULL)";
            continue;
        }

        // booleani / si-no (molte viste espongono 'si'/'no')
        $low = mb_strtolower($val, 'UTF-8');
        if (in_array($low, ['si','sì','sí','sì','no'], true)) {
            $params[] = $low;
            $clauses[] = "LOWER(CAST($identifier AS TEXT)) = $" . count($params);
            continue;
        }

        // Confronto numerico con operatori
        if (preg_match('/^(<=|>=|=|<|>)\s*([0-9]+(?:[.,][0-9]+)?)$/', $val, $m)) {
            $op  = $m[1];
            $num = str_replace(',', '.', $m[2]);
            $params[] = $num;
            // Se appare numerico lo confronto come NUMERIC, altrimenti faccio un match testuale.
            $clauses[] = "CASE WHEN CAST($identifier AS TEXT) ~ '^[0-9]+(\\.[0-9]+)?$'
                               THEN ($identifier::NUMERIC $op $" . count($params) . ")
                               ELSE (CAST($identifier AS TEXT) ILIKE $" . count($params) . ")
                          END";
            continue;
        }

        // Date "gg/mm/aaaa": confronto testuale (coerente con rappresentazioni in vista)
        if (preg_match('/^\\d{1,2}\\/\\d{1,2}\\/\\d{4}$/', $val)) {
            $params[] = $val;
            $clauses[] = "CAST($identifier AS TEXT) ILIKE $" . count($params);
            continue;
        }

        // Default: ILIKE con wildcard; '*' -> '%'
        $like = str_replace('*', '%', $val);
        if (strpos($like, '%') === false && strpos($like, '_') === false) {
            $like = '%' . $like . '%';
        }
        $params[] = $like;
        $clauses[] = "CAST($identifier AS TEXT) ILIKE $" . count($params);
    }

    $where = implode(' AND ', $clauses);
    return [$where, $params];
}

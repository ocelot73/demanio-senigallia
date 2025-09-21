<?php
// =====================================================================
// FILE: /src/models/concessione_model.php (COMPLETO E CORRETTO)
// =====================================================================

/**
 * Recupera i nomi e i tipi delle colonne di una data tabella o vista.
 *
 * @param PgSql\Connection $conn Connessione al database.
 * @param string $table_name Nome della tabella/vista.
 * @return array Un array di colonne.
 */
function get_table_columns($conn, $table_name) {
    if (!$conn) return [];

    // *** CORREZIONE APPLICATA QUI ***
    // Per interrogare information_schema, il nome della tabella e dello schema
    // devono essere trattati come valori stringa, quindi usiamo pg_escape_literal().
    $table_name_literal = pg_escape_literal($conn, $table_name);
    $schema_name_literal = pg_escape_literal($conn, DB_SCHEMA);
    
    $sql = "SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_schema = {$schema_name_literal} AND table_name = {$table_name_literal}
            ORDER BY ordinal_position";
            
    $result = db_query($conn, $sql);

    // Aggiunto controllo per prevenire l'errore fatale in caso di fallimento della query
    if (!$result) {
        error_log("Query per recuperare le colonne fallita per la tabella: " . $table_name);
        return [];
    }
    
    return pg_fetch_all($result) ?: [];
}

/**
 * Recupera tutti i dati da una tabella o vista, applicando filtri e ordinamento.
 *
 * @param PgSql\Connection $conn Connessione al database.
 * @param string $table_name Nome della tabella/vista.
 * @return array Un array di righe di dati.
 */
function get_table_data($conn, $table_name) {
    if (!$conn) return [];

    // Qui pg_escape_identifier è corretto perché il nome della tabella è un identificatore
    // nella clausola FROM.
    $table_name_safe = pg_escape_identifier($conn, $table_name);
    $filters = $_SESSION['column_filters'] ?? [];
    $where_clauses = [];
    $params = [];
    $param_index = 1;

    foreach ($filters as $column => $value) {
        if (!empty($value)) {
            $column_safe = pg_escape_identifier($conn, $column);
            $where_clauses[] = "CAST({$column_safe} AS TEXT) ILIKE $" . $param_index;
            $params[] = '%' . $value . '%';
            $param_index++;
        }
    }

    $sql = "SELECT * FROM {$table_name_safe}";
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $result = db_query($conn, $sql, $params);
    
    if (!$result) {
        error_log("Query per recuperare i dati fallita per la tabella: " . $table_name);
        return [];
    }

    return pg_fetch_all($result) ?: [];
}
?>

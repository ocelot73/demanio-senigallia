<?php
// =====================================================================
// FILE: /src/lib/db.php
// =====================================================================

/**
 * Stabilisce una connessione al database PostgreSQL utilizzando le costanti
 * definite in config/config.php e imposta lo schema di default.
 *
 * @return PgSql\Connection|false La risorsa di connessione in caso di successo, altrimenti false.
 */
function get_db_connection() {
    // Le costanti DB_HOST, DB_PORT, etc., sono definite nel file config.php
    $conn_string = "host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
    
    // Tenta di connettersi al database
    $conn = pg_connect($conn_string);

    if ($conn) {
        // Se la connessione ha successo, imposta lo schema di default per questa sessione
        pg_query($conn, "SET search_path TO " . pg_escape_identifier($conn, DB_SCHEMA));
    } else {
        // Se la connessione fallisce, termina lo script con un errore chiaro
        // In un ambiente di produzione, potresti voler gestire l'errore in modo diverso (es. log)
        error_log("Connessione al database fallita: " . pg_last_error());
        die("Errore: Impossibile connettersi al database. Controllare la configurazione e i log del server.");
    }

    return $conn;
}

/**
 * Esegue una query parametrizzata in modo sicuro.
 *
 * @param PgSql\Connection $conn La risorsa di connessione.
 * @param string $sql La stringa SQL con segnaposto ($1, $2, ...).
 * @param array $params I parametri da associare ai segnaposto.
 * @return PgSql\Result|false Il risultato della query o false in caso di errore.
 */
function db_query($conn, $sql, $params = []) {
    $result = pg_query_params($conn, $sql, $params);
    if (!$result) {
        // Logga l'errore SQL per il debug
        error_log("Errore Query PostgreSQL: " . pg_last_error($conn));
    }
    return $result;
}
?>

<?php
// =====================================================================
// FILE: /src/lib/database.php (CORRETTO E UNIFICATO)
// =====================================================================

/**
 * @var PgSql\Connection|false|null Mantiene una singola istanza della connessione al database (Singleton).
 */
static $db_connection_instance = null;

/**
 * Stabilisce una connessione al database PostgreSQL o restituisce quella già esistente.
 *
 * Questa funzione implementa un pattern Singleton per garantire che venga aperta una sola
 * connessione per ogni richiesta PHP, migliorando le prestazioni. Utilizza le costanti
 * definite nel file /config/config.php.
 *
 * @return PgSql\Connection|false Il resource della connessione al database in caso di successo.
 * @throws Exception Se la connessione al database fallisce, solleva un'eccezione.
 */
function get_db_connection() {
    // Riferimento alla variabile statica globale.
    global $db_connection_instance;

    // Se esiste già una connessione attiva e funzionante, la restituisce immediatamente.
    if ($db_connection_instance !== null && pg_ping($db_connection_instance)) {
        return $db_connection_instance;
    }

    // Costruisce la stringa di connessione usando le costanti di configurazione.
    // *** CORREZIONE: Utilizza DB_PASSWORD invece del non definito DB_PASS ***
    $conn_string = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_USER,
        DB_PASSWORD
    );

    try {
        // Tenta di connettersi al database. L'@ sopprime l'avviso di default di PHP
        // per gestire l'errore in modo personalizzato tramite l'eccezione.
        $connection = @pg_connect($conn_string);

        if ($connection === false) {
            // Solleva un'eccezione chiara se la connessione fallisce.
            throw new Exception("Connessione al database fallita. Controllare le credenziali e la raggiungibilità del server.");
        }

        // Salva la connessione nella variabile statica per usi futuri.
        $db_connection_instance = $connection;

        // Imposta lo schema di default in modo sicuro per tutte le query su questa connessione.
        $schema_safe = pg_escape_identifier($db_connection_instance, DB_SCHEMA);
        db_query($db_connection_instance, "SET search_path TO " . $schema_safe);

        return $db_connection_instance;
    } catch (Exception $e) {
        // In un ambiente di produzione, l'errore verrebbe registrato in un file di log.
        error_log('Errore di connessione al DB: ' . $e->getMessage());
        
        // Mostra un messaggio generico all'utente per non esporre dettagli tecnici.
        die("Errore critico del sistema. Impossibile connettersi al database. Si prega di contattare l'amministratore.");
    }
}

/**
 * Esegue una query SQL parametrizzata in modo sicuro.
 *
 * @param PgSql\Connection|false $conn La risorsa della connessione al database (ottenuta da get_db_connection()).
 * @param string $sql La stringa della query SQL con segnaposto ($1, $2, ...).
 * @param array $params Un array di parametri da associare ai segnaposto.
 * @return PgSql\Result|false Il risultato della query in caso di successo, altrimenti false.
 * @throws Exception Se la query non può essere eseguita.
 */
function db_query($conn, $sql, $params = []) {
    if ($conn === false) {
        throw new Exception("Tentativo di eseguire una query su una connessione al database non valida.");
    }

    // Esegue la query usando i parametri per prevenire SQL injection.
    $result = pg_query_params($conn, $sql, $params);

    if ($result === false) {
        // Registra l'errore dettagliato nei log del server per il debug.
        $error_message = pg_last_error($conn);
        error_log("Errore query SQL: " . $error_message . " | Query: " . $sql);

        // Solleva un'eccezione generica per nascondere i dettagli all'utente.
        throw new Exception("Si è verificato un errore durante l'elaborazione della richiesta.");
    }

    return $result;
}

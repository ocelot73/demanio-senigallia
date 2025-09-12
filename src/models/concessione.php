/**
 * Dati per la modale "Modifica": restituisce il record completo da mostrare.
 * Per semplicità leggiamo dalla vista aggregata; i campi saranno in sola lettura
 * nella modale (il salvataggio diretto non è abilitato qui).
 */
function get_concessione_for_edit_model($conn, $idf24) {
    $sql = 'SELECT * FROM demanio.concessioni_unione_v WHERE idf24::text = $1 LIMIT 1';
    $res = db_query($conn, $sql, [strval($idf24)]);
    $row = pg_fetch_assoc($res) ?: [];
    return ['record' => $row];
}

/**
 * Salvataggio da modale: in questa versione è disabilitato (evitiamo side-effect
 * non desiderati su tabelle fisiche). Viene restituito un messaggio chiaro.
 */
function save_concessione_model($conn, $original_idf24, array $updates) {
    return [
        'success' => false,
        'error'   => 'Il salvataggio diretto dalla modale non è attivo su questa vista. Usa la voce "Modifica Concessioni".'
    ];
}

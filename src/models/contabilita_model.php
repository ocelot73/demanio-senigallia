<?php
// /src/models/contabilita_model.php

/**
 * Recupera l'estratto conto annuale per una data concessione.
 */
function get_estrattoconto_model($conn, $idf24) {
    $sql = "SELECT * FROM demanio.vista_estrattoconto_annuo WHERE idf24 = $1 ORDER BY anno_competenza DESC";
    $result = db_query($conn, $sql, [$idf24]);
    return pg_fetch_all($result) ?: [];
}

/**
 * Recupera la data dell'ultima sincronizzazione per una concessione.
 */
function get_data_sincronizzazione_model($conn, $idf24) {
    $sql = "SELECT TO_CHAR(MAX(data_ultimo_aggiornamento), 'DD/MM/YYYY HH24:MI:SS') as formatted_date 
            FROM demanio.canoni_annuali WHERE idf24 = $1";
    $result = db_query($conn, $sql, [$idf24]);
    return pg_fetch_result($result, 0, 'formatted_date') ?: 'Mai';
}

/**
 * Recupera il dettaglio delle rate da SID per un dato anno/concessione.
 */
function get_dettaglio_rate_sid_model($conn, $idf24, $anno) {
    $sql = "SELECT * FROM demanio.mv_rate_canone WHERE idf24 = $1 AND anno_rata = $2 ORDER BY numero_rata";
    $result = db_query($conn, $sql, [$idf24, $anno]);
    return pg_fetch_all($result) ?: [];
}

/**
 * Recupera i solleciti inviati per un dato canone annuale.
 */
function get_solleciti_model($conn, $id_canone_annuale) {
    $sql = "SELECT *, TO_CHAR(data_invio, 'DD/MM/YYYY') as data_invio_fmt, TO_CHAR(data_scadenza, 'DD/MM/YYYY') as data_scadenza_fmt FROM demanio.solleciti WHERE id_canone_annuale = $1 ORDER BY data_invio DESC";
    $result = db_query($conn, $sql, [$id_canone_annuale]);
    return pg_fetch_all($result) ?: [];
}

/**
 * Salva un nuovo sollecito nel database.
 */
function save_sollecito_model($conn, $data) {
    // Calcola la data di scadenza sul backend per sicurezza
    $data_invio = new DateTime($data['data_invio']);
    $data_invio->add(new DateInterval('P' . intval($data['giorni']) . 'D'));
    $data_scadenza = $data_invio->format('Y-m-d');

    $sql = "INSERT INTO demanio.solleciti 
                (id_canone_annuale, livello_sollecito, data_invio, giorni_scadenza, data_scadenza, protocollo, importo_sollecitato)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
            RETURNING id";
    
    $params = [
        $data['id_canone_annuale'],
        $data['livello'],
        $data['data_invio'],
        $data['giorni'],
        $data_scadenza,
        empty($data['protocollo']) ? null : $data['protocollo'],
        $data['importo']
    ];
    
    $result = db_query($conn, $sql, $params);
    return pg_fetch_result($result, 0, 'id');
}
?>

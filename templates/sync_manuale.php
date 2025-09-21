<?php // /templates/sync_manuale.php ?>
<div class="detail-header">
    <h2><i class="fas fa-sync-alt"></i> Sincronizzazione Manuale Dati Contabili</h2>
    <p>
        Usa questa funzione per forzare un aggiornamento dei dati contabili (richiesti e versati)
        attingendo direttamente dalla vista materializzata <code>demanio.mv_rate_canone</code>.
    </p>
</div>
<div class="card-container glass-effect" id="sync-container">
    <p>
        Clicca il pulsante qui sotto per avviare il processo. L'operazione potrebbe richiedere
        alcuni secondi. L'aggiornamento avviene anche automaticamente dopo ogni importazione da SID.
    </p>
    <button id="start-sync-btn" class="btn btn-primary">
        <i class="fas fa-play-circle"></i> Avvia Sincronizzazione
    </button>
    <div id="sync-result" style="display:none; margin-top: 1.5rem;"></div>
</div>

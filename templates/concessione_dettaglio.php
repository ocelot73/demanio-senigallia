<?php // /templates/concessione_dettaglio.php ?>
<div class="detail-header">
    <a href="index.php?page=concessioni" class="back-link"><i class="fas fa-arrow-left"></i> Torna all'elenco</a>
    <h2>
        <i class="fas fa-file-invoice"></i> 
        Fascicolo Concessione: <?= htmlspecialchars($concessione['idf24']) ?>
    </h2>
    <p><strong>Titolare:</strong> <?= htmlspecialchars($concessione['denominazione ditta concessionario']) ?></p>
</div>

<div class="card-container glass-effect">
    <div class="tab-nav">
        <button class="tab-link active" data-tab="tab-contabilita">Contabilità e Solleciti</button>
        <button class="tab-link" data-tab="tab-anagrafica">Dati Anagrafici</button>
    </div>

    <div class="tab-content active" id="tab-contabilita">
        <div class="loading-placeholder">
            <i class="fas fa-spinner fa-spin"></i> Caricamento dati contabili...
        </div>
    </div>
    
    <div class="tab-content" id="tab-anagrafica">
        <p>Qui verranno visualizzati i dettagli anagrafici completi della concessione. Questa funzionalità può essere espansa in futuro.</p>
    </div>
</div>

<script type="text/template" id="form-sollecito-template">
    <form id="form-sollecito" class="form-sollecito">
        <h5>Nuovo Sollecito</h5>
        <input type="hidden" id="form_id_canone_annuale" value="__ID_CANONE_ANNUALE__">
        <input type="hidden" id="form_importo_sollecitato" value="__IMPORTO_SOLLECITATO__">
        <div class="form-group">
            <label for="form_livello_sollecito">Livello</label>
            <select id="form_livello_sollecito" class="control-input" required>
                <option value="Richiesta Iniziale">Richiesta Iniziale</option>
                <option value="Primo Sollecito">Primo Sollecito</option>
                <option value="Secondo Sollecito">Secondo Sollecito</option>
                <option value="Invio Riscossione Coattiva">Invio Riscossione Coattiva</option>
            </select>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label for="form_data_invio">Data Invio</label>
                <input type="date" id="form_data_invio" class="control-input" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label for="form_giorni_scadenza">Giorni Scadenza</label>
                <input type="number" id="form_giorni_scadenza" class="control-input" required value="20" min="1">
            </div>
        </div>
        <small id="calcolo-data-scadenza" class="form-hint"></small>
        <div class="form-group">
            <label for="form_protocollo">Protocollo (facoltativo)</label>
            <input type="text" id="form_protocollo" class="control-input">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salva Sollecito</button>
    </form>
</script>

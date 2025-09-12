<?php // /templates/importa.php ?>
<div class="import-container">
    <div class="card-container" id="uploaderCard">
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" id="zipfile" name="zipfile" accept=".zip,application/zip" required hidden>
            <div id="drop-zone">
                <span class="icon"><i class="fas fa-file-zipper"></i></span>
                <p>Trascina qui il file ZIP estratto da SID o <span class="browse-link">SFOGLIA</span>.</p>
            </div>
            <div id="fileInfo" style="display: none;">
                <i class="fas fa-check-circle" style="color: var(--color-success);"></i>
                <span id="fileName"></span>
            </div>
            <button type="submit" id="uploadButton" class="btn btn-primary" disabled><i class="fas fa-cogs"></i> Avvia Elaborazione</button>
        </form>
    </div>
    <div class="card-container" id="progressCard" style="display: none;">
        <div id="progress-bar-container"><div id="progress-bar"></div></div>
        <div id="progress-text">In attesa di avvio...</div>
        <div id="logContainer"></div>
        <div class="final-actions" id="finalActions" style="display: none;">
            <a href="index.php?page=concessioni" class="btn"><i class="fas fa-arrow-left"></i> Torna alla Gestione Concessioni</a>
        </div>
    </div>
</div>

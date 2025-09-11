// /public/assets/js/app.js

/** Config HELP e stato colonne (da PHP) */
const FIELD_HELP = <?= json_encode($FIELD_HELP ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const hiddenColumns = <?= json_encode($hidden_columns ?? [], JSON_UNESCAPED_UNICODE) ?>;

// --- Funzioni di Utilità ---
function updateColumnOrder(){ let order=$('#dataTable thead th[data-column]').map(function(){return $(this).data('column');}).get(); $.post(window.location.href,{column_order:order}); }
function toggleColumn(n){ $.post(window.location.href,{toggle_column:n}, r=>{ if(r.success) location.reload(); },'json'); }
function saveColumnWidths(){ let w={}; $('#dataTable thead th[data-column]').each(function(){const n=$(this).data('column'); if(n) w[n]=$(this).outerWidth();}); $.post(window.location.href,{column_widths:w}); }
function applyFilter(n,v){ $.post(window.location.href,{set_filter:n,filter_value:v}, r=>{ if(r.success) location.reload(); },'json'); }

// --- Gestione UI Globale ---
$(document).ready(function() {
    // Gestione Sidebar, Modali, Tema Chiaro/Scuro (incolla qui il blocco dal vecchio index.php o dalle risposte precedenti)
    // ...
    
    // --- Logica Specifica per la Pagina di Importazione ---
    const uploaderCard = document.getElementById('uploaderCard');
    if (uploaderCard) {
        // --- CORREZIONE: Inserito tutto il blocco JS per l'importazione dal vecchio file ---
        const progressCard = document.getElementById('progressCard'),
              zipFileInput = document.getElementById('zipfile'),
              dropZone = document.getElementById('drop-zone'),
              fileInfo = document.getElementById('fileInfo'),
              fileNameDisplay = document.getElementById('fileName'),
              uploadButton = document.getElementById('uploadButton');
        const progressText = document.getElementById('progress-text'),
              logContainer = document.getElementById('logContainer'),
              finalActions = document.getElementById('finalActions'),
              progressBar = document.getElementById('progress-bar');
        let eventSource = null;

        const setupUploader = () => {
            const browseLink = dropZone.querySelector('.browse-link');
            dropZone.onclick = (e) => {
                if (e.target === browseLink) e.preventDefault();
                zipFileInput.click();
            };
            dropZone.ondragover = (e) => { e.preventDefault(); dropZone.classList.add('dragover'); };
            dropZone.ondragleave = () => dropZone.classList.remove('dragover');
            dropZone.ondrop = (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                if (e.dataTransfer.files.length && (e.dataTransfer.files[0].type.includes('zip') || e.dataTransfer.files[0].name.endsWith('.zip'))) {
                    zipFileInput.files = e.dataTransfer.files;
                    handleFileSelection();
                } else {
                    alert('Per favore, seleziona un file in formato ZIP.');
                }
            };
            zipFileInput.onchange = handleFileSelection;
        };

        function handleFileSelection() {
            if (zipFileInput.files.length) {
                fileNameDisplay.textContent = zipFileInput.files[0].name;
                fileInfo.style.display = 'flex';
                uploadButton.disabled = false;
            }
        }

        document.getElementById('uploadForm').onsubmit = (e) => {
            e.preventDefault();
            if (!zipFileInput.files.length) return;

            uploaderCard.style.display = 'none';
            progressCard.style.display = 'block';
            updateLog('info', 'Preparazione e avvio caricamento file...');

            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('zipfile', zipFileInput.files[0]);

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 5;
                    updateProgress(percentComplete, `Fase 1/5: Caricamento file... ${Math.round((event.loaded / event.total) * 100)}%`);
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            startSseProcessing(result.processId);
                        } else {
                            finishProcess('error', result.error || 'Errore sconosciuto durante il caricamento');
                        }
                    } catch (e) {
                        finishProcess('error', 'Risposta del server non valida: ' + xhr.responseText);
                    }
                } else {
                    finishProcess('error', `Errore del server: ${xhr.status} ${xhr.statusText}`);
                }
            };

            xhr.onerror = () => {
                finishProcess('error', 'Errore di rete durante il caricamento del file.');
            };

            // CORREZIONE: URL AJAX corretto
            const uploadUrl = new URL(window.location.href);
            uploadUrl.searchParams.set('action', 'import_zip');
            
            xhr.open('POST', uploadUrl.toString(), true);
            xhr.send(formData);
        };

        function startSseProcessing(processId) {
            const url = new URL(window.location.href);
            url.searchParams.set('action', 'process');
            url.searchParams.set('id', processId);
            eventSource = new EventSource(url.toString());

            eventSource.addEventListener('log', e => {
                const data = JSON.parse(e.data);
                updateLog(data.status, data.message);
            });
            eventSource.addEventListener('progress', e => {
                const data = JSON.parse(e.data);
                updateProgress(data.value, data.text);
            });
            eventSource.addEventListener('close', e => {
                const data = JSON.parse(e.data);
                finishProcess(data.status, data.message);
            });
            eventSource.onerror = (e) => {
                if (eventSource.readyState === EventSource.CLOSED) {
                    console.log("Connessione SSE chiusa correttamente.");
                } else {
                    finishProcess('error', 'Connessione con il server interrotta. Ricaricare la pagina.');
                }
            };
        }

        const iconMap = { info: 'fas fa-info-circle', success: 'fas fa-check-circle', warning: 'fas fa-exclamation-triangle', error: 'fas fa-times-circle' };
        function updateLog(status, message) {
            const item = document.createElement('div');
            item.className = `log-item status-${status}`;
            item.innerHTML = `<i class="icon ${iconMap[status] || ''}"></i><span class="message">${message}</span>`;
            logContainer.appendChild(item);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateProgress(value, text) {
            progressBar.style.width = `${Math.min(value, 100)}%`;
            progressText.textContent = text;
        }

        function finishProcess(status, message) {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            updateProgress(100, "Completato");
            updateLog(status, `<strong>${message}</strong>`);
            progressBar.classList.remove('error', 'warning');
            if (status === 'error') progressBar.classList.add('error');
            if (status === 'warning') progressBar.classList.add('warning');
            finalActions.style.display = 'block';
        }

        setupUploader();
    }
    
    // Incolla qui il resto del codice JS (gestione tabella, modali, etc.) dalle risposte precedenti
    // ...
});

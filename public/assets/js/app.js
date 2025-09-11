// /public/assets/js/app.js

$(document).ready(function() {

    // --- GESTIONE UI GLOBALE (SIDEBAR, TEMA, MODALI) ---
    $('#sidebar-toggle').on('click', function() {
        const body = document.body;
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    });

    const openModal = (modalId) => $(`#${modalId}`).css('display','flex').delay(10).queue(function(next){ $(this).addClass('open'); next(); });
    const closeModal = (modalId) => {
        const $modal = $(`#${modalId}`);
        $modal.removeClass('open');
        setTimeout(() => { $modal.css('display', 'none'); $('.help-popup').remove(); }, 300);
    };

    $('.modal-overlay, .modal-close-btn, #editCancelBtn').on('click', function(e) {
        if (e.target === this) {
            closeModal($(this).closest('.modal-overlay').attr('id'));
        }
    });
    $('.modal-container').on('click', e => e.stopPropagation());

    $('.submenu-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).parent('.has-submenu').toggleClass('open');
    });

    const themeToggle = $('#theme-toggle');
    function setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark-theme');
            themeToggle.find('i').removeClass('fa-moon').addClass('fa-sun');
            themeToggle.find('.link-text').text('Tema Chiaro');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark-theme');
            themeToggle.find('i').removeClass('fa-sun').addClass('fa-moon');
            themeToggle.find('.link-text').text('Tema Scuro');
            localStorage.setItem('theme', 'light');
        }
    }
    setTheme(localStorage.getItem('theme') || 'light');
    themeToggle.on('click', () => setTheme(document.documentElement.classList.contains('dark-theme') ? 'light' : 'dark'));

    // --- GESTIONE TABELLA ---
    // (Include qui tutta la logica per la tabella: filtri, ordinamento, modali, etc., dalle risposte precedenti)
    // ...

    // --- LOGICA SPECIFICA PER LA PAGINA DI IMPORTAZIONE ---
    const uploaderCard = document.getElementById('uploaderCard');
    if (uploaderCard) {
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
    
});

import './bootstrap';

// --- Utils ---
function escapeHtml(text) {
    if (!text) return text;
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getFileIcon(fileType) {
    if (!fileType) return 'file';
    if (fileType.includes('pdf')) return 'file-text';
    if (fileType.includes('image')) return 'image';
    if (fileType.includes('word') || fileType.includes('document')) return 'file-text';
    return 'file';
}

function humanFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}


document.addEventListener("DOMContentLoaded", function() {
    // --- Elements ---
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const chatMessages = document.getElementById('chat-messages');
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatbotOverlay = document.getElementById('chatbot-overlay');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotBackdrop = document.getElementById('chatbot-backdrop');
    const chatbotPanel = document.getElementById('chatbot-panel');
    
    // File upload elements
    const attachBtn = document.getElementById('attach-btn');
    const fileElem = document.getElementById('fileElem');
    const dropArea = document.getElementById('drop-area');
    const attachmentList = document.getElementById('attachment-list');
    const attachmentChips = document.getElementById('attachment-chips');

    // --- State ---
    let isAwaitingResponse = false;
    let pendingFiles = [];
    window.pendingJobs = []
    let currentController = null; // AbortController per fermare la generazione

    chatbotToggle.addEventListener('click', () => toggleChat(true));
    chatbotClose.addEventListener('click', () => toggleChat(false));
    chatbotBackdrop.addEventListener('click', () => toggleChat(false));

    // --- Auto-resize Textarea ---
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(this.value === '') this.style.height = '40px';
    });

    const userIdMeta = document.querySelector('meta[name="user-id"]');

    if (userIdMeta) {

        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        const attachBtn = document.getElementById('attach-btn');

        function setChatUIEnabled(isEnabled) {
            chatInput.disabled = !isEnabled;
            sendBtn.disabled = !isEnabled;
            attachBtn.disabled = !isEnabled;
            chatInput.placeholder = isEnabled ? "Scrivi un messaggio..." : "AI sta elaborando...";
            if(isEnabled) sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            else sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }

        const userId = userIdMeta.content;

        window.Echo.private('doctors.' + userId)
            .listen('.message.sent', (e) => {
                const batch = window.activeBatchProcess;
                console.log(e)
                if (batch && batch.jobs.includes(e.message.job)) {
            
                batch.completed++;
                
                const percent = (batch.completed / batch.total) * 100;
                
                $(`#${batch.uiId}-bar`).css('width', percent + '%');
                $(`#${batch.uiId}-count`).text(`${batch.completed}/${batch.total}`);
                $(`.loader-spin`).remove()
                let index = batch.jobs.indexOf(e.message.job);
                if (index !== -1) batch.jobs.splice(index, 1);

                if (batch.completed >= batch.total) {
                    $(`#${batch.uiId}-label`).text("Elaborazione Completata! ✅");
                    $(`#${batch.uiId}-bar`).css('background-color', '#10b981'); // Verde
                    
                    window.activeBatchProcess = null;
                    
                    setChatUIEnabled(true);
                    isAwaitingResponse = false;
                }
            }
        });
    }

    // --- File Handling ---
    attachBtn.addEventListener('click', () => fileElem.click());
    
    fileElem.addEventListener('change', function() {
        handleFiles(this.files);
        this.value = ''; // Reset per permettere di riselezionare lo stesso file
    });

    // Drag & Drop
    const preventDefaults = (e) => { e.preventDefault(); e.stopPropagation(); };
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        chatbotPanel.addEventListener(eventName, preventDefaults, false);
    });

    chatbotPanel.addEventListener('dragenter', () => { 
        if(!isAwaitingResponse) dropArea.classList.remove('hidden'); 
    });
    
    dropArea.addEventListener('dragleave', () => dropArea.classList.add('hidden'));
    
    dropArea.addEventListener('drop', (e) => {
        dropArea.classList.add('hidden');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (!files.length) return;
        pendingFiles = [...pendingFiles, ...Array.from(files)];
        renderAttachments();
    }

    function renderAttachments() {
        attachmentChips.innerHTML = '';
        if (pendingFiles.length > 0) {
            attachmentList.classList.remove('hidden');
            pendingFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full px-3 py-1 text-xs shadow-sm';
                div.innerHTML = `
                    <i data-feather="${getFileIcon(file.type)}" class="w-3 h-3 text-blue-500"></i>
                    <span class="truncate max-w-[100px] text-gray-700 dark:text-gray-200">${file.name}</span>
                    <span class="text-[10px] text-gray-400">${humanFileSize(file.size)}</span>
                    <button class="ml-1 text-gray-400 hover:text-red-500 remove-file" data-index="${index}">
                        <i data-feather="x" class="w-3 h-3"></i>
                    </button>
                `;
                attachmentChips.appendChild(div);
            });
            feather.replace();
            
            document.querySelectorAll('.remove-file').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const idx = parseInt(e.currentTarget.dataset.index);
                    pendingFiles.splice(idx, 1);
                    renderAttachments();
                });
            });
        } else {
            attachmentList.classList.add('hidden');
        }
    }

    const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

    // --- Sending Logic ---
    async function sendMessage() {
        const text = chatInput.value.trim();
        if ((!text && pendingFiles.length === 0) || isAwaitingResponse) return;

        // 1. UI Updates
        isAwaitingResponse = true;
        setChatUIEnabled(false);
        chatInput.value = '';
        chatInput.style.height = '40px';

        // Add User Message
        appendUserMessage(text, pendingFiles);
        
        // Reset Files
        const filesToUpload = [...pendingFiles];
        pendingFiles = [];
        renderAttachments();

        // 2. Add AI Placeholder (Thinking state)
        let msgId = `ai-${Date.now()}`;
        appendAILoading(msgId);
        scrollBottom();

        // 3. Build History & Send
        try {
            if (filesToUpload.length > 0) {
                const totalFiles = filesToUpload.length;
                const formData = new FormData();
                filesToUpload.forEach(file => {
                    formData.append('documents[]', file); 
                });
                replaceLoadingWithContainer(msgId);

                // Testo iniziale
                for (const word of "🤖 Caricamento in corso dei documenti nel sistema...".split(" ")) {
                    handleStreamChunk(msgId, { type: "content", content: word + " " });
                    await wait(Math.floor(Math.random() * 50));
                }

                // --- SETUP UI PROGRESS BAR ---
                const progressId = `pyspark-prog-${Date.now()}`;
                const progressHTML = `
                    <div id="${progressId}-container" style="margin-top: 15px; width: 100%; max-width: 320px;">
                        <style>
                            @keyframes spin-icon { 100% { transform: rotate(360deg); } }
                            .loader-spin { animation: spin-icon 2s linear infinite; }
                        </style><div style="display:flex; justify-content:space-between; align-items:center; font-size: 13px; margin-bottom: 6px; font-weight: 500; color: #ffffff;">
                            <div style="display: flex; align-items: center; opacity: 0.9;">
                                <i data-feather="loader" class="loader-spin" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                                <span id="${progressId}-label">In attesa...</span>
                            </div>
                            <span id="${progressId}-count" style="font-feature-settings: 'tnum'; font-variant-numeric: tabular-nums;">0%</span>
                        </div><div style="background-color: rgba(255, 255, 255, 0.2); border-radius: 9999px; height: 6px; overflow: hidden; width: 100%;">
                            <div id="${progressId}-bar" style="background-color: #3b82f6; width: 0%; height: 100%; transition: width 0.3s ease-out, background-color 0.3s;"></div>
                        </div>
                    </div>
                `;
                
                handleStreamChunk(msgId, {
                    type: "content",
                    content: progressHTML
                });

                msgId = `ai-${Date.now()}`;
                setChatUIEnabled(false)
                isAwaitingResponse = true;
                scrollBottom();

                const response = await $.ajax({
                    url: '/api/document',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    // 1. FASE UPLOAD: Monitoriamo l'invio fisico dei file
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percent = Math.round((evt.loaded / evt.total) * 100);
                                $(`#${progressId}-bar`).css('width', percent + '%');
                                $(`#${progressId}-count`).text(percent + '%');
                            }
                        }, false);
                        return xhr;
                    }
                });

                // 2. FASE PREPARAZIONE SOCKET
                window.pendingJobs = response.response.jobs;
                
                // Aggiorniamo la UI per mostrare che siamo in attesa dell'elaborazione
                $(`#${progressId}-label`).text("Elaborazione PySpark...");
                $(`#${progressId}-bar`).css('width', '0%'); // Resettiamo la barra o la mettiamo a pulsare
                $(`#${progressId}-bar`).css('background-color', '#8b5cf6'); // Cambiamo colore (es. viola per AI)
                $(`#${progressId}-count`).text(`0/${totalFiles}`);

                // --- PUNTO CHIAVE ---
                // Esponiamo una funzione globale (o salviamo un riferimento) che la tua Socket chiamerà
                // Mappiamo i Job ID all'ID della progress bar nell'interfaccia
                if (!window.activeBatchProcess) window.activeBatchProcess = {};
                
                // Salviamo lo stato di questo batch
                window.activeBatchProcess = {
                    uiId: progressId,
                    total: totalFiles,
                    completed: 0,
                    jobs: response.response.jobs // Array di ID [101, 102, 103]
                };

                return;
            }
            const messages = getChatHistory();
            
            currentController = new AbortController();
            
            // Endpoint localhost:8085 come da tua configurazione
            const response = await fetch('http://localhost:8085/chat', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ messages: messages }),
                signal: currentController.signal
            });

            if (!response.ok) throw new Error("Network response was not ok");

            // 4. Handle Streaming
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            
            let buffer = "";
            let isFirstChunk = true;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                buffer += chunk;
                
                const lines = buffer.split('\n');
                buffer = lines.pop(); 

                for (const line of lines) {
                    const data = JSON.parse(line)
                    if (!line.trim()) continue;
                    try {
                        console.log(msgId, data.type)
                        
                        // Gestione primo chunk se necessario (es. cleanup iniziale)
                        if (isFirstChunk) {
                            isFirstChunk = false;
                            replaceLoadingWithContainer(msgId);
                        }

                        // Skip solo se contenuto vuoto E NON è un'azione
                        if ((!data.content && data.type !== 'action') || data.content === null) continue;

                        handleStreamChunk(msgId, data);
                    } catch (e) {
                        console.warn("Error:", e);
                    }
                }
            }

        } catch (error) {
            console.error("Errore chat:", error);
            if (error.name !== 'AbortError') {
                const errorContainer = document.getElementById(msgId);
                if(errorContainer) {
                     errorContainer.innerHTML = `<div class="p-3 text-sm text-red-500 bg-red-50 rounded-lg">Errore di connessione al server locale.</div>`;
                }
            }
        } finally {
            if (filesToUpload.length > 0) return; // Sarà l'evento websocket a sbloccarmi la situazione.
            isAwaitingResponse = false;
            setChatUIEnabled(true);
            currentController = null;
            scrollBottom();
            setTimeout(() => chatInput.focus(), 100);
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // --- Helpers ---

    function scrollBottom() {
        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
    }

    function toggleChat(show) {
        if (show) {
            chatbotOverlay.classList.remove('invisible', 'opacity-0');
            chatbotPanel.classList.remove('translate-x-full');
            document.body.style.overflow = 'hidden'; // Previeni scroll background
            setTimeout(() => chatInput.focus(), 300);
        } else {
            chatbotOverlay.classList.add('invisible', 'opacity-0');
            chatbotPanel.classList.add('translate-x-full');
            document.body.style.overflow = '';
        }
    }

    function setChatUIEnabled(isEnabled) {
        chatInput.disabled = !isEnabled;
        sendBtn.disabled = !isEnabled;
        attachBtn.disabled = !isEnabled;
        chatInput.placeholder = isEnabled ? "Scrivi un messaggio..." : "AI sta elaborando...";
        if(isEnabled) sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        else sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    function getChatHistory() {
        const history = [];
        // Selezioniamo tutti i messaggi che hanno l'attributo data-role
        const messages = chatMessages.querySelectorAll('[data-role]');
        
        messages.forEach(msg => {
            const role = msg.getAttribute('data-role');
            let content = "";
            
            if (role === 'user') {
                // Per l'utente il testo è diretto nel paragrafo
                content = msg.querySelector('p')?.innerText || "";
            } else {
                // Per l'assistente, prendiamo il contenuto markdown (escludendo il thinking)
                const contentDiv = msg.querySelector('.ai-content');
                content = contentDiv ? contentDiv.innerText : "";
            }

            if (content.trim()) {
                history.push({ role: role, content: content });
            }
        });
        
        return history;
    }

    function appendUserMessage(text, files) {
        const div = document.createElement('div');
        div.className = 'flex justify-end mb-4 animate-fade-in-up';
        div.setAttribute('data-role', 'user'); // Importante per l'history scraper
        
        let fileHTML = '';
        if (files.length > 0) {
            fileHTML = `<div class="bg-blue-700/50 rounded p-2 mb-2 text-xs text-blue-100 flex flex-col gap-1">
                ${files.map(f => `<div class="flex items-center gap-1"><i data-feather="${getFileIcon(f.type)}" class="w-3 h-3"></i> ${f.name}</div>`).join('')}
            </div>`;
        }

        div.innerHTML = `
            <div class="bg-blue-600 text-white rounded-t-xl rounded-bl-xl rounded-br-none p-3 max-w-[85%] shadow-md">
                ${fileHTML}
                <p class="text-sm whitespace-pre-wrap leading-relaxed">${escapeHtml(text)}</p>
            </div>
        `;
        chatMessages.appendChild(div);
        feather.replace();
    }

    function appendAILoading(id) {
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex justify-start mb-4';
        div.setAttribute('data-role', 'assistant'); // Placeholder role
        div.innerHTML = `
            <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-t-xl rounded-br-xl rounded-bl-none p-4 shadow-sm w-auto">
                <div class="flex space-x-1.5 items-center">
                    <div class="w-2 h-2 bg-blue-400 rounded-full loading-dot"></div>
                    <div class="w-2 h-2 bg-blue-400 rounded-full loading-dot" style="animation-delay: 0.2s"></div>
                    <div class="w-2 h-2 bg-blue-400 rounded-full loading-dot" style="animation-delay: 0.4s"></div>
                </div>
            </div>
        `;
        chatMessages.appendChild(div);
    }

    function replaceLoadingWithContainer(id) {
        const container = document.getElementById(id);
        if (!container) return;
        
        container.innerHTML = `
            <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-t-xl rounded-br-xl rounded-bl-none p-3 max-w-[90%] shadow-sm overflow-hidden text-sm">
                <div class="ai-thinking-container hidden mb-2">
                    <details class="ai-thinking-box">
                        <summary class="ai-thinking-summary text-xs flex flex-row items-center">
                            <i data-feather="cpu" class="w-3 h-3"></i> <span>Processo di pensiero</span>
                        </summary>
                        <div class="ai-thinking-content mt-2 text-xs font-mono leading-relaxed"></div>
                    </details>
                </div>
                <div class="ai-content chat-markdown text-gray-800 dark:text-gray-100 leading-relaxed"></div>
            </div>
        `;
        feather.replace();
    }
    // --- Stream Logic ---

    function handleStreamChunk(msgId, data) {
        const container = document.getElementById(msgId);
        if (!container) return;

        const thinkingContainer = container.querySelector('.ai-thinking-container');
        const thinkingContent = container.querySelector('.ai-thinking-content');
        const contentDiv = container.querySelector('.ai-content');
        switch (data.type) {
            case 'thinking':
            case 'reasoning_xml':
                thinkingContainer.classList.remove('hidden');
                if (typeof thinkingContent._rawMarkdown === 'undefined') {
                    thinkingContent._rawMarkdown = "";
                }
                thinkingContent._rawMarkdown += data.content;
                thinkingContent.innerHTML = marked.parse(thinkingContent._rawMarkdown).trim();
                scrollBottom();
                break;
            case 'TOOL_USE':
                console.log(data)
                $(thinkingContainer).appendChild(`
                    <i data-feather="tool" class="w-3 h-3"></i> Tool calling
                `)
                break;

            case 'content':
                if (typeof contentDiv._rawMarkdown === 'undefined') {
                    contentDiv._rawMarkdown = "";
                }
                contentDiv._rawMarkdown += data.content;
                contentDiv.innerHTML = marked.parse(contentDiv._rawMarkdown);
                scrollBottom();
                break;

            // case 'action':
            //     handleAIAction(data.action, data.payload);
            //     break;
        }
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    // // --- Action Handler (Action Dispatcher) ---
    // function handleAIAction(actionName, payload) {
    //     console.log(`Executing AI Action: ${actionName}`, payload);
        
    //     switch(actionName) {
    //         case 'open_modal':
    //             const modal = document.querySelector(payload.target);
    //             if(modal) {
    //                 modal.classList.remove('invisible', 'opacity-0');
    //                 if(typeof Swal !== 'undefined') {
    //                     Swal.fire({
    //                         toast: true, position: 'top-end', icon: 'success', 
    //                         title: 'Modulo aperto', showConfirmButton: false, timer: 2000
    //                     });
    //                 }
    //             }
    //             break;
            
    //         case 'fill_form':
    //             const form = document.getElementById(payload.formId);
    //             if(form && payload.fields) {
    //                 Object.entries(payload.fields).forEach(([key, value]) => {
    //                     const input = form.querySelector(`[name="${key}"]`);
    //                     if(input) input.value = value;
    //                 });
    //             }
    //             break;

    //         case 'redirect':
    //             window.location.href = payload.url;
    //             break;

    //         case 'alert':
    //             if(typeof Swal !== 'undefined') {
    //                 Swal.fire({
    //                     title: payload.title || 'Info',
    //                     text: payload.text,
    //                     icon: payload.icon || 'info'
    //                 });
    //             } else {
    //                 alert(payload.text);
    //             }
    //             break;
    //     }
    // }
});
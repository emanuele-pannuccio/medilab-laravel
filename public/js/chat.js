function humanFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024,
        sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function getFileIcon(fileType) {
    if (!fileType) return 'file';
    if (fileType.includes('pdf')) return 'file-text';
    if (fileType.includes('image')) return 'image';
    if (fileType.includes('word') || fileType.includes('document')) return 'file-text';
    return 'file';
}

function scrollBottom() {
    const $chatMessages = $('#chat-messages');
    if ($chatMessages.length) {
        $chatMessages.scrollTop($chatMessages[0].scrollHeight);
    }
}

function addAIResponse(text, msgId) {
    let $msgElement = $('#' + msgId);
    let $bubble;

    if ($msgElement.length === 0) {
        const $aiDiv = $('<div/>', { 
            class: 'flex justify-start', 
            id: msgId,
        });
        
        $bubble = $('<div/>', { 
            class: 'msg bot bg-gray-100 dark:bg-gray-700 rounded-lg p-3 max-w-xs' 
        });

        $aiDiv.append($bubble);
        $('#chat-messages').append($aiDiv);
    } else {
        $bubble = $msgElement.find('div').first();
        $bubble.empty();
    }

    if (text === null) {
        // Loading dots
        const $loadingDots = $(`
            <div class="flex space-x-1.5 p-1">
                <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
            </div>
        `);
        $bubble.append($loadingDots);
    } else {
        const $p = $('<p/>', {
            class: 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap',
            text: text
        });
        $bubble.append($p);
    }
    scrollBottom();
}

function appendToAIMessage(msgId, textChunk) {
    const msgDiv = document.getElementById(msgId);
    if (msgDiv) {
        const p = msgDiv.querySelector('p');
        if (p) {
            p.textContent += textChunk;
            scrollBottom();
        }
    }
}

$(function() {
    // Inizializza icone
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    let pendingFiles = [];

    var awaitingResponse = false;
        
    $chatInput = $('#chat-input')

    async function onSend() {
        if(awaitingResponse) return;
        const text = $chatInput.val().trim();
        
        const filesToSend = [...pendingFiles];
        
        if (text === '' && filesToSend.length === 0) return;
        
        $chatInput.val("");
        pendingFiles = [];

        if (filesToSend.length > 0) {
            addUserFilesMessage(filesToSend, text);
            filesToSend.forEach(file => {
                var formData = new FormData();
                formData.append('document', file);
            })
        } else {
            addUserTextMessage(text);
        }

        const msgId = `msg-${Date.now()}`;

        try {
            var messages = []
            $("#chat-messages .msg").toArray().forEach(elem => {
                messages.push(
                    {
                        role: $(elem).hasClass("user") ? "user" : "assistant",
                        content: $(elem).text()
                    }
                )
            })
            sendChat(messages)
        } catch (error) {
            console.error("Errore chat:", error);
            addAIResponse("Spiacente, si è verificato un errore di connessione.", msgId);
        } finally {
            isAwaitingResponse = false;
            setChatUIEnabled(true);
            $chatInput.focus();
        }
    }
    
    async function sendChat(messages) {
        try {
            isAwaitingResponse = true;
            setChatUIEnabled(false);
            const response = await fetch('http://localhost:8085/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ messages: messages })
            });

            if (!response.ok) throw new Error("Network response was not ok");

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            let isFirstChunk = true;
            let buffer = "";
            
            const msgId = $("#chat-messages .msg").length;
            console.log(msgId)
            addAIResponse(null, msgId); 

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunkText = decoder.decode(value, { stream: true });
                buffer += chunkText;
                let lines = buffer.split('\n');

                // L'ultima linea potrebbe essere incompleta
                buffer = lines.pop();

                for (const line of lines) {
                    if (line.trim() === "") continue;
                    try {
                        const jsonMessage = JSON.parse(line);
                        console.log(jsonMessage);
                        
                        if (jsonMessage.content === "" || jsonMessage.type == "reasoning_xml") continue;

                        // Gestione primo chunk (Rimuove loading dots)
                        if (isFirstChunk) {
                            isFirstChunk = false;
                            addAIResponse("", msgId); 
                        }

                        if (jsonMessage.type === "content") {
                            appendToAIMessage(msgId, jsonMessage.content);
                        } 
                        
                    } catch (e) {
                        console.error("Errore nel parsing JSON della linea:", line, e);
                    }
                }
            }
            
            isAwaitingResponse = false;
            setChatUIEnabled(true);
        } catch (error) {
            console.error("Errore fetch:", error);
            $('#' + 0).find('div').html('<p class="text-red-500">Errore di connessione</p>');
        }
    }


    function setChatUIEnabled(isEnabled) {
        const $sendBtn = $('#send-btn');
        const $attachBtn = $('#attach-btn');

        $chatInput.prop('disabled', !isEnabled);
        $sendBtn.prop('disabled', !isEnabled);
        $attachBtn.prop('disabled', !isEnabled);
        
        $chatInput.attr('placeholder', isEnabled ? "Scrivi un messaggio..." : "AI sta elaborando...");
    }

    function addUserTextMessage(text) {
        const $userDiv = $('<div>').addClass('flex justify-end');
        const $bubble = $('<div>').addClass('msg user bg-blue-600 text-white rounded-lg p-3 max-w-xs');
        
        const $p = $('<p>').addClass('text-sm whitespace-pre-wrap').text(text);
        $userDiv.append($bubble.append($p));
        $('#chat-messages').append($userDiv);
        
        scrollBottom();
        isAwaitingResponse = true;
        setChatUIEnabled(false);
    }

    function addUserFilesMessage(files, text) {
        const $wrap = $('<div>').addClass('flex justify-end');
        const $card = $('<div>').addClass('bg-blue-100 dark:bg-blue-900 rounded-lg p-3 max-w-xs');
        
        isAwaitingResponse = true;
        setChatUIEnabled(false);

        $.each(files, function(index, file) {
            const $row = $('<div>').addClass('flex items-center');
            if (index > 0) {
                $row.addClass('mt-2');
            }

            const $icon = $('<i>')
                .attr('data-feather', getFileIcon(file.type)) // Imposta attributo per Feather
                .addClass('w-4 h-4 mr-2 text-blue-600 dark:text-blue-300 flex-shrink-0');

            const $name = $('<span>')
                .addClass('text-sm text-blue-800 dark:text-blue-200 truncate')
                .text(`${file.name} (${humanFileSize(file.size)})`);

            $row.append($icon, $name);
            $card.append($row);
        });

        if (text) {
            const $hr = $('<div>').addClass('my-2 h-px bg-blue-200/60 dark:bg-blue-800/60');
            
            const $noteEl = $('<p>')
                .addClass('text-sm text-blue-900 dark:text-blue-100 whitespace-pre-wrap')
                .text(text);

            $card.append($hr, $noteEl);
        }

        $wrap.append($card);
        $('#chatMessages').append($wrap);
        
        scrollBottom();
        feather.replace();
    }

    $('#send-btn').click(onSend)
    $chatInput.keypress((e) => {
        if (e.key === 'Enter') onSend();
    })

    $('#chatbot-toggle').on('click', function() {
        $('#chatbot-overlay').removeClass('invisible opacity-0').addClass('opacity-100');
        $('#chatbot-panel').removeClass('translate-x-full');
        $('body').css('overflow', 'hidden');
        $chatInput.focus();
        
        sendChat([
            {
                role: "user",
                content: "Ciao, presentati dicendo chi sei e qual'è il tuo obiettivo."
            }
        ]);
    });

    $('#chatbotClose, #chatbotBackdrop').on('click', function() {
        $('#chatbot-overlay').removeClass('opacity-100').addClass('opacity-0');
        $('#chatbot-panel').addClass('translate-x-full');
        $('body').css('overflow', 'auto');
        
        hideDropArea();
        pendingFiles = [];
        renderChips();

        setTimeout(function() {
            $('#chatbot-overlay').addClass('invisible');
        }, 300);
    });
});
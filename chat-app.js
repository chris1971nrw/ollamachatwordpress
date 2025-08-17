// Warten, bis das DOM vollständig geladen ist.
document.addEventListener('DOMContentLoaded', function() {
    const chatModal = document.getElementById('ollama-chat-modal');
    const chatContainer = document.getElementById('ollama-chat-container');
    const chatToggleButton = document.getElementById('ollama-chat-toggle');

    if (!chatModal || !chatContainer || !chatToggleButton) return;

    // Nachrichtenarray für die Historie.
    let messages = [];

    // HTML-Struktur für die Chat-Oberfläche erstellen.
    chatContainer.innerHTML = `
        <div class="ollama-chat-box">
            <div class="ollama-chat-header">
                <span class="ollama-chat-title">Ollama Chat</span>
                <button id="clear-chat-button" title="Chat löschen">🗑️</button>
                <button id="close-chat-button" title="Chat schließen">❌</button>
            </div>
            <div class="ollama-chat-messages" id="ollama-chat-messages"></div>
            <div id="ollama-starter-prompts" class="ollama-starter-prompts"></div>
            <div id="ollama-error-box" class="ollama-error-box" style="display:none;"></div>
            <form id="ollama-chat-form" class="ollama-chat-form">
                <input type="text" id="ollama-chat-input" placeholder="Stellen Sie Ihre Frage..." />
                <button type="submit">Senden</button>
            </form>
            <div class="ollama-chat-footer">
                <span>Powered by Ollama</span>
            </div>
        </div>
    `;

    // Elemente der Benutzeroberfläche abrufen.
    const chatMessages = document.getElementById('ollama-chat-messages');
    const chatForm = document.getElementById('ollama-chat-form');
    const chatInput = document.getElementById('ollama-chat-input');
    const closeChatButton = document.getElementById('close-chat-button');
    const clearChatButton = document.getElementById('clear-chat-button');
    const starterPromptsContainer = document.getElementById('ollama-starter-prompts');
    const errorBox = document.getElementById('ollama-error-box');

    // Initialer Begrüßungsstatus, um eine doppelte Nachricht zu vermeiden.
    let hasGreeted = false;
    
    /**
     * Konvertiert einfachen Markdown in HTML.
     * @param {string} text Der Eingabetext mit Markdown.
     * @returns {string} Der HTML-gerenderte Text.
     */
    function renderMarkdown(text) {
        // Ersetze Überschriften
        text = text.replace(/^### (.*$)/g, '<h3>$1</h3>');
        text = text.replace(/^## (.*$)/g, '<h2>$1</h2>');
        text = text.replace(/^# (.*$)/g, '<h1>$1</h1>');

        // Ersetze fett, kursiv und Links
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        // Ersetze Listen
        text = text.replace(/^- (.*)$/gm, '<li>$1</li>');
        if (text.includes('<li>')) {
            text = `<ul>${text}</ul>`;
        }

        // Ersetze Codeblöcke
        text = text.replace(/```(.*?)```/gs, (match, p1) => {
            const lines = p1.trim().split('\n');
            let language = '';
            let code = p1.trim();

            if (lines.length > 0) {
                const firstLine = lines[0].trim();
                if (firstLine.match(/^[a-zA-Z0-9]+$/)) {
                    language = firstLine;
                    code = lines.slice(1).join('\n');
                }
            }
            return `<pre><code class="language-${language}">${code}</code></pre>`;
        });
        
        return text;
    }

    /**
     * Zeigt eine Fehlermeldung an.
     * @param {string} message Die Fehlermeldung.
     */
    function showError(message) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
        setTimeout(() => {
            errorBox.style.display = 'none';
        }, 5000); // Fehler nach 5 Sekunden ausblenden
    }

    /**
     * Fügt eine neue Nachricht zur Benutzeroberfläche hinzu.
     * @param {string} text Der Nachrichtentext.
     * @param {string} sender Der Absender ('user' oder 'assistant').
     */
    function addMessageToUI(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('ollama-chat-message', `ollama-${sender}`);
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(20px)';
        messageDiv.innerHTML = (sender === 'assistant') ? renderMarkdown(text) : text;
        
        if (sender === 'assistant' && text.trim() !== "") {
            const copyButton = document.createElement('button');
            copyButton.classList.add('ollama-copy-button');
            copyButton.innerHTML = '📋';
            copyButton.title = 'In die Zwischenablage kopieren';
            copyButton.addEventListener('click', () => {
                copyToClipboard(text);
                copyButton.innerHTML = '✅';
                setTimeout(() => { copyButton.innerHTML = '📋'; }, 2000);
            });
            messageDiv.appendChild(copyButton);
        }

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Sanft einblenden
        setTimeout(() => {
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateY(0)';
        }, 50);
    }
    
    /**
     * Kopiert Text in die Zwischenablage.
     * @param {string} text Der zu kopierende Text.
     */
    function copyToClipboard(text) {
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = text;
        tempTextarea.style.position = 'absolute';
        tempTextarea.style.left = '-9999px';
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Fehler beim Kopieren des Textes: ', err);
        }
        document.body.removeChild(tempTextarea);
    }

    /**
     * Speichert den Chat-Verlauf im localStorage.
     */
    function saveChatHistory() {
        localStorage.setItem('ollamaChatHistory', JSON.stringify(messages));
        localStorage.setItem('ollamaChatVisited', 'true');
    }

    /**
     * Lädt den Chat-Verlauf aus dem localStorage.
     */
    function loadChatHistory() {
        const storedHistory = localStorage.getItem('ollamaChatHistory');
        const hasVisited = localStorage.getItem('ollamaChatVisited') === 'true';

        if (storedHistory) {
            messages = JSON.parse(storedHistory);
            messages.forEach(msg => addMessageToUI(msg.text, msg.sender));
        } else {
            messages = [];
        }

        // Zeige die personalisierte Begrüßung an, wenn es der erste Besuch ist.
        if (!hasVisited || messages.length === 0) {
            addMessageToUI("Hallo! Ich bin Ihr Chat-Assistent. Fragen Sie mich etwas über diese Seite.", 'assistant');
        } else {
            addMessageToUI("Willkommen zurück! Wie kann ich Ihnen heute helfen?", 'assistant');
        }
        
        showStarterPrompts();
    }

    /**
     * Zeigt die Startaufforderungen an
     */
     function showStarterPrompts() {
         if (ollama_chat_ajax.starter_prompts && ollama_chat_ajax.starter_prompts.length > 0) {
            starterPromptsContainer.innerHTML = '';
            ollama_chat_ajax.starter_prompts.forEach(prompt => {
                const button = document.createElement('button');
                button.textContent = prompt;
                button.classList.add('ollama-starter-prompt-button');
                button.addEventListener('click', () => {
                    chatInput.value = prompt;
                    chatForm.dispatchEvent(new Event('submit'));
                });
                starterPromptsContainer.appendChild(button);
            });
         }
     }

    // Chat-Verlauf beim Laden der Seite laden
    loadChatHistory();

    // Event-Handler für "Chat löschen"-Button.
    clearChatButton.addEventListener('click', function() {
        localStorage.removeItem('ollamaChatHistory');
        messages = [];
        chatMessages.innerHTML = '';
        addMessageToUI("Hallo! Ich bin Ihr Chat-Assistent. Fragen Sie mich etwas über diese Seite.", 'assistant');
        showStarterPrompts();
    });

    // Toggle-Funktion für den Chat-Button
    chatToggleButton.addEventListener('click', function() {
        chatModal.style.display = 'flex';
        chatInput.focus();
    });

    // Event-Handler für den Schließen-Button
    closeChatButton.addEventListener('click', function() {
        chatModal.style.display = 'none';
    });

    // AJAX-Formular-Handler mit Fetch API für Streaming.
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const userPrompt = chatInput.value.trim();
        if (userPrompt === '') {
            return;
        }

        addMessageToUI(userPrompt, 'user');
        messages.push({ text: userPrompt, sender: 'user' });
        saveChatHistory();

        chatInput.value = '';
        chatInput.disabled = true;

        starterPromptsContainer.style.display = 'none';
        errorBox.style.display = 'none';

        const assistantMessageDiv = document.createElement('div');
        assistantMessageDiv.classList.add('ollama-chat-message', 'ollama-assistant', 'ollama-loading');
        assistantMessageDiv.innerHTML = '<span></span><span></span><span></span>';
        chatMessages.appendChild(assistantMessageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        try {
            const formData = new FormData();
            formData.append('action', 'ollama_chat_request');
            formData.append('prompt', userPrompt);
            formData.append('history', JSON.stringify(messages.slice(0, -1)));
            formData.append('nonce', ollama_chat_ajax.nonce);
            
            // Persona aus dem Data-Attribut holen und anhängen.
            const persona = chatContainer.dataset.persona || '';
            formData.append('persona', persona);

            const response = await fetch(ollama_chat_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error('Netzwerkfehler: ' + errorText);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let result = '';

            assistantMessageDiv.classList.remove('ollama-loading');
            assistantMessageDiv.innerHTML = '';

            // Lese-Loop für den Stream
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                result += chunk;
                assistantMessageDiv.innerHTML = renderMarkdown(result);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Die endgültige Antwort mit dem Kopierbutton neu rendern
            assistantMessageDiv.innerHTML = '';
            addMessageToUI(result, 'assistant');
            assistantMessageDiv.remove();

            // Gesamte Antwort zur Historie hinzufügen
            messages.push({ text: result, sender: 'assistant' });
            saveChatHistory();

        } catch (error) {
            showError('Es ist ein Fehler aufgetreten: ' + error.message);
        } finally {
            chatInput.disabled = false;
            chatInput.focus();
        }
    });
});

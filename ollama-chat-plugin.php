<?php
// Dateiname: ollama-chat.php

// Shortcode-Handler für [ollama_chat]
function ollama_chat_shortcode_handler($atts) {
    // Attributes handling, etc.

    // CSS und JS für das direkt eingebettete Interface laden
    wp_enqueue_style('ollama-chat-style');
    wp_enqueue_script('ollama-chat-script');

    // Das Chat-Interface als HTML-String zurückgeben
    return '<div id="ollama-chat-container" class="ollama-chat-embedded"></div>';
}

add_shortcode('ollama_chat', 'ollama_chat_shortcode_handler');
?>
```javascript
// Dateiname: assets/js/ollama-chat.js

// Funktion, um das Chat-Interface in das HTML-Element zu rendern
function renderChatInterface(container) {
    // Generiert das gesamte HTML-Markup für das Chat-Interface
    // und fügt es in das übergebene container-Element ein.
    // Das vorherige modale Overlay und der Button zum Öffnen werden entfernt.

    // Beispielhafte Struktur des Chat-Interfaces
    const chatHtml = `
        <div class="chat-header">Ollama Chat</div>
        <div class="chat-messages"></div>
        <div class="chat-input-area">
            <textarea placeholder="Nachricht senden..."></textarea>
            <button>Senden</button>
        </div>
    `;

    container.innerHTML = chatHtml;

    // Fügen Sie hier die Logik für die Größenanpassung hinzu
    // Mit CSS flexbox oder grid kann die Größenanpassung über das
    // Elternelement gesteuert werden.
    // Beispiel: .ollama-chat-embedded { width: 100%; height: 500px; }
}

document.addEventListener('DOMContentLoaded', () => {
    // Findet den Container-Div des Shortcodes und rendert das Chat-Interface
    const container = document.getElementById('ollama-chat-container');
    if (container) {
        renderChatInterface(container);
    }
});
```css
/* Dateiname: assets/css/ollama-chat.css */

/* Stile für das eingebettete Chat-Fenster */
.ollama-chat-embedded {
    /* Grundlegende Stil-Anpassungen für das eingebettete Chat-Fenster */
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    /* Flexbox für die automatische Größenanpassung im übergeordneten Element */
    display: flex;
    flex-direction: column;
    /* Ermöglicht das Anpassen der Höhe über das Elternelement */
    height: 100%;
}

/* Anpassung der Chat-Nachrichten, Header, etc. */
.ollama-chat-embedded .chat-header {
    /* ... */
}

.ollama-chat-embedded .chat-messages {
    flex-grow: 1; /* Nimmt den restlichen Platz ein */
    overflow-y: auto;
    /* ... */
}

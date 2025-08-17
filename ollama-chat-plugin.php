<?php
/**
 * Plugin Name: Ollama Chat
 * Plugin URI: https://example.com/ollama-chat
 * Description: Integriert einen anpassbaren Chatbot, der auf der Ollama-Plattform basiert, direkt in Ihre WordPress-Seiten.
 * Version: 10.3
 * Author: Ihr Name
 * Author URI: https://example.com
 * License: GPL2
 */

// WICHTIG: Vermeidet den direkten Zugriff auf die Datei
if (!defined('ABSPATH')) {
    exit;
}

// Lade CSS und JavaScript
function ollama_chat_enqueue_assets() {
    wp_enqueue_style('ollama-chat-style', plugin_dir_url(__FILE__) . 'assets/css/ollama-chat.css', [], '10.3');
    wp_enqueue_script('ollama-chat-script', plugin_dir_url(__FILE__) . 'assets/js/ollama-chat.js', ['jquery'], '10.3', true);
}
add_action('wp_enqueue_scripts', 'ollama_chat_enqueue_assets');


// Registriert das Einstellungsmenü für das Plugin
function ollama_chat_add_admin_menu() {
    add_options_page(
        'Ollama Chat Einstellungen',
        'Ollama Chat',
        'manage_options',
        'ollama-chat-settings',
        'ollama_chat_settings_page'
    );
}
add_action('admin_menu', 'ollama_chat_add_admin_menu');

// Rendert die Einstellungsseite
function ollama_chat_settings_page() {
    // Hier würde der HTML-Code für die Einstellungsseite stehen
    ?>
    <div class="wrap">
        <h1>Ollama Chat Einstellungen</h1>
        <p>Hier können Sie die API-URL, das Modell und weitere Optionen konfigurieren.</p>
        <!-- Formular zum Speichern der Einstellungen -->
    </div>
    <?php
}

// Shortcode-Handler für [ollama_chat]
function ollama_chat_shortcode_handler($atts) {
    // Attributes handling, etc.

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
    const chatHtml = `
        <div class="chat-header">Ollama Chat</div>
        <div class="chat-messages"></div>
        <div class="chat-input-area">
            <textarea placeholder="Nachricht senden..."></textarea>
            <button>Senden</button>
        </div>
    `;

    container.innerHTML = chatHtml;
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
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* Anpassung der Chat-Nachrichten, Header, etc. */
.ollama-chat-embedded .chat-header {
    /* ... */
}

.ollama-chat-embedded .chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    /* ... */
}

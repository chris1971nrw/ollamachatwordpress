<?php
/**
 * Plugin Name: Ollama Chat Plugin
 * Description: Ein einfacher Chatbot, der mithilfe von Ollama auf den Inhalt der WordPress-Seite antwortet.
 * Version: 10.0
 * Author: Ihr Name
 */

// Sicherheit: Direkten Zugriff auf die Datei verhindern.
if (!defined('ABSPATH')) {
    exit;
}

// =========================================================================
// == 1. PLUGIN-EINRICHTUNG UND EINSTELLUNGEN ===============================
// =========================================================================

/**
 * Erstellt die Einstellungsseite im WordPress-Admin-Menü.
 */
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

/**
 * Registriert die Plugin-Einstellungen.
 */
function ollama_chat_settings_init() {
    register_setting('ollama-chat-group', 'ollama_chat_api_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ));
    register_setting('ollama-chat-group', 'ollama_chat_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('ollama-chat-group', 'ollama_chat_model', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    for ($i = 1; $i <= 3; $i++) {
        register_setting('ollama-chat-group', 'ollama_chat_starter_prompt_' . $i, array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }

    add_settings_section(
        'ollama-chat-settings-section',
        'Ollama API-Konfiguration',
        'ollama_chat_settings_section_callback',
        'ollama-chat-settings'
    );
    
    add_settings_field(
        'ollama_chat_api_url_field',
        'Ollama API-URL',
        'ollama_chat_api_url_callback',
        'ollama-chat-settings',
        'ollama-chat-settings-section'
    );

    add_settings_field(
        'ollama_chat_api_key_field',
        'Ollama API-Schlüssel',
        'ollama_chat_api_key_callback',
        'ollama-chat-settings',
        'ollama-chat-settings-section'
    );

    add_settings_field(
        'ollama_chat_model_field',
        'Ollama Modellname',
        'ollama_chat_model_callback',
        'ollama-chat-settings',
        'ollama-chat-settings-section'
    );

    add_settings_section(
        'ollama-chat-prompts-section',
        'Startaufforderungen',
        'ollama_chat_prompts_section_callback',
        'ollama-chat-settings'
    );

    for ($i = 1; $i <= 3; $i++) {
        add_settings_field(
            'ollama_chat_starter_prompt_field_' . $i,
            'Startaufforderung #' . $i,
            'ollama_chat_starter_prompt_callback',
            'ollama-chat-settings',
            'ollama-chat-prompts-section',
            array('prompt_number' => $i)
        );
    }
}
add_action('admin_init', 'ollama_chat_settings_init');

/**
 * Callback-Funktion für die Abschnittsbeschreibung.
 */
function ollama_chat_settings_section_callback() {
    echo '<p>Geben Sie die URL Ihres Ollama-Servers an. Das Plugin wird dann versuchen, eine Verbindung herzustellen und die verfügbaren Modelle aufzulisten.</p>';
}

/**
 * Callback-Funktion zum Anzeigen des Eingabefelds für die API-URL.
 */
function ollama_chat_api_url_callback() {
    $url = get_option('ollama_chat_api_url', 'http://localhost:11434');
    echo '<input type="text" name="ollama_chat_api_url" value="' . esc_attr($url) . '" class="regular-text">';
    echo '<p class="description">Geben Sie die Basis-URL Ihres Ollama-Servers an (z.B. http://localhost:11434).</p>';

    // Führen Sie einen Verbindungstest durch.
    $check_result = ollama_chat_check_connection($url);
    if ($check_result['success']) {
        echo '<p style="color: green; font-weight: bold;">Verbindung erfolgreich! Gefundene Modelle: ' . count($check_result['models']) . '</p>';
    } else {
        echo '<p style="color: red; font-weight: bold;">Verbindung fehlgeschlagen: ' . esc_html($check_result['message']) . '</p>';
    }
}

/**
 * Callback-Funktion zum Anzeigen des Eingabefelds für den API-Schlüssel.
 */
function ollama_chat_api_key_callback() {
    $api_key = get_option('ollama_chat_api_key', '');
    echo '<input type="password" name="ollama_chat_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    echo '<p class="description">Optional: Geben Sie einen API-Schlüssel ein, falls Ihr Ollama-Server eine Authentifizierung erfordert.</p>';
}

/**
 * Callback-Funktion zum Anzeigen des Dropdown-Felds für den Modellnamen.
 */
function ollama_chat_model_callback() {
    $api_url = get_option('ollama_chat_api_url', 'http://localhost:11434');
    $current_model = get_option('ollama_chat_model', '');
    $check_result = ollama_chat_check_connection($api_url);
    $models = $check_result['models'];

    echo '<select name="ollama_chat_model" class="regular-text">';

    if (!empty($models)) {
        foreach ($models as $model) {
            $model_name = $model['name'];
            $selected = selected($current_model, $model_name, false);
            echo '<option value="' . esc_attr($model_name) . '"' . $selected . '>' . esc_html($model_name) . '</option>';
        }
    } else {
        echo '<option value="">Keine Modelle gefunden</option>';
    }

    echo '</select>';
    echo '<p class="description">Wählen Sie das Modell aus der Liste aus, das für den Chat verwendet werden soll.</p>';
}

/**
 * Callback-Funktion für die Sektion der Startaufforderungen.
 */
function ollama_chat_prompts_section_callback() {
    echo '<p>Geben Sie bis zu drei Fragen ein, die als klickbare Buttons im Chat-Widget angezeigt werden sollen.</p>';
}

/**
 * Callback-Funktion für die Eingabefelder der Startaufforderungen.
 */
function ollama_chat_starter_prompt_callback($args) {
    $prompt_number = $args['prompt_number'];
    $prompt = get_option('ollama_chat_starter_prompt_' . $prompt_number, '');
    echo '<input type="text" name="ollama_chat_starter_prompt_' . $prompt_number . '" value="' . esc_attr($prompt) . '" class="regular-text">';
}

/**
 * Funktion, die eine Verbindung zu Ollama testet und Modelle abruft.
 *
 * @param string $api_url Die Basis-URL des Ollama-Servers.
 * @return array Ein Array mit dem Ergebnis der Prüfung.
 */
function ollama_chat_check_connection($api_url) {
    $api_key = get_option('ollama_chat_api_key', '');
    $headers = array();
    if (!empty($api_key)) {
        $headers['Authorization'] = 'Bearer ' . $api_key;
    }

    $response = wp_remote_get(
        trailingslashit($api_url) . 'api/tags',
        array(
            'timeout' => 10,
            'headers' => $headers,
        )
    );

    if (is_wp_error($response)) {
        return array('success' => false, 'message' => $response->get_error_message(), 'models' => array());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['models'])) {
        return array('success' => true, 'message' => 'Verbindung erfolgreich', 'models' => $data['models']);
    }

    return array('success' => false, 'message' => 'Ungültige API-Antwort.', 'models' => array());
}

/**
 * Erstellt die HTML-Struktur für die Einstellungsseite.
 */
function ollama_chat_settings_page() {
    ?>
    <div class="wrap">
        <h2>Ollama Chat Einstellungen</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('ollama-chat-group');
            do_settings_sections('ollama-chat-settings');
            submit_button('Einstellungen speichern');
            ?>
        </form>
    </div>
    <?php
}

// =========================================================================
// == 2. FRONTEND- UND AJAX-LOGIK ===========================================
// =========================================================================

/**
 * Funktion zum Einbinden der JavaScript- und CSS-Dateien.
 */
function ollama_chat_enqueue_scripts() {
    wp_enqueue_style('ollama-chat-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), '10.0');
    wp_enqueue_script('ollama-chat-script', plugin_dir_url(__FILE__) . 'js/chat-app.js', array('jquery'), '10.0', true);

    // Sammeln der Starter-Prompts.
    $starter_prompts = array();
    for ($i = 1; $i <= 3; $i++) {
        $prompt = get_option('ollama_chat_starter_prompt_' . $i);
        if (!empty($prompt)) {
            $starter_prompts[] = $prompt;
        }
    }

    wp_localize_script('ollama-chat-script', 'ollama_chat_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ollama-chat-nonce'),
        'starter_prompts' => $starter_prompts,
    ));
}
add_action('wp_enqueue_scripts', 'ollama_chat_enqueue_scripts');

/**
 * Shortcode-Funktion zum Anzeigen des Chat-Containers.
 *
 * @param array $atts Benutzerdefinierte Shortcode-Attribute.
 */
function ollama_chat_shortcode($atts) {
    // Standard-Attribute definieren und mit den benutzerdefinierten Attributen zusammenführen.
    $atts = shortcode_atts(
        array(
            'persona' => '',
        ),
        $atts,
        'ollama_chat'
    );
    
    // Die Persona als Data-Attribut an den Container übergeben.
    $persona_attribute = !empty($atts['persona']) ? ' data-persona="' . esc_attr($atts['persona']) . '"' : '';

    ob_start();
    ?>
    <button id="ollama-chat-toggle" title="Chat öffnen">💬</button>
    <div id="ollama-chat-modal" class="ollama-chat-modal">
        <div id="ollama-chat-container"<?php echo $persona_attribute; ?>></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ollama_chat', 'ollama_chat_shortcode');

/**
 * Hilfsfunktion zum Finden des relevantesten Inhalts von der gesamten Website.
 *
 * @param string $query Die Benutzeranfrage.
 * @return string Der kombinierte Inhalt relevanter Seiten.
 */
function ollama_chat_find_relevant_content($query) {
    // Suchen Sie nach relevanten Seiten basierend auf der Abfrage.
    $args = array(
        'post_type' => array('post', 'page'),
        'posts_per_page' => 3,
        's' => $query,
        'fields' => 'ids',
    );
    $relevant_post_ids = get_posts($args);

    $combined_content = '';
    
    // Extrahieren Sie den Inhalt aus den gefundenen Seiten.
    if (!empty($relevant_post_ids)) {
        foreach ($relevant_post_ids as $post_id) {
            $post = get_post($post_id);
            $post_content = $post ? wp_strip_all_tags(strip_shortcodes($post->post_content)) : '';
            $post_title = $post ? $post->post_title : '';
            if (!empty($post_content)) {
                $combined_content .= "### Titel: {$post_title}\n\n" . $post_content . "\n\n---\n\n";
            }
        }
    } else {
        // Fallback: Wenn keine relevanten Seiten gefunden werden, verwenden Sie den Inhalt der aktuellen Seite.
        $current_post = get_post();
        if ($current_post) {
            $post_content = wp_strip_all_tags(strip_shortcodes($current_post->post_content));
            $post_title = $current_post->post_title;
            $combined_content = "### Titel: {$post_title}\n\n" . $post_content;
        }
    }

    return $combined_content;
}


/**
 * AJAX-Handler für die Chat-Anfrage mit Streaming.
 */
function ollama_chat_handle_stream_request() {
    // Überprüfen der Nonce zur Sicherheit.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ollama-chat-nonce')) {
        wp_send_json_error('Sicherheitsüberprüfung fehlgeschlagen.');
    }

    // Holen der Daten aus der Anfrage.
    $user_prompt = sanitize_text_field($_POST['prompt']);
    $history_json = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();
    $persona = isset($_POST['persona']) ? sanitize_text_field($_POST['persona']) : '';

    // Abrufen der konfigurierten Einstellungen.
    $ollama_api_url_base = get_option('ollama_chat_api_url', 'http://localhost:11434');
    $ollama_api_key = get_option('ollama_chat_api_key', '');
    $ollama_model = get_option('ollama_chat_model', 'llama3');

    // RAG: Finden des relevanten Inhalts von der Website.
    $relevant_content = ollama_chat_find_relevant_content($user_prompt);

    // Ein Prompt-Template erstellen.
    $system_prompt = "Sie sind ein hilfreicher Chatbot, der Benutzern hilft, Informationen von der Webseite zu finden. Ihre Antworten basieren ausschließlich auf dem bereitgestellten Text. Wenn die Antwort im Text nicht vorhanden ist, sagen Sie, dass Sie die Antwort nicht kennen.";
    if (!empty($persona)) {
        $system_prompt = "Sie sind ein hilfreicher Chatbot, der Benutzern hilft, Informationen von der Webseite zu finden. Ihre Antworten basieren ausschließlich auf dem bereitgestellten Text. Sie treten in der Rolle eines {$persona} auf. Wenn die Antwort im Text nicht vorhanden ist, sagen Sie, dass Sie die Antwort nicht kennen.";
    }
    
    $context_prompt = "Hier ist der relevante Inhalt von der Website: '{$relevant_content}'.";

    // Nachrichtenarray für die Ollama API erstellen.
    $messages = array(
        array('role' => 'system', 'content' => $system_prompt),
        array('role' => 'system', 'content' => $context_prompt)
    );

    // Bestehende Nachrichten aus dem Chat-Verlauf hinzufügen.
    if (!empty($history_json)) {
        foreach ($history_json as $message) {
            $messages[] = array('role' => $message['sender'], 'content' => $message['text']);
        }
    }
    
    // Die aktuelle Benutzeranfrage hinzufügen.
    $messages[] = array('role' => 'user', 'content' => $user_prompt);

    $data_to_send = array(
        'model' => $ollama_model,
        'messages' => $messages,
        'stream' => true,
    );
    
    $ollama_api_url = trailingslashit($ollama_api_url_base) . 'api/chat';

    // Header für die Anfrage
    $headers = array('Content-Type: application/json');
    if (!empty($ollama_api_key)) {
        $headers[] = 'Authorization: Bearer ' . $ollama_api_key;
    }

    // cURL-Anfrage für Streaming-Antworten
    $ch = curl_init($ollama_api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Streaming-Callback-Funktion
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        $json_data = json_decode($data, true);
        if (isset($json_data['message']['content'])) {
            $content = $json_data['message']['content'];
            echo $content;
            ob_flush();
            flush();
        }
        return strlen($data);
    });

    header('Content-Type: text/plain; charset=utf-8');
    curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL-Fehler: ' . curl_error($ch);
    }
    
    curl_close($ch);
    wp_die();
}
add_action('wp_ajax_ollama_chat_request', 'ollama_chat_handle_stream_request');
add_action('wp_ajax_nopriv_ollama_chat_request', 'ollama_chat_handle_stream_request');


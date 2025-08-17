Ollama Chat Plugin für WordPressEin einfaches und leichtgewichtiges WordPress-Plugin, das einen Chatbot integriert, der auf der Ollama-Plattform basiert.

Der Chatbot kann auf den Inhalt Ihrer Website zugreifen und Fragen der Nutzer beantworten.FunktionenRAG (Retrieval-Augmented Generation): 
Der Chatbot nutzt den Inhalt Ihrer Website, um relevante Antworten zu generieren.Anpassbare Persona: Sie können dem Chatbot eine spezifische Rolle (z.B. "Kundenservice-Assistent" oder "Technik-Experte") zuweisen.

Benutzerfreundliches Chat-Interface: Ein modernes, modales Fenster mit einem einfachen Chat-Verlauf und Startaufforderungen.Lokale Speicherung: Der Chat-Verlauf wird im Browser des Nutzers gespeichert, um das Gespräch bei einem erneuten Besuch fortzusetzen.
Sicher und effizient: Die API-Anfragen werden serverseitig verarbeitet, um den API-Schlüssel zu schützen.

Installation
Laden Sie das gesamte Plugin-Verzeichnis in den wp-content/plugins/-Ordner Ihrer WordPress-Installation hoch.
Gehen Sie in Ihrem WordPress-Admin-Dashboard zu Plugins und aktivieren Sie das Plugin Ollama Chat Plugin.Konfiguration
Navigieren Sie im Admin-Dashboard zu Einstellungen > Ollama Chat.Geben Sie die URL Ihres Ollama-Servers ein (z.B. http://localhost:11434).
Wählen Sie das zu verwendende Modell aus der Dropdown-Liste.Optional können Sie bis zu drei Startaufforderungen eingeben, die als klickbare Buttons im Chat erscheinen.
Speichern Sie die Einstellungen.

VerwendungUm den Chatbot auf Ihrer Website anzuzeigen, fügen Sie einfach den folgenden Shortcode auf einer beliebigen Seite oder in einem Beitrag ein:[ollama_chat]
Optional können Sie eine Persona definieren:[ollama_chat persona="Vertriebsexperte"]

Changelog 

v10.0
Hinzugefügt: README.md und package.json für bessere Dokumentation und Projektstandard.
Hinzugefügt: "Powered by Ollama"-Footer im Chat-Interface.Verbessert: Loading-Indikator und weitere visuelle Verfeinerungen.

v9.0
Hinzugefügt: Personalisierte Begrüßung ("Willkommen zurück!").Hinzugefügt: Erweiterte Markdown-Unterstützung (Überschriften, Codeblöcke).Verbessert: Sanfte Einblend-Animation der Nachrichten.
Hinzugefügt: Button zum Löschen des Chats im Header.

v8.0
Erste Veröffentlichung mit grundlegender Chat-Funktionalität.
Modal-Fenster für das Chat-Interface.Verlaufsspeicherung im lokalen Speicher des Browsers.
Dynamische Startaufforderungen.
Sichere API-Anfragen ohne sichtbaren API-Schlüssel im Frontend.

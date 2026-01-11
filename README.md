# QrGate - Modernes Ticketing- und Zugangskontrollsystem

![QrGate Logo](backend/data/assets/logo.png)

**QrGate** ist ein modernes, webbasiertes Ticketing- und Zugangskontrollsystem für Veranstaltungen. Es ermöglicht den Verkauf, die Verwaltung und die Validierung von Tickets über QR-Codes und bietet eine benutzerfreundliche Oberfläche für Administratoren und Besucher.

## 📋 Übersicht

QrGate ist ein umfassendes System für die Verwaltung von Veranstaltungen, Tickets und Zugangskontrollen. Es besteht aus einem **Backend** (Python/Quart) und einem **Frontend** (PHP/HTML/CSS/JavaScript) und bietet eine moderne, responsive Benutzeroberfläche.

### Hauptfunktionen

- **Ticketverkauf**: Einfacher Kauf von Tickets über eine Weboberfläche mit Unterstützung für verschiedene Zahlungsmethoden (Barzahlung, PayPal).
- **Zugangskontrolle**: QR-Code-basierte Validierung von Tickets für den Einlass.
- **Verwaltungsoberfläche**: Umfassendes Admin-Panel für die Verwaltung von Veranstaltungen, Tickets und Statistiken.
- **Mehrsprachigkeit**: Unterstützung für mehrere Sprachen (Deutsch, Englisch).
- **Responsive Design**: Optimiert für Desktop und mobile Geräte.

## 🚀 Installation

### Voraussetzungen

- **Backend**:
  - Python 3.7+
  - Quart (Web-Framework)
  - Weitere Abhängigkeiten (siehe `backend/main.py`)

- **Frontend**:
  - PHP 7.4+
  - Webserver (Apache, Nginx)
  - Composer (für PHP-Abhängigkeiten)

### Schritte

1. **Repository klonen:**
   ```bash
   git clone https://github.com/rwolf2467/QrGate.git
   cd QrGate
   ```

2. **Backend einrichten:**
   ```bash
   cd backend
   pip install -r requirements.txt  # Falls vorhanden
   ```

3. **Frontend einrichten:**
   ```bash
   cd frontend
   composer install
   ```

4. **Konfiguration anpassen:**
   - **Backend**: `backend/config/conf.py`
     ```python
     class API:
         port = 1654
         backend_url = "https://qrgate-backend.example.com/"
     
     class Auth:
         auth_key = "YourGeneratedKeyHere"
     
     class Mail:
         smtp_server = "smtp.example.com"
         smtp_port = 587
         smtp_user = "user@example.com"
         smtp_password = "smtp_password"
     ```
   
   - **Frontend**: `frontend/config.php`
     ```php
     <?php
     define('API_BASE_URL', 'http://localhost:1654');
     define('API_KEY', 'YourGeneratedKeyHere');
     define('PAYPAL_CLIENT_ID', 'YourPayPalClientID');
     ```

5. **Webserver einrichten:**
   - Richten Sie den Webserver so ein, dass er auf das `frontend`-Verzeichnis zeigt.
   - Stellen Sie sicher, dass die `backend/data`-Verzeichnisse beschreibbar sind.

6. **Anwendung starten:**
   - **Backend**:
     ```bash
     cd backend
     python main.py
     ```
   - **Frontend**: Öffnen Sie die Anwendung in Ihrem Browser.

## 📂 Projektstruktur

```
QrGate/
├── backend/
│   ├── assets/              # Backend-Module (Ticketverwaltung, Validierung, etc.)
│   ├── config/              # Konfigurationsdateien
│   ├── data/                # Daten (Shows, Tickets)
│   └── main.py              # Haupt-Backend-Server
│
├── frontend/
│   ├── admin/               # Admin-Oberfläche
│   ├── help/                # Hilfeseiten
│   ├── screens/             # Bildschirme für Veranstaltungen
│   ├── vote/                # Abstimmungssystem
│   ├── buy.php              # Ticketkauf
│   ├── config.php           # Frontend-Konfiguration
│   └── index.php            # Hauptseite
│
└── README.md               # Diese Datei
```

## 🎟️ Verwendung

### Ticketverkauf

1. Navigieren Sie zur Startseite der Anwendung.
2. Wählen Sie die gewünschte Veranstaltung aus.
3. Füllen Sie das Formular aus und bestätigen Sie den Kauf.
4. Ihr Ticket wird per E-Mail zugesendet oder kann heruntergeladen werden.

### Zugangskontrolle

1. Melden Sie sich als Administrator an.
2. Navigieren Sie zur Zugangskontrolloberfläche.
3. Scannen Sie den QR-Code des Tickets.
4. Das System validiert das Ticket und zeigt den Status an.

### Verwaltung

1. Melden Sie sich als Administrator an.
2. Navigieren Sie zum Admin-Panel.
3. Verwalten Sie Veranstaltungen, Tickets und Benutzer.
4. Sehen Sie sich Statistiken und Berichte an.

## 📊 Admin-Panel

Das Admin-Panel bietet folgende Funktionen:

- **Dashboard**: Übersicht über verkaufte Tickets, verfügbare Tickets und geschätzte Einnahmen.
- **Statistiken**: Grafische Darstellung der Ticketverkäufe und Verfügbarkeit.
- **Veranstaltungen verwalten**: Bearbeiten von Veranstaltungseinstellungen.
- **Termine verwalten**: Hinzufügen, Bearbeiten und Löschen von Veranstaltungsterminen.

## 🔧 Konfiguration

### Backend-Konfiguration

Die Backend-Konfiguration erfolgt in `backend/config/conf.py`. Hier können Sie Einstellungen wie den API-Port, die Backend-URL und die Authentifizierungsschlüssel anpassen.

### Frontend-Konfiguration

Die Frontend-Konfiguration erfolgt in `frontend/config.php`. Hier können Sie Einstellungen wie die API-Basis-URL, den Authentifizierungsschlüssel und die PayPal-Client-ID anpassen.

## 🛠️ Entwicklung

### Beitrag leisten

Wir freuen uns über Beiträge zur Weiterentwicklung von QrGate. Bitte beachten Sie die folgenden Schritte:

1. Forken Sie das Repository.
2. Erstellen Sie einen neuen Branch für Ihre Änderungen.
3. Implementieren Sie Ihre Änderungen und testen Sie sie gründlich.
4. Erstellen Sie einen Pull Request mit einer Beschreibung Ihrer Änderungen.

### Tests

Um die Tests auszuführen, verwenden Sie den folgenden Befehl:

```bash
cd backend
python -m pytest tests/
```

### Code-Standards

- **Python**: PEP-8-Standards
- **PHP**: PSR-12-Standards
- **JavaScript**: ESLint-Standards

## 📜 Lizenz

QrGate wird unter der MIT-Lizenz veröffentlicht. Weitere Informationen finden Sie in der Datei [LICENSE](backend/LICENSE).

## 🤝 Support

Für Fragen oder Unterstützung können Sie ein Issue im Repository erstellen oder uns unter der folgenden E-Mail-Adresse kontaktieren:

- **E-Mail**: support@qrgate.com

## 📸 Screenshots

![Admin Panel](screenshots/admin_panel.png)
![Ticket Flow](screenshots/ticket_flow.png)
![QR Validation](screenshots/qr_validation.png)

---

© 2023 QrGate. Alle Rechte vorbehalten.

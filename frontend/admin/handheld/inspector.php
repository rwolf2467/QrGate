<?php
// Backend (PHP)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php'; 

$API_KEY = API_KEY;
$API_ENDPOINT = API_BASE_URL . 'api/ticket/get';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticketId = $data['tid'] ?? null; // ticketId zu tid geändert

    if ($ticketId) {
        $ch = curl_init($API_ENDPOINT);
        $requestData = json_encode(['tid' => $ticketId]); // Anfrage anpassen

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $API_KEY
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Gib die API-Antwort immer als JSON zurück
        header('Content-Type: application/json');
        http_response_code($httpCode); // Setze den HTTP-Statuscode
        echo $response; // Gib die API-Antwort direkt zurück
        exit;
    } else {
        // Falls keine tid gesendet wurde
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Keine tid angegeben.' // Nachricht angepasst
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Inspector</title> <!-- Titel geändert -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <style>
        /* Allgemeine Stile */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #121212;
            /* Dunkler Hintergrund */
            color: #e0e0e0;
            /* Helle Schriftfarbe */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            /* Verhindert Scrollen auf Handys */
        }

        .container {
            background: #1e1e1e;
            /* Dunkler Container */
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            padding: 20px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: rgb(198, 198, 198);
            margin-bottom: 20px;
        }

        #reader {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        #result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            display: none;
            background: #f9f9f9;
            border: 1px solid #eee;
        }

        .success {
            color: #155724;
            background: #d4edda;
            border-color: #c3e6cb;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            border-color: #f5c6cb;
        }

        .ticket-info {
            margin-top: 15px;
            text-align: left;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .ticket-info p {
            margin: 8px 0;
            font-size: 14px;
            color: #555;
        }

        .ticket-info strong {
            color: #333;
        }

        .timestamp {
            font-size: 12px;
            color: #777;
            margin-top: 10px;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            h1 {
                font-size: 20px;
            }

            .container {
                padding: 15px;
            }

            #reader {
                max-width: 250px;
            }
        }

        .popup {
            display: none;
            /* Standardmäßig versteckt */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #2c2c2c;
            padding: 20px;
            border-radius: 8px;
            z-index: 1000;
            /* Über anderen Inhalten */
            max-width: 100%;
            /* Maximale Breite für mobile Geräte */
            box-sizing: border-box;
            /* Box-Modell anpassen */
            width: 95%;
        }

        .popup h2 {
            margin: 0 0 10px;
            /* Abstand unter dem Titel */
            font-size: 20px;
            /* Titelgröße */
        }

        .popup p {
            margin: 5px 0;
            /* Abstand zwischen den Absätzen */
            line-height: 1.5;
            /* Zeilenhöhe für bessere Lesbarkeit */
            font-size: 16px;
            /* Schriftgröße für den Text */
        }

        .popup.valid {
            border: 10px solid #4caf50;
            /* Grüner Rand für gültig */
            animation: blink-green 1s infinite;
            /* Blinken Animation für gültig */
            width: 95%;
        }

        @keyframes blink-green {
            0% {
                border-color: #4caf50;
            }

            /* Hellgrün */
            80% {
                border-color: rgb(18, 52, 19);
            }

            /* Dunkelgrün */
            100% {
                border-color: #4caf50;
                }

            /* Hellgrün */
        }

        .popup.invalid {
            border: 10px solid #f44336;
            /* Roter Rand für ungültig */
            animation: blink-red 1s infinite;
            
            /* Blinken Animation für ungültig */
            width: 95%;
        }

        @keyframes blink-red {
            0% {
                border-color: #f44336;
            }

            /* Hellrot */
            80% {
                border-color: rgb(79, 16, 16);
            }

            /* Dunkelrot */
            100% {
                border-color: #f44336;
            }

            /* Hellrot */
        }

        .popup button {
            margin-top: 15px;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            background: linear-gradient(90deg, #9333ea, #ec4899);
            width: 100%;
        }

        .popup button:hover {
            background: linear-gradient(90deg, #9333ea, #ec4899);
        }

        .blur-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(18, 18, 18, 0.8);
            /* Dunkler, transparenter Hintergrund */
            backdrop-filter: blur(10px);
            /* Hintergrund verschwommen */
            z-index: 999;
            /* Unter dem Popup */
            display: none;
            /* Standardmäßig versteckt */
        }

        #currentDateTime {
            color: rgb(137, 137, 137);
            margin-bottom: 10px;
        }

        /* Spinner Popup */
        .popup#spinnerPopup {
            border: 10px solid yellow; /* Gelber Rand */
            animation: blink-yellow 1s infinite; /* Blinkende Animation */
        }

        @keyframes blink-yellow {
            0% {
                border-color: yellow;
            }

            50% {
                border-color: rgba(255, 255, 0, 0.5); /* Halbtransparentes Gelb */
            }

            100% {
                border-color: yellow;
            }
        }

        .spinner {
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-top: 8px solid yellow; /* Gelber Spinner */
            border-radius: 50%;
            width: 60px; /* Breite des Spinners erhöhen */
            height: 60px; /* Höhe des Spinners erhöhen */
            animation: spin 0.8s linear infinite; /* Schnelleres Drehen */
            margin: 20px auto; /* Zentrieren */
            box-shadow: 0 0 15px rgb(168, 127, 5); /* Schatten hinzufügen */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .attempts-summary {
            max-height: 300px; /* Maximale Höhe für den Container */
            background-color:rgb(218, 191, 193); /* Dunkler Hintergrund für die Zusammenfassung */
            overflow-y: auto; /* Ermöglicht vertikales Scrollen */
            border-radius: 8px; /* Abgerundete Ecken */
            padding: 10px; /* Innenabstand */
        }

        .attempts-summary ul {
            list-style-type: none; /* Entfernt die Standard-Aufzählungszeichen */
            padding: 0; /* Entfernt den Standard-Padding */
        }

        .attempts-summary li {
            padding: 10px; /* Innenabstand für jedes Listenelement */
            margin: 5px 0; /* Abstand zwischen den Listenelementen */
            border-radius: 5px; /* Abgerundete Ecken */
        }

        .attempts-summary li.success {
            background-color:rgba(76, 136, 51, 0.51); /* Dunkler Hintergrund für erfolgreiche Versuche */
            color: #d4edda; /* Helle Schriftfarbe */
        }

        .attempts-summary li.error {
            background-color:rgba(182, 46, 46, 0.52); /* Dunkler Hintergrund für fehlgeschlagene Versuche */
            color: #f8d7da; /* Helle Schriftfarbe */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Ticket Inspector</h1>
        <select id="appSelector" onchange="navigateToApp()" style="margin-bottom: 20px;">
            <option value="inspector.php">Ticket Inspector</option>
            <option value="index.php">Ticket Scanner</option>
        </select>
        <div id="currentDateTime"></div>
        <div id="reader"></div>
        <div id="result"></div>
        <div id="spinner" style="display: none;">Validating Ticket...<br>One moment please...</div>
    </div>

    <div class="blur-background" id="blurBackground" style="display: none;"></div>
    <div class="popup" id="resultPopup">
        <div id="resultContent"></div>
        <button onclick="closePopup()">Close</button>
    </div>
    <div class="popup" id="spinnerPopup" style="display: none;">
        <h2>Validating Ticket...<br>One moment please...</h2>
        <div class="spinner"></div>
    </div>

    <script>
        // Frontend (JavaScript)
        let lastScannedCode = '';
        let lastScanTime = 0;
        const SCAN_COOLDOWN = 1000;

        function formatDateTime(date) {
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function onScanSuccess(decodedText) {
            const currentTime = Date.now();

            // Prüfe Cooldown und ob es der gleiche Code ist
            if (currentTime - lastScanTime < SCAN_COOLDOWN || decodedText === lastScannedCode) {
                return;
            }

            lastScannedCode = decodedText;
            lastScanTime = currentTime;

            // Spinner Popup anzeigen
            document.getElementById('blurBackground').style.display = 'block'; // Hintergrund anzeigen
            document.getElementById('spinnerPopup').style.display = 'block'; // Spinner Popup anzeigen

            // API-Anfrage
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tid: decodedText // ticketId zu tid geändert
                    })
                })
                .then(response => {
                    // Spinner Popup ausblenden
                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none'; // Hintergrund ausblenden
                    return response.json().then(data => {
                        if (!response.ok) {
                            // Wenn die Antwort nicht ok ist, werfen Sie einen Fehler mit der Nachricht aus den Daten
                            throw new Error(data.message || 'API-Fehler');
                        }
                        return data; // Geben Sie die Daten zurück, wenn die Antwort ok ist
                    });
                })
                .then(data => {
                    // Spinner Popup ausblenden
                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none'; // Hintergrund ausblenden
                    const resultPopup = document.getElementById('resultPopup');
                    const blurBackground = document.getElementById('blurBackground');
                    blurBackground.style.display = 'block'; // Hintergrund anzeigen
                    resultPopup.style.display = 'block'; // Popup anzeigen

                    let resultHTML = `
                    <div class="timestamp">Scan Time: ${formatDateTime(new Date())}</div>
                `;

                    if (data.status === 'success') {
                        // Audio für Erfolg abspielen
                        const successAudio = new Audio('success.mp3');
                        successAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von success.mp3:', error);
                        });
                        resultHTML += `
                        <lord-icon
                            src="https://cdn.lordicon.com/lomfljuq.json"
                            trigger="in"
                            delay="500"
                            state="in-check"
                            colors="primary:#4caf50"
                            style="width:100px;height:100px;float:right;">
                        </lord-icon>
                        <h2><b><u>${data.message}</u></b></h2>
                        <div class="ticket-info">
                            <h3 style="color:black;"><b>Ticket Details:</b></h3>
                            <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                            <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}</p>
                            <p><strong><i class="fa-solid fa-calendar-days"></i> Valid Until:</strong> ${data.data.valid_date}</p>
                            <p><strong><i class="fa-solid fa-clock"></i> Used At:</strong> ${data.data.used_at || 'Not Used Yet'}</p>
                            <h4><strong><i class="fa-solid fa-exclamation-circle"></i> Access Attempts:</strong></h4>
                            <div class="attempts-summary">
                                <p><strong>Total Attempts:</strong> ${data.data.access_attempts.length}</p>
                                <p><strong>Successful Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'success').length}</p>
                                <p><strong>Failed Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'error').length}</p>
                                <ul>
                                    ${data.data.access_attempts.reverse().map(attempt => `
                                        <li class="${attempt.status}">
                                            <strong>Status:</strong> ${attempt.status} <br>
                                            <strong>Type:</strong> ${attempt.type} <br>
                                            <strong>Time:</strong> ${attempt.time}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                    `;
                        resultPopup.classList.add('valid'); // Add valid class
                        resultPopup.classList.remove('invalid'); // Remove invalid class
                    } else {
                        // Audio für Fehler abspielen
                        const errorAudio = new Audio('error.mp3');
                        errorAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von error.mp3:', error);
                        });
                        resultPopup.classList.add('invalid'); // Add invalid class
                        resultPopup.classList.remove('valid'); // Remove valid class

                        // Fehlerdaten anzeigen
                        resultHTML += `
                        <lord-icon
                            src="https://cdn.lordicon.com/zxvuvcnc.json"
                            trigger="in"
                            delay="500"
                            state="in-cross"
                            colors="primary:#f44336"
                            style="width:100px;height:100px;float:right;">
                        </lord-icon>
                        <h2><b><u>${data.message}</u></b></h2>
                        <div class="ticket-info">
                            <h3 style="color:black;"><b>Ticket Details:</b></h3>
                            <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                            <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}</p>
                            <p><strong><i class="fa-solid fa-calendar-days"></i> Valid Until:</strong> ${data.data.valid_date}</p>
                            <p><strong><i class="fa-solid fa-clock"></i> Used At:</strong> ${data.data.used_at || 'Not Used Yet'}</p>
                            <div class="attempts-summary">
                                <h4><strong><i class="fa-solid fa-exclamation-circle"></i> Access Attempts:</strong></h4>
                                <p><strong>Total Attempts:</strong> ${data.data.access_attempts.length}</p>
                                <p><strong>Successful Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'success').length}</p>
                                <p><strong>Failed Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'error').length}</p>
                                <ul>
                                    ${data.data.access_attempts.reverse().map(attempt => `
                                        <li class="${attempt.status}">
                                            <strong>Status:</strong> ${attempt.status} <br>
                                            <strong>Type:</strong> ${attempt.type} <br>
                                            <strong>Time:</strong> ${attempt.time}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                    `;
                    }

                    resultContent.innerHTML = resultHTML;
                })
                .catch(error => {
                    // Spinner Popup ausblenden
                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none'; // Hintergrund ausblenden
                    const errorAudio = new Audio('error.mp3');
                    errorAudio.play().catch(error => {
                        console.error('Fehler beim Abspielen von error.mp3:', error);
                    });
                    const resultPopup = document.getElementById('resultPopup');
                    const blurBackground = document.getElementById('blurBackground');
                    blurBackground.style.display = 'block'; // Hintergrund anzeigen
                    resultPopup.style.display = 'block'; // Popup anzeigen
                    resultContent.innerHTML = `
                    <strong><i class="fa-solid fa-circle-xmark" style="color:rgb(199, 38, 38);"></i> ${error.message}</strong>
                    <div class="timestamp">Time: ${formatDateTime(new Date())}</div>
                `;

                    resultPopup.classList.add('invalid'); // Add invalid class
                    resultPopup.classList.remove('valid'); // Remove valid class
                });
        }

        function closePopup() {
            const resultPopup = document.getElementById('resultPopup');
            const blurBackground = document.getElementById('blurBackground');
            blurBackground.style.display = 'none'; // Hintergrund ausblenden
            resultPopup.style.display = 'none'; // Popup ausblenden
            lastScannedCode = ''; // Letzten gescannten Code zurücksetzen
            lastScanTime = 0; // Letzte Scanzeit zurücksetzen
        }

        function updateDateTime() {
            const now = new Date();
            const formattedDateTime = now.toLocaleString('de-DE', {
                dateStyle: 'short',
                timeStyle: 'medium'
            });
            document.getElementById('currentDateTime').innerText = formattedDateTime;
        }

        // Datum und Uhrzeit jede Sekunde aktualisieren
        setInterval(updateDateTime, 1000);
        updateDateTime(); // Initiale Anzeige

        // Scanner initialisieren
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", {
                fps: 1,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1.0,
                rememberLastUsedCamera: true
            }
        );
        html5QrcodeScanner.render(onScanSuccess);

        // Dropdown-Menü für den Seitenwechsel
        function navigateToApp() {
            const selectedApp = document.getElementById('appSelector').value;
            if (selectedApp) {
                window.location.href = selectedApp;
            }
        }
    </script>
</body>

</html>
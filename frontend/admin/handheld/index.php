<?php

require_once '../../config.php';

$API_KEY = API_KEY;
$API_ENDPOINT = API_BASE_URL . 'api/ticket/validate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticketId = $data['ticketId'] ?? null;

    if ($ticketId) {
        $ch = curl_init($API_ENDPOINT);
        $requestData = json_encode(['tid' => $ticketId]);

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


        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo $response;
        exit;
    } else {

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Keine ticketId angegeben.'
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
    <title>Ticket Scanner</title>
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
            border: 10px solid yellow;
            /* Gelber Rand */
            animation: blink-yellow 1s infinite;
            /* Blinkende Animation */
        }

        @keyframes blink-yellow {
            0% {
                border-color: yellow;
            }

            50% {
                border-color: rgba(255, 255, 0, 0.5);
                /* Halbtransparentes Gelb */
            }

            100% {
                border-color: yellow;
            }
        }

        .spinner {
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-top: 8px solid yellow;
            /* Gelber Spinner */
            border-radius: 50%;
            width: 60px;
            /* Breite des Spinners erhöhen */
            height: 60px;
            /* Höhe des Spinners erhöhen */
            animation: spin 0.8s linear infinite;
            /* Schnelleres Drehen */
            margin: 20px auto;
            /* Zentrieren */
            box-shadow: 0 0 15px rgb(168, 127, 5);
            /* Schatten hinzufügen */
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
            margin-top: 10px;
            /* Abstand nach oben */
            border-top: 1px solid #ddd;
            /* Trennlinie */
            padding-top: 10px;
            /* Innenabstand oben */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Ticket Scanner</h1>
        <select id="appSelector" onchange="navigateToApp()" style="margin-bottom: 20px;">
            <option value="index.php">Ticket Scanner</option>
            <option value="inspector.php">Ticket Inspector</option>
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


            if (currentTime - lastScanTime < SCAN_COOLDOWN || decodedText === lastScannedCode) {
                return;
            }

            lastScannedCode = decodedText;
            lastScanTime = currentTime;


            document.getElementById('blurBackground').style.display = 'block';
            document.getElementById('spinnerPopup').style.display = 'block';


            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticketId: decodedText
                    })
                })
                .then(response => {

                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none';
                    return response.json().then(data => {
                        if (!response.ok) {

                            throw new Error(data.message || 'API-Fehler');
                        }
                        return data;
                    });
                })
                .then(data => {

                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none';
                    const resultPopup = document.getElementById('resultPopup');
                    const blurBackground = document.getElementById('blurBackground');
                    blurBackground.style.display = 'block';
                    resultPopup.style.display = 'block';

                    let resultHTML = `
                    <div class="timestamp">Scan Time: ${formatDateTime(new Date())}</div>
                `;

                    if (data.status === 'success') {

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
                        <h3><b>Person is allowed to enter.</b></h3>
                        <div class="ticket-info">
                            <h3 style="color:black;"><b>Ticket Details:</b></h3>
                            <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                            <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Type:</strong> ${data.data.type}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}</p>
                            <p><strong><i class="fa-solid fa-calendar-days"></i> Valid Until:</strong> ${data.data.valid_date}</p>
                            <p><strong><i class="fa-solid fa-clock"></i> Used At:</strong> ${data.data.used_at || 'Not Used Yet'}</p>
                            <div class="attempts-summary">
                                <p><strong><i class="fa-solid fa-exclamation-circle"></i> Access Attempts:</strong> ${data.data.access_attempts.length}</p>
                                <p><strong><i class="fa-solid fa-check-circle"></i> Successful Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'success').length}</p>
                                <p><strong><i class="fa-solid fa-times-circle"></i> Failed Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'error').length}</p>
                            </div>
                        </div>
                    `;
                        resultPopup.classList.add('valid');
                        resultPopup.classList.remove('invalid');
                    } else {

                        const errorAudio = new Audio('error.mp3');
                        errorAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von error.mp3:', error);
                        });
                        resultPopup.classList.add('invalid');
                        resultPopup.classList.remove('valid');


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
                        <h3><b>Person is NOT allowed to enter.</b></h3>
                        <div class="ticket-info">
                            <h3 style="color:black;"><b>Ticket Details:</h3>
                            <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                            <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Type:</strong> ${data.data.type}</p>
                            <p><strong><i class="fa-solid fa-coins"></i> Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}</p>
                            <p><strong><i class="fa-solid fa-calendar-days"></i> Valid Until:</strong> ${data.data.valid_date}</p>
                            <p><strong><i class="fa-solid fa-clock"></i> Used At:</strong> ${data.data.used_at || 'Not Used Yet'}</p>
                            <div class="attempts-summary">
                                <p><strong><i class="fa-solid fa-exclamation-circle"></i> Access Attempts:</strong> ${data.data.access_attempts.length || "N/A"}</p>
                                <p><strong><i class="fa-solid fa-check-circle"></i> Successful Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'success').length}</p>
                                <p><strong><i class="fa-solid fa-times-circle"></i> Failed Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'error').length || "N/A"}</p>
                            </div>
                        </div>
                    `;
                    }

                    resultContent.innerHTML = resultHTML;
                })
                .catch(error => {

                    document.getElementById('spinnerPopup').style.display = 'none';
                    document.getElementById('blurBackground').style.display = 'none';
                    const errorAudio = new Audio('error.mp3');
                    errorAudio.play().catch(error => {
                        console.error('Fehler beim Abspielen von error.mp3:', error);
                    });
                    const resultPopup = document.getElementById('resultPopup');
                    const blurBackground = document.getElementById('blurBackground');
                    blurBackground.style.display = 'block';
                    resultPopup.style.display = 'block';
                    resultContent.innerHTML = `
                    <strong><i class="fa-solid fa-circle-xmark" style="color:rgb(199, 38, 38);"></i> ${error.message}</strong>
                    <div class="timestamp">Time: ${formatDateTime(new Date())}</div>
                `;

                    resultPopup.classList.add('invalid');
                    resultPopup.classList.remove('valid');
                });
        }

        function closePopup() {
            const resultPopup = document.getElementById('resultPopup');
            const blurBackground = document.getElementById('blurBackground');
            blurBackground.style.display = 'none';
            resultPopup.style.display = 'none';
            lastScannedCode = '';
            lastScanTime = 0;
        }

        function updateDateTime() {
            const now = new Date();
            const formattedDateTime = now.toLocaleString('de-DE', {
                dateStyle: 'short',
                timeStyle: 'medium'
            });
            document.getElementById('currentDateTime').innerText = formattedDateTime;
        }


        setInterval(updateDateTime, 1000);
        updateDateTime();


        setInterval(() => {
            const selectElement = document.getElementById('html5-qrcode-select-camera');
            if (selectElement) {
                document.querySelectorAll("#html5-qrcode-select-camera option").forEach(option => {
                    if (option.textContent.includes("front")) {
                        option.remove();
                    }
                });
                selectElement.selectedIndex = 0; 
                clearInterval(this);
            }
        }, 1000);

        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", {
                fps: 1,
                qrbox: {
                    width: 200,
                    height: 200
                },
                aspectRatio: 1.0,
                rememberLastUsedCamera: true
            }
        );
        html5QrcodeScanner.render(onScanSuccess);


        function navigateToApp() {
            const selectedApp = document.getElementById('appSelector').value;
            if (selectedApp) {
                window.location.href = selectedApp;
            }
        }
    </script>
</body>

</html>
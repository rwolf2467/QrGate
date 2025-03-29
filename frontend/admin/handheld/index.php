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
    <!--<script src="https://unpkg.com/html5-qrcode"></script>-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #121212;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            background: #1e1e1e;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            padding: 20px;
            text-align: center;
        }

        h1 {
            font-size: 20px;
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
            font-size: 12px;
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

        @media (max-width: 600px) {
            h1 {
                font-size: 20px;
            }

            .container {
                padding: 10px;
            }

            #reader {
                max-width: 250px;
            }
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #2c2c2c;
            padding: 20px;
            border-radius: 8px;
            z-index: 1000;
            max-width: 100%;
            box-sizing: border-box;
            width: 95%;
        }

        .popup h2 {
            margin: 0 0 10px;
            font-size: 15px;
        }

        .popup p {
            margin: 5px 0;
            line-height: 1.5;
            font-size: 12px;
        }

        .popup.valid {
            border: 10px solid #4caf50;
            animation: blink-green 1s infinite;
            width: 95%;
        }

        @keyframes blink-green {
            0% {
                border: 10px solid transparent;
            }

            50% {
                border: 10px solid #4caf50;
            }

            100% {
                border: 10px solid transparent;
            }

        }

        .popup.invalid {
            border: 10px solid #f44336;
            animation: blink-red 1s infinite;

            width: 95%;
        }

        @keyframes blink-red {
            0% {
                border: 10px solid transparent;
            }

            50% {
                border: 10px solid #f44336;
            }

            100% {
                border: 10px solid transparent;
            }
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
            backdrop-filter: blur(10px);
            z-index: 999;
            display: none;
        }

        #currentDateTime {
            color: rgb(137, 137, 137);
            margin-bottom: 10px;
        }

        .popup#spinnerPopup {
            border: 10px solid yellow;
            animation: blink-yellow 1s infinite;
        }

        @keyframes blink-yellow {
            0% {
                border-color: yellow;
            }

            50% {
                border-color: rgba(255, 255, 0, 0.5);
            }

            100% {
                border-color: yellow;
            }
        }

        .loader {
            width: 215px;
            height: 215px;
            display: block;
            margin: auto;
            position: relative;
            background: #121212;
            box-sizing: border-box;
            border-radius: 14px;
        }

        .loader::after {
            content: '';
            width: calc(100% - 30px);
            height: calc(100% - 15px);
            top: 15px;
            left: 15px;
            position: absolute;
            background-image: linear-gradient(90deg, transparent, rgba(132, 132, 132, 0.5) 50%, transparent 100%),
                linear-gradient(rgb(75, 75, 75) 100px, transparent 0),
                linear-gradient(rgb(75, 75, 75) 16px, transparent 0),
                linear-gradient(rgb(75, 75, 75) 50px, transparent 0);
            background-repeat: no-repeat;
            background-size: 75px 175px, 100% 100px, 100% 16px, 100% 30px;
            background-position: -185px 0, center 0, center 115px, center 142px;
            box-sizing: border-box;
            animation: animloader .7s linear infinite;
        }

        @keyframes animloader {
            to {
                background-position: 185px 0, center 0, center 115px, center 142px;
            }
        }

        .attempts-summary {
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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

        <h3 id="successMessage"><b>Authorised</b></h3>
        <h3 id="errorMessage"><b>Denied</b></h3>
        <div id="resultContent"></div>
        <button onclick="closePopup()">Close</button>
    </div>
    <div class="popup" id="spinnerPopup" style="display: none;">
        <span class="loader"></span>
        <h2 style="justify-content: center;">Loading...</h2>
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
                `;

                    if (data.status === 'success') {
                        const successAudio = new Audio('success.mp3');
                        successAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von success.mp3:', error);
                        });


                        resultHTML += `
                            <h4><b><u>${data.message}</u></b></h4>
                            <div class="ticket-info">
                                <h3 style="color:black;"><b>Ticket Details:</b></h3>
                                <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                                <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                                <p><strong><i class="fa-solid fa-filter"></i> Type:</strong> ${data.data.type}</p>
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

                        document.getElementById("successMessage").innerHTML = "Authorised";
                        document.getElementById("successMessage").style.display = "block";
                        document.getElementById("errorMessage").style.display = "none";
                    } else {
                        const errorAudio = new Audio('error.mp3');
                        errorAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von error.mp3:', error);
                        });

                        resultPopup.classList.add('invalid');
                        resultPopup.classList.remove('valid');


                        resultHTML += `
                            <h4><b><u>${data.message}</u></b></h4>
                            <div class="ticket-info">
                                <h3 style="color:black;"><b>Ticket Details:</b></h3>
                                <p><strong><i class="fa-solid fa-ticket"></i> Ticket ID:</strong> ${data.data.tid}</p>
                                <p><strong><i class="fa-solid fa-user"></i> Name:</strong> ${data.data.first_name} ${data.data.last_name}</p>
                                <p><strong><i class="fa-solid fa-filter"></i> Type:</strong> ${data.data.type}</p>
                                <p><strong><i class="fa-solid fa-coins"></i> Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}</p>
                                <p><strong><i class="fa-solid fa-calendar-days"></i> Valid Until:</strong> ${data.data.valid_date}</p>
                                <p><strong><i class="fa-solid fa-clock"></i> Used At:</strong> ${data.data.used_at || 'Not Used Yet'}</p>
                                <div class="attempts-summary">
                                    <p><strong><i class="fa-solid fa-exclamation-circle"></i> Access Attempts:</strong> ${data.data.access_attempts.length || "N/A"}</p>
                                    <p><strong><i class="fa-solid fa-check-circle"></i> Successful Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'success').length || "N/A"}</p>
                                    <p><strong><i class="fa-solid fa-times-circle"></i> Failed Attempts:</strong> ${data.data.access_attempts.filter(attempt => attempt.status === 'error').length || "N/A"}</p>
                                </div>
                            </div>
                        `;


                        document.getElementById("errorMessage").innerHTML = "Denied";
                        document.getElementById("errorMessage").style.display = "block";
                        document.getElementById("successMessage").style.display = "none";
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
        setTimeout(() => {
            html5QrcodeScanner.start();
        }, 1500);


        function navigateToApp() {
            const selectedApp = document.getElementById('appSelector').value;
            if (selectedApp) {
                window.location.href = selectedApp;
            }
        }
        var textSuccess = ["Einlass erlaubt", "Access granted"];
        var currentIndexSuccess = 0;

        function updateTextSuccess() {
            document.getElementById("successMessage").innerHTML = textSuccess[currentIndexSuccess];

            setTimeout(function () {
                currentIndexSuccess = (currentIndexSuccess + 1) % textSuccess.length;
                updateTextSuccess();
            }, 1000);
        }

        updateTextSuccess();

        var textError = ["Zutritt verweigert", "Access denied"];
        var currentIndexError = 0;

        function updateTextError() {
            document.getElementById("errorMessage").innerHTML = textError[currentIndexError];

            setTimeout(function () {
                currentIndexError = (currentIndexError + 1) % textError.length;
                updateTextError();
            }, 1000);
        }

        updateTextError();
    </script>
</body>

</html>
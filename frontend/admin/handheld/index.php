<?php
require_once '../../config.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['handheld_access'])) {
    header('Location: ../login.php?redirect=handheld');
    exit;
}

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
<html lang="de" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Scanner</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="./manifest.json">
    <!-- Safari PWA settings -->
    <link rel="apple-touch-icon" href="./icon-192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="QR Scanner">
    <!-- Windows PWA settings -->
    <meta name="msapplication-TileImage" content="./icon-192x192.png">
    <meta name="msapplication-TileColor" content="#0f172a">
    <!-- Theme Color -->
    <meta name="theme-color" content="#0f172a">
    <!-- Register service worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('./sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
            
            // Listen for the 'beforeinstallprompt' event to manually trigger installation
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevent the mini-infobar from appearing on mobile
                e.preventDefault();
                // Stash the event so it can be triggered later
                deferredPrompt = e;
                
                // Show the install button if we want to manually trigger the installation
                const installButton = document.createElement('button');
                installButton.id = 'install-button';
                installButton.textContent = 'App installieren';
                installButton.style.position = 'fixed';
                installButton.style.bottom = '20px';
                installButton.style.right = '20px';
                installButton.style.zIndex = '1000';
                installButton.style.padding = '10px 20px';
                installButton.style.backgroundColor = '#4CAF50';
                installButton.style.color = 'white';
                installButton.style.border = 'none';
                installButton.style.borderRadius = '5px';
                installButton.style.cursor = 'pointer';
                installButton.style.display = 'none'; // Initially hidden
                
                document.body.appendChild(installButton);
                
                installButton.addEventListener('click', () => {
                    // Show the install prompt
                    deferredPrompt.prompt();
                    // Wait for the user to respond to the prompt
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                        installButton.style.display = 'none';
                    });
                });
            });
            
            // Listen for the app installed event
            window.addEventListener('appinstalled', () => {
                console.log('PWA was installed');
                const installButton = document.getElementById('install-button');
                if (installButton) {
                    installButton.style.display = 'none';
                }
            });
        }
    </script>
    
    <!--<script src="https://unpkg.com/html5-qrcode"></script>-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap" rel="stylesheet">
    <style>
        body {
            color: white;
            font-family: 'Quicksand', sans-serif;
        }

        @media (max-width: 640px) {
            dialog {
                width: auto !important;
                max-width: 90vw !important;
                max-height: 90vh !important;
                min-height: 50vh !important;
                background-color: var(--card-background) !important;
                color: var(--text-color) !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                padding: 0 !important;
                z-index: 1000 !important;
                margin: auto !important;
            }

            dialog>div {
                max-height: calc(80vh - 4rem);
                overflow-y: auto;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <dialog id="spinnerPopup" class="dialog w-full sm:max-w-[425px] max-h-[612px]"
        aria-labelledby="demo-dialog-edit-profile-title" aria-describedby="demo-dialog-edit-profile-description"
        onclick="if (event.target === this) this.close()">


        <div
            class="flex min-w-0 flex-1 flex-col items-center justify-center gap-6 rounded-lg p-6 text-center text-balance md:p-12 text-neutral-800 dark:text-neutral-300">
            <header class="flex max-w-sm flex-col items-center gap-3 text-center">
                <div
                    class="mb-2 bg-muted text-foreground flex size-10 shrink-0 items-center justify-center rounded-lg [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        role="status" aria-label="Loading" class="animate-spin size-8">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold tracking-tight">Processing your request</h3>
                <p class="text-muted-foreground text-sm/relaxed">Please wait while we process your request. Do not
                    refresh the page.</p>
            </header>

        </div>


    </dialog>
    <dialog id="resultPopup" class="dialog w-full sm:max-w-[425px] max-h-[612px]">
        <div class="max-h-[80vh] overflow-y-auto p-6">
            <header>
                <h3 id="successMessage"><b>Ticket valid</b></h3>
                <h3 id="errorMessage"><b>Ticket unvalid</b></h3>
            </header>
            <section>
                <div id="resultContent"></div>
            </section>
            <button type="button" aria-label="Close dialog" onclick="this.closest('dialog').close()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-x-icon lucide-x">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
    </dialog>
    <header class="bg-darker border-b border-border px-6 py-4 flex justify-between items-center">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-scan-qr-code-icon lucide-scan-qr-code">
                <path d="M17 12v4a1 1 0 0 1-1 1h-4" />
                <path d="M17 3h2a2 2 0 0 1 2 2v2" />
                <path d="M17 8V7" />
                <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
                <path d="M3 7V5a2 2 0 0 1 2-2h2" />
                <path d="M7 17h.01" />
                <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
                <rect x="7" y="7" width="5" height="5" rx="1" />
            </svg>
            <h1 class="text-2xl font-bold">Handheld - Ticket Scanner</h1>
        </div>
        <div class="flex items-center gap-4">
            <button id="install-button-header" class="btn btn-secondary" style="display:none; margin-right: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-download">
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7" />
                    <path d="M12 2v12" />
                    <path d="m8 10 4 4 4-4" />
                </svg>
                Install App
            </button>
            <a href="../logout.php" class="btn-destructive">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-log-out-icon lucide-log-out">
                    <path d="m16 17 5-5-5-5" />
                    <path d="M21 12H9" />
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                </svg>
                Logout
            </a>
        </div>
    </header>

    <script>
        // Skript zum Anzeigen des Installationsbuttons im Header
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Verhindere das Standardverhalten
            e.preventDefault();
            // Speichere das Event für später
            deferredPrompt = e;
            
            // Zeige den Installationsbutton im Header
            const installButton = document.getElementById('install-button-header');
            if (installButton) {
                installButton.style.display = 'inline-flex'; // Zeige den Button an
                installButton.onclick = () => {
                    // Zeige den Installationsdialog
                    deferredPrompt.prompt();
                    
                    // Warte auf die Benutzerantwort
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('Benutzer hat die Installation akzeptiert');
                            installButton.style.display = 'none'; // Verstecke den Button nach erfolgreicher Installation
                        } else {
                            console.log('Benutzer hat die Installation abgelehnt');
                        }
                        deferredPrompt = null;
                    });
                };
            }
        });
        
        // Event für erfolgreiche Installation
        window.addEventListener('appinstalled', () => {
            console.log('PWA wurde installiert');
            const installButton = document.getElementById('install-button-header');
            if (installButton) {
                installButton.style.display = 'none';
            }
        });
    </script>
    <main class="container mx-auto px-6 py-8">
        <div class="card">
            <header>
                <h1>Ticket Scanner</h1>
                <select id="appSelector" onchange="navigateToApp()"
                    style="margin-bottom: 20px; background: var(--darker); color: white; border: 1px solid var(--border); padding: 8px; border-radius: 4px;">
                    <option value="index.php">Ticket Scanner</option>
                    <option value="inspector.php">Ticket Inspector</option>
                </select>
            </header>
            <section>
                <div id="currentDateTime"></div>
                <div id="reader"></div>
                <div id="result"></div>
            </section>
        </div>
    </main>
    <script>
        let lastScannedCode = '';
        let lastScanTime = 0;
        const SCAN_COOLDOWN = 1000;
        const SAME_SCAN_COOLDOWN = 5000;

        function formatDateTime(date) {
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        function getLocalDateYYYYMMDD() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        function onScanSuccess(decodedText) {
            const currentTime = Date.now();


            if (decodedText === lastScannedCode && (currentTime - lastScanTime < SAME_SCAN_COOLDOWN)) {
                return;
            }

            lastScannedCode = decodedText;
            lastScanTime = currentTime;


            document.getElementById('spinnerPopup').showModal();


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

                    document.getElementById('spinnerPopup').close();
                    return response.json().then(data => {
                        if (!response.ok) {

                            throw new Error(data.message || 'API-Fehler');
                        }
                        return data;
                    });
                })
                .then(data => {

                    document.getElementById('spinnerPopup').close();
                    const resultPopup = document.getElementById('resultPopup');
                    resultPopup.showModal();

                    let resultHTML = `
                 `;

                    if (data.status === 'success') {
                        const successAudio = new Audio('./success.mp3');
                        successAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von success.mp3:', error);
                        });


                        resultHTML += `
         <span class="badge-secondary bg-green-500 text-white dark:bg-green-600 text-center mb-3 text-lg font-bold flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
            ${data.message}
         </span>
         <div class="ticket-info">
         <h3 class="font-bold mb-2" style="color: white;">Ticket Details:</h3>

         <!-- Ticket ID -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ticket">
         <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
         <path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>
         </svg>
         <strong>Ticket ID:</strong> ${data.data.tid}
         </div>

         <!-- Name -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user">
         <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
         </svg>
         <strong>Name:</strong> ${data.data.first_name} ${data.data.last_name}
         </div>

         <!-- Type -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cog">
         <path d="M11 10.27 7 3.34"/><path d="m11 13.73-4 6.93"/><path d="M12 22v-2"/><path d="M12 2v2"/><path d="M14 12h8"/><path d="m17 20.66-1-1.73"/><path d="m17 3.34-1 1.73"/><path d="M2 12h2"/><path d="m20.66 17-1.73-1"/><path d="m20.66 7-1.73 1"/><path d="m3.34 17 1.73-1"/><path d="m3.34 7 1.73 1"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="8"/>
         </svg>
         <strong>Type:</strong> ${data.data.type}
         </div>

         <!-- Paid -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-coins">
         <circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>
         </svg>
         <strong>Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}
         </div>

         <!-- Valid Until -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days">
         <path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>
         </svg>
         <strong>Valid on:</strong> ${data.data.valid_date} | <strong>Today?:</strong> ${getLocalDateYYYYMMDD() === data.data.valid_date ? 'Yes' : 'No'}
         </div>

         <!-- Used At -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock-4">
         <path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/>
         </svg>
         <strong>Used At:</strong> ${data.data.used_at || 'Not Used Yet'}
         </div>

         <!-- Attempts Summary -->
         <div class="attempts-summary mt-3 pt-2 border-t border-gray-700">
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in-icon lucide-log-in"><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg>
         <strong>Access Attempts:</strong> ${data.data.access_attempts?.length || 0}
         </div>
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check">
           <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
         </svg>
         <strong>Successful Attempts:</strong> ${data.data.access_attempts?.filter(a => a.status === 'success').length || 0}
         </div>
         <div class="flex items-center gap-2">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
         <strong>Failed Attempts:</strong> ${data.data.access_attempts?.filter(a => a.status === 'error').length || 0}
         </div>
         </div>
         </div>
         `;
                        resultPopup.classList.add('valid');
                        resultPopup.classList.remove('invalid');

                        document.getElementById("successMessage").innerHTML = "Ticket valid";
                        document.getElementById("successMessage").style.display = "block";
                        document.getElementById("errorMessage").style.display = "none";
                    } else {
                        const errorAudio = new Audio('./error.mp3');
                        errorAudio.play().catch(error => {
                            console.error('Fehler beim Abspielen von error.mp3:', error);
                        });

                        resultPopup.classList.add('invalid');
                        resultPopup.classList.remove('valid');


                        resultHTML += `
         <span class="badge-secondary bg-red-500 text-white dark:bg-red-600 text-center mb-3 text-lg font-bold flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width=30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-x-icon lucide-badge-x"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
            ${data.message}
         </span>
         <div class="ticket-info">
         <h3 class="font-bold mb-2" style="color: white;">Ticket Details:</h3>

         <!-- Ticket ID -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ticket">
         <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
         <path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>
         </svg>
         <strong>Ticket ID:</strong> ${data.data.tid}
         </div>

         <!-- Name -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user">
         <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
         </svg>
         <strong>Name:</strong> ${data.data.first_name} ${data.data.last_name}
         </div>

         <!-- Type -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cog">
         <path d="M11 10.27 7 3.34"/><path d="m11 13.73-4 6.93"/><path d="M12 22v-2"/><path d="M12 2v2"/><path d="M14 12h8"/><path d="m17 20.66-1-1.73"/><path d="m17 3.34-1 1.73"/><path d="M2 12h2"/><path d="m20.66 17-1.73-1"/><path d="m20.66 7-1.73 1"/><path d="m3.34 17 1.73-1"/><path d="m3.34 7 1.73 1"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="8"/>
         </svg>
         <strong>Type:</strong> ${data.data.type}
         </div>

         <!-- Paid -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-coins">
         <circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>
         </svg>
         <strong>Paid:</strong> ${data.data.paid ? 'Yes' : 'No'}
         </div>

         <!-- Valid Until -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days">
         <path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>
         </svg>
         <strong>Valid on:</strong> ${data.data.valid_date} | <strong>Today?:</strong> ${getLocalDateYYYYMMDD() === data.data.valid_date ? 'Yes' : 'No'}
         </div>

         <!-- Used At -->
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock-4">
         <path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/>
         </svg>
         <strong>Used At:</strong> ${data.data.used_at || 'Not Used Yet'}
         </div>

         <!-- Attempts Summary -->
         <div class="attempts-summary mt-3 pt-2 border-t border-gray-700">
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in-icon lucide-log-in"><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg>
         <strong>Access Attempts:</strong> ${data.data.access_attempts?.length || 0}
         </div>
         <div class="flex items-center gap-2 mb-1">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check">
           <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
         </svg>
         <strong>Successful Attempts:</strong> ${data.data.access_attempts?.filter(a => a.status === 'success').length || 0}
         </div>
         <div class="flex items-center gap-2">
         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
         <strong>Failed Attempts:</strong> ${data.data.access_attempts?.filter(a => a.status === 'error').length || 0}
         </div>
         </div>
         </div>
         `;


                        document.getElementById("errorMessage").innerHTML = "Ticket unvalid";
                        document.getElementById("errorMessage").style.display = "none";
                        document.getElementById("successMessage").style.display = "none";
                    }


                    resultContent.innerHTML = resultHTML;
                })
                .catch(error => {

                    document.getElementById('spinnerPopup').close();
                    const errorAudio = new Audio('error.mp3');
                    errorAudio.play().catch(error => {
                        console.error('Fehler beim Abspielen von error.mp3:', error);
                    });
                    const resultPopup = document.getElementById('resultPopup');
                    const blurBackground = document.getElementById('blurBackground');
                    blurBackground.style.display = 'block';
                    resultPopup.showModal();
                    resultContent.innerHTML = `
                     <strong><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg> ${error.message}</strong>
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
            resultPopup.close();
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
                width: 150,
                height: 150
            },
            aspectRatio: 0.3,
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
    </script>
</body>

</html>
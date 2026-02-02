<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$languages = [
    'en' => [
        'flag' => '🇬🇧',
        'name' => 'English',
        'page_title' => 'Help - How to buy tickets',
        'hero_title' => 'How to buy tickets',
        'hero_subtitle' => 'Step-by-step guide to purchasing your event tickets',
        'back_to_shop' => 'Back to ticket shop',
        'method_cash' => 'Cash Payment',
        'method_cash_subtitle' => 'Reserve now, pay at the venue',
        'method_online' => 'Online Payment',
        'method_online_subtitle' => 'Pay immediately with PayPal or card',
        'step' => 'Step',
        'cash_steps' => [
            [
                'title' => 'Select your event date',
                'description' => 'On the main page, you will see all available event dates displayed as cards. Each card shows the date, time, ticket price, and how many tickets are still available. Click the "Buy Tickets" button on your desired date.'
            ],
            [
                'title' => 'Enter your personal information',
                'description' => 'A booking form will open. Fill in your first name, last name, and email address. Make sure your email is correct - this is where your ticket will be sent! If you are buying multiple tickets, you will also need to enter the names for each additional person.'
            ],
            [
                'title' => 'Choose "Cash Payment"',
                'description' => 'You will see two payment options: "Cash payment" and "Online payment". Click on the "Cash payment" button (with the coins icon). This means you will pay for your ticket at the venue on the day of the event.'
            ],
            [
                'title' => 'Confirm your booking',
                'description' => 'After selecting cash payment, a "Book Tickets" button will appear. Click it to complete your reservation. Your ticket is now reserved but not yet paid.'
            ],
            [
                'title' => 'Check your email',
                'description' => 'You will receive an email with your ticket attached as a PDF file. The ticket contains a QR code that will be scanned at the entrance. Important: Bring this ticket (printed or on your phone) and the payment amount to the event.'
            ],
            [
                'title' => 'Pay at the venue',
                'description' => 'On the day of the event, go to the entrance and show your ticket. Pay the ticket price in cash, and your ticket will be validated for entry. Keep your ticket ready for scanning!'
            ]
        ],
        'online_steps' => [
            [
                'title' => 'Select your event date',
                'description' => 'On the main page, browse through the available event dates. Each card displays the date, time, ticket price, and remaining availability. Find your preferred date and click "Buy Tickets".'
            ],
            [
                'title' => 'Enter your personal information',
                'description' => 'Fill out the booking form with your first name, last name, and email address. Double-check your email address - your ticket will be sent there immediately after payment. For multiple tickets, enter the name of each attendee.'
            ],
            [
                'title' => 'Choose "Online Payment"',
                'description' => 'Click on the "Online payment" button (with the credit card icon). This allows you to pay immediately using PayPal or a debit/credit card.'
            ],
            [
                'title' => 'Select your payment method',
                'description' => 'PayPal payment buttons will appear. You can either log in to your PayPal account to pay, or click "Debit or Credit Card" to pay without a PayPal account. Both options are secure.'
            ],
            [
                'title' => 'Complete your payment',
                'description' => 'Follow the PayPal instructions to complete your payment. Enter your card details or PayPal credentials. Once the payment is confirmed, your ticket is automatically marked as paid.'
            ],
            [
                'title' => 'Receive your ticket',
                'description' => 'Immediately after successful payment, you will receive an email with your ticket as a PDF attachment. The ticket contains a QR code. Simply show this QR code (on your phone or printed) at the entrance - no additional payment needed!'
            ]
        ],
        'tips_title' => 'Tips',
        'tips' => [
            'Save your ticket email or download the PDF to make sure you have it on the event day.',
            'If you do not receive your ticket email within a few minutes, check your spam folder.',
            'You can show the ticket on your phone screen - no need to print it.',
            'Each ticket has a unique QR code and can only be used once for entry.'
        ],
        'questions_title' => 'Still have questions?',
        'questions_text' => 'If you need further assistance, please contact the event organizer directly.'
    ],
    'de' => [
        'flag' => '🇩🇪',
        'name' => 'Deutsch',
        'page_title' => 'Hilfe - So kaufst du Tickets',
        'hero_title' => 'So kaufst du Tickets',
        'hero_subtitle' => 'Schritt-für-Schritt Anleitung zum Ticketkauf',
        'back_to_shop' => 'Zurück zum Ticketshop',
        'method_cash' => 'Barzahlung',
        'method_cash_subtitle' => 'Jetzt reservieren, vor Ort bezahlen',
        'method_online' => 'Online-Zahlung',
        'method_online_subtitle' => 'Sofort mit PayPal oder Karte bezahlen',
        'step' => 'Schritt',
        'cash_steps' => [
            [
                'title' => 'Wähle dein Veranstaltungsdatum',
                'description' => 'Auf der Hauptseite siehst du alle verfügbaren Veranstaltungstermine als Karten. Jede Karte zeigt das Datum, die Uhrzeit, den Ticketpreis und wie viele Tickets noch verfügbar sind. Klicke auf "Tickets kaufen" bei deinem gewünschten Termin.'
            ],
            [
                'title' => 'Gib deine persönlichen Daten ein',
                'description' => 'Ein Buchungsformular öffnet sich. Trage deinen Vornamen, Nachnamen und deine E-Mail-Adresse ein. Achte darauf, dass deine E-Mail korrekt ist - dorthin wird dein Ticket gesendet! Wenn du mehrere Tickets kaufst, musst du auch die Namen der weiteren Personen angeben.'
            ],
            [
                'title' => 'Wähle "Barzahlung"',
                'description' => 'Du siehst zwei Zahlungsoptionen: "Barzahlung" und "Online-Zahlung". Klicke auf den "Barzahlung"-Button (mit dem Münzen-Symbol). Das bedeutet, dass du dein Ticket am Veranstaltungstag vor Ort bezahlst.'
            ],
            [
                'title' => 'Bestätige deine Buchung',
                'description' => 'Nach der Auswahl von Barzahlung erscheint ein "Tickets buchen"-Button. Klicke darauf, um deine Reservierung abzuschließen. Dein Ticket ist jetzt reserviert, aber noch nicht bezahlt.'
            ],
            [
                'title' => 'Prüfe deine E-Mails',
                'description' => 'Du erhältst eine E-Mail mit deinem Ticket als PDF-Anhang. Das Ticket enthält einen QR-Code, der am Eingang gescannt wird. Wichtig: Bringe dieses Ticket (ausgedruckt oder auf dem Handy) und den Geldbetrag zur Veranstaltung mit.'
            ],
            [
                'title' => 'Bezahle vor Ort',
                'description' => 'Am Veranstaltungstag gehst du zum Eingang und zeigst dein Ticket. Bezahle den Ticketpreis in bar, und dein Ticket wird für den Einlass freigeschaltet. Halte dein Ticket zum Scannen bereit!'
            ]
        ],
        'online_steps' => [
            [
                'title' => 'Wähle dein Veranstaltungsdatum',
                'description' => 'Auf der Hauptseite kannst du durch die verfügbaren Termine blättern. Jede Karte zeigt Datum, Uhrzeit, Ticketpreis und verbleibende Verfügbarkeit. Finde deinen Wunschtermin und klicke auf "Tickets kaufen".'
            ],
            [
                'title' => 'Gib deine persönlichen Daten ein',
                'description' => 'Fülle das Buchungsformular mit deinem Vornamen, Nachnamen und deiner E-Mail-Adresse aus. Überprüfe deine E-Mail-Adresse - dein Ticket wird sofort nach der Zahlung dorthin geschickt. Bei mehreren Tickets gib den Namen jedes Teilnehmers ein.'
            ],
            [
                'title' => 'Wähle "Online-Zahlung"',
                'description' => 'Klicke auf den "Online-Zahlung"-Button (mit dem Kreditkarten-Symbol). Damit kannst du sofort mit PayPal oder einer Debit-/Kreditkarte bezahlen.'
            ],
            [
                'title' => 'Wähle deine Zahlungsmethode',
                'description' => 'PayPal-Zahlungsbuttons erscheinen. Du kannst dich entweder in dein PayPal-Konto einloggen, oder auf "Debit- oder Kreditkarte" klicken, um ohne PayPal-Konto zu bezahlen. Beide Optionen sind sicher.'
            ],
            [
                'title' => 'Schließe die Zahlung ab',
                'description' => 'Folge den PayPal-Anweisungen, um deine Zahlung abzuschließen. Gib deine Kartendaten oder PayPal-Zugangsdaten ein. Sobald die Zahlung bestätigt ist, wird dein Ticket automatisch als bezahlt markiert.'
            ],
            [
                'title' => 'Erhalte dein Ticket',
                'description' => 'Direkt nach erfolgreicher Zahlung erhältst du eine E-Mail mit deinem Ticket als PDF-Anhang. Das Ticket enthält einen QR-Code. Zeige diesen QR-Code einfach am Eingang (auf dem Handy oder ausgedruckt) - keine weitere Zahlung nötig!'
            ]
        ],
        'tips_title' => 'Tipps',
        'tips' => [
            'Speichere deine Ticket-E-Mail oder lade das PDF herunter, damit du es am Veranstaltungstag sicher hast.',
            'Wenn du deine Ticket-E-Mail nicht innerhalb weniger Minuten erhältst, prüfe deinen Spam-Ordner.',
            'Du kannst das Ticket auf deinem Handybildschirm zeigen - Ausdrucken ist nicht nötig.',
            'Jedes Ticket hat einen einzigartigen QR-Code und kann nur einmal für den Einlass verwendet werden.'
        ],
        'questions_title' => 'Noch Fragen?',
        'questions_text' => 'Wenn du weitere Hilfe benötigst, kontaktiere bitte direkt den Veranstalter.'
    ],
];

$current_language = $_SESSION['language'] ?? 'en';
$lang = $languages[$current_language];
$shows = getShows();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shows['orga_name']); ?> - <?php echo $lang['page_title']; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo API_BASE_URL; ?>/api/image/get/logo.png?t=<?php echo time(); ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

        :root {
            --background-color: #0a0a0a;
            --card-background: #111111;
            --text-color: #ffffff;
            --text-secondary: #888888;
            --border-color: #222222;
        }

        body {
            font-family: 'Quicksand', sans-serif;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .show-name {
            padding: 2px 8px;
            background-color: rgba(147, 51, 234, 0.2);
            border-radius: 4px;
            color: rgb(216, 180, 254);
        }

        .language-selector {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .language-selector select {
            background-color: var(--card-background);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px;
            cursor: pointer;
        }

        .step-number {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 50%;
            background-color: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
            color: var(--text-color);
        }
    </style>
</head>

<body>
    <div class="language-selector">
        <form method="post" id="langForm">
            <select name="language" onchange="this.form.submit()">
                <?php foreach ($languages as $code => $l): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($current_language == $code) ? 'selected' : ''; ?>>
                        <span class="flag"><?php echo $l['flag']; ?></span>
                        <?php echo $l['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="animate-fade-in">
        <!-- Hero Section -->
        <div class="relative w-full min-h-[40vh] max-h-[50vh] flex items-center justify-center mb-8 px-4">
            <div id="bannerBackground" class="absolute inset-0 bg-cover bg-center"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const timestamp = new Date().getTime();
                    document.getElementById('bannerBackground').style.backgroundImage = `url('<?php echo API_BASE_URL; ?>/api/image/get/banner.png?t=${timestamp}')`;
                });
            </script>
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center max-w-2xl w-full py-6 md:py-10 px-3 rounded-xl">
                <h1 class="text-3xl md:text-5xl font-bold mb-4 animate-fade-in-up">
                    <?php echo $lang['hero_title']; ?>
                </h1>
                <p class="text-lg md:text-xl mb-6 animate-fade-in-up">
                    <?php echo $lang['hero_subtitle']; ?>
                </p>
                <a href="../index.php" class="btn-primary inline-flex items-center gap-2 animate-fade-in-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7"/>
                        <path d="M19 12H5"/>
                    </svg>
                    <?php echo $lang['back_to_shop']; ?>
                </a>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- Cash Payment Section -->
            <div class="card mb-6">
                <div class="p-6">
                    <header class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="8" r="6"/>
                                <path d="M18.09 10.37A6 6 0 1 1 10.34 18"/>
                                <path d="M7 6h1v4"/>
                                <path d="m16.71 13.88.7.71-2.82 2.82"/>
                            </svg>
                            <div>
                                <h2 class="text-xl font-bold"><?php echo $lang['method_cash']; ?></h2>
                                <p class="text-sm text-gray-400"><?php echo $lang['method_cash_subtitle']; ?></p>
                            </div>
                        </div>
                    </header>

                    <div class="space-y-4">
                        <?php foreach ($lang['cash_steps'] as $index => $step): ?>
                        <div class="flex gap-3">
                            <div class="step-number"><?php echo $index + 1; ?></div>
                            <div class="flex-1">
                                <h3 class="font-semibold mb-1"><?php echo $step['title']; ?></h3>
                                <p class="text-gray-400 text-sm leading-relaxed"><?php echo $step['description']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Online Payment Section -->
            <div class="card mb-6">
                <div class="p-6">
                    <header class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <line x1="2" x2="22" y1="10" y2="10"/>
                            </svg>
                            <div>
                                <h2 class="text-xl font-bold"><?php echo $lang['method_online']; ?></h2>
                                <p class="text-sm text-gray-400"><?php echo $lang['method_online_subtitle']; ?></p>
                            </div>
                        </div>
                    </header>

                    <div class="space-y-4">
                        <?php foreach ($lang['online_steps'] as $index => $step): ?>
                        <div class="flex gap-3">
                            <div class="step-number"><?php echo $index + 1; ?></div>
                            <div class="flex-1">
                                <h3 class="font-semibold mb-1"><?php echo $step['title']; ?></h3>
                                <p class="text-gray-400 text-sm leading-relaxed"><?php echo $step['description']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="card mb-6">
                <div class="p-6">
                    <header class="mb-4">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 16v-4"/>
                                <path d="M12 8h.01"/>
                            </svg>
                            <h2 class="text-xl font-bold"><?php echo $lang['tips_title']; ?></h2>
                        </div>
                    </header>
                    <ul class="space-y-2">
                        <?php foreach ($lang['tips'] as $tip): ?>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400 mt-1">•</span>
                            <span class="text-gray-400 text-sm"><?php echo $tip; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Questions Section -->
            <div class="text-center py-6">
                <h3 class="text-lg font-bold mb-2"><?php echo $lang['questions_title']; ?></h3>
                <p class="text-gray-400 text-sm"><?php echo $lang['questions_text']; ?></p>
            </div>
        </div>
    </div>
</body>

</html>

<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$languages = [
    'en' => [
        'flag' => '🇬🇧',
        'name' => 'Englisch',
        'error_loading_shows' => 'Error loading shows',
        'try_again' => 'Please try again later or contact support.',
        'buy_tickets' => 'Buy Tickets',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'number_of_tickets' => 'Number of Tickets',
        'payment_method' => 'Payment Method',
        'cash_payment' => 'Cash payment',
        'online_payment' => 'Online payment',
        'book_tickets' => 'Book Tickets',
        'need_help' => 'Do you need help?',
        'sold_out' => 'Sold out',
        'tickets_left' => 'Only {count} tickets left!',
        'tickets_available' => '{available} of {total} tickets available.',
        'store_lock_title' => 'Store locked',
        'store_lock_message' => 'The ticket shop of {name} is currently closed. Maby there are currently no tickets to sell? Please check back later or contact the operator for more information.',
    ],
    'de' => [
        'flag' => '🇩🇪',
        'name' => 'Deutsch',
        'error_loading_shows' => 'Fehler beim Laden der Shows',
        'try_again' => 'Bitte versuche es später erneut oder kontaktiere den Support.',
        'buy_tickets' => 'Tickets kaufen',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'E-Mail',
        'number_of_tickets' => 'Anzahl der Tickets',
        'payment_method' => 'Zahlungsmethode',
        'cash_payment' => 'Barzahlung',
        'online_payment' => 'Online-Zahlung',
        'book_tickets' => 'Tickets buchen',
        'need_help' => 'Brauchen Sie Hilfe?',
        'sold_out' => 'Ausverkauft',
        'tickets_left' => 'Nur noch {count} Plätze frei!',
        'tickets_available' => '{available} von {total} Plätzen verfügbar.',
        'store_lock_title' => 'Shop gesperrt',
        'store_lock_message' => 'Der Ticketshop von {name} ist derzeit geschlossen. Möglicherweise sind derzeit keine Tickets zum Verkauf verfügbar? Bitte schauen Sie später wieder vorbei oder kontaktieren Sie den Betreiber für weitere Informationen.',
    ],
];

$current_language = $_SESSION['language'] ?? 'en';
$shows = getShows();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shows['orga_name']); ?> - Tickets</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo API_BASE_URL; ?>/api/image/get/logo.png?t=<?php echo time(); ?>">
    <?php if ($shows !== null): ?>
        <script
            src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=EUR&locale=<?php echo $current_language; ?>_AT"></script>
        <script>
            let availablePaymentMethods = 'both';


            async function loadPaymentMethods() {
                try {
                    const response = await fetch('api-proxy.php?endpoint=payment_methods');
                    const data = await response.json();
                    if (data.status === 'success') {
                        availablePaymentMethods = data.payment_methods;
                        console.log('Payment methods loaded:', availablePaymentMethods);
                    }
                } catch (error) {
                    console.error('Error loading payment methods:', error);
                }
            }


            document.addEventListener('DOMContentLoaded', loadPaymentMethods);
        </script>
    <?php endif; ?>
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

        .bar {
            background: #e4e5e4;
        }

        .bar-dark {
            background-color: rgb(46, 46, 46);
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        .orga-name {
            color: var(--text-color);
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

        .language-selector .flag {
            margin-right: 5px;
        }

        @media (max-width: 640px) {
            .language-selector {
                bottom: 10px;
                right: 10px;
            }

            .language-selector select {
                padding: 6px;
                font-size: 0.875rem;
            }
        }

        .help-question {
            font-size: larger;
            margin-top: -10px;
        }

        div[class*="paypal-checkout-sandbox"] {
            z-index: 999999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }

        #bookingModal::backdrop {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        @media (max-width: 640px) {
            #bookingModal {
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

            #bookingModal>div {
                max-height: calc(80vh - 4rem);
                overflow-y: auto;
                width: 100%;
            }

            #bookingModal>button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="language-selector">
        <form method="post" id="langForm">
            <select name="language" onchange="this.form.submit()">
                <?php foreach ($languages as $code => $lang): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($current_language == $code) ? 'selected' : ''; ?>>
                        <span class="flag"><?php echo $lang['flag']; ?></span>
                        <?php echo $lang['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <dialog id="error-dialog" class="dialog" aria-labelledby="error-dialog-title"
        aria-describedby="error-dialog-description">
        <div>
            <header>
                <h2 id="error-dialog-title" class="text-2xl inline-flex gap-x-2"><svg xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-circle-alert-icon lucide-circle-alert">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg> Error</h2>
                <p id="error-dialog-description"></p>
            </header>
            <footer>
                <button class="btn-primary" onclick="document.getElementById('error-dialog').close()">Okay</button>
            </footer>
        </div>
    </dialog>
    <dialog id="message-dialog" class="dialog" aria-labelledby="message-dialog-title"
        aria-describedby="message-dialog-description">
        <div>
            <header>
                <h2 id="message-dialog-title" class="text-2xl inline-flex gap-x-2"></h2>
                <p id="message-dialog-description" class="text-white"></p>
            </header>
            <footer>
                <button class="btn-primary" onclick="document.getElementById('message-dialog').close()">Okay</button>
            </footer>
        </div>
    </dialog>
    <?php if (isset($_SESSION['error']) || isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dialog = document.getElementById('message-dialog');
                const title = dialog.querySelector('#message-dialog-title');
                const desc = dialog.querySelector('#message-dialog-description');

                <?php if (isset($_SESSION['error'])): ?>
                    title.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg> Error';
                    desc.textContent = <?php echo json_encode($_SESSION['error']); ?>;
                    <?php unset($_SESSION['error']); ?>
                <?php elseif (isset($_SESSION['success'])): ?>
                    title.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ticket-check-icon lucide-ticket-check"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="m9 12 2 2 4-4"/></svg> Success';
                    desc.textContent = <?php echo json_encode($_SESSION['success']); ?>;
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                dialog.showModal();
            });
        </script>
    <?php endif; ?>
    <dialog id="bookingModal" class="dialog w-full sm:max-w-[425px]" aria-labelledby="demo-dialog-edit-profile-title"
        onclick="if (event.target === this) this.close()">
        <div class="max-h-[80vh] overflow-y-auto p-6">
            <header class="mb-4">
                <h2 id="demo-dialog-edit-profile-title" class="text-xl font-bold">
                    <?php echo $languages[$current_language]['buy_tickets']; ?>
                </h2>
                <p class="animate-pulse demo-dialog-edit-profile-description text-sm mt-1">
                    <i class="fa-solid fa-circle-question"></i>
                    <a href="./help/buy_ticket.php" class="text-gray-200" target="_blank">
                        <span><?php echo $languages[$current_language]['need_help']; ?></span>
                    </a>
                </p>
            </header>
            <section>
                <form class="form grid gap-4" id="bookingForm" action="buy.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="valid_date" id="validDate">
                    <input type="hidden" name="price" id="ticketPrice">
                    <div class="grid gap-3">
                        <label for="first_name"><?php echo $languages[$current_language]['first_name']; ?></label>
                        <input type="text" name="first_name" placeholder="Max" required autofocus>
                        <input type="hidden" name="payment_method" id="paymentMethodInput" value="">
                    </div>
                    <div class="grid gap-3">
                        <label for="last_name"><?php echo $languages[$current_language]['last_name']; ?></label>
                        <input type="text" name="last_name" placeholder="Mustermann" required>
                    </div>
                    <div class="grid gap-3">
                        <label for="email"><?php echo $languages[$current_language]['email']; ?></label>
                        <input type="email" name="email" placeholder="max@mustermann.de" required>
                    </div>
                    <div class="grid gap-3">
                        <label for="tickets"><?php echo $languages[$current_language]['number_of_tickets']; ?></label>
                        <select name="tickets" required>
                            <?php for ($i = 1; $i <= 10; $i++) { ?>
                                <option value="<?php echo $i; ?>">
                                    <?php echo $i . ' Ticket' . (($i > 1 ? 's' : '')); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div id="nameFieldsContainer" class="space-y-2"></div>
                    <div class="grid gap-3">
                        <label><?php echo $languages[$current_language]['payment_method']; ?></label>
                        <div id="paymentMethodSelection" class="flex gap-3" style="min-height: 56px;">
                            <button type="button" id="cashButton" class="btn-secondary flex-1 payment-method-btn"
                                onclick="selectCashPayment()" data-method="cash"
                                aria-label="<?php echo $languages[$current_language]['cash_payment']; ?>"
                                style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; min-height: 56px; padding: 0 1rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-coins-icon lucide-coins">
                                    <circle cx="8" cy="8" r="6" />
                                    <path d="M18.09 10.37A6 6 0 1 1 10.34 18" />
                                    <path d="M7 6h1v4" />
                                    <path d="m16.71 13.88.7.71-2.82 2.82" />
                                </svg>
                                <span><?php echo $languages[$current_language]['cash_payment']; ?></span>
                            </button>
                            <button type="button" id="paypalButton" class="btn-primary flex-1 payment-method-btn"
                                onclick="selectPayPalPayment()" data-method="online"
                                aria-label="<?php echo $languages[$current_language]['online_payment']; ?>"
                                style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; min-height: 56px; padding: 0 1rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-credit-card-icon lucide-credit-card">
                                    <rect width="20" height="14" x="2" y="5" rx="2" />
                                    <line x1="2" x2="22" y1="10" y2="10" />
                                </svg>
                                <span><?php echo $languages[$current_language]['online_payment']; ?></span>
                            </button>
                        </div>
                        <button type="submit" id="cashConfirmButton" class="btn-primary w-full mt-2 hidden"
                            aria-label="<?php echo $languages[$current_language]['book_tickets']; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check">
                                <path
                                    d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg> <?php echo $languages[$current_language]['book_tickets']; ?>
                        </button>
                        <div id="paypalButtons" class="mt-4 hidden" style="margin: 8px; border-radius: 8px;"></div>
                    </div>
                </form>
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
        <button type="button" aria-label="Close dialog" onclick="this.closest('dialog').close()"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-x">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </dialog>
    <?php if ($shows === null): ?>
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-red-900/50 backdrop-blur-md border border-red-700 rounded-lg p-8 max-w-md w-full text-center">
                <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-2xl font-bold mb-4"><?php echo $languages[$current_language]['error_loading_shows']; ?></h2>
                <p class="text-gray-300"><?php echo $languages[$current_language]['try_again']; ?></p>
                <button onclick="location.reload()"
                    class="mt-6 px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Try again
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php if ($shows["store_lock"] === true): ?>
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="bg-red-900/50 backdrop-blur-md border border-red-700 rounded-lg p-8 max-w-md w-full text-center"
                    style="background-color: rgba(234, 51, 51, 0.2)">
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; "><svg
                            xmlns="http://www.w3.org/2000/svg" width="85" height="85" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-lock-icon lucide-lock">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg></div>
                    <h2 class="text-2xl font-bold mb-4"><br><?php echo $languages[$current_language]['store_lock_title']; ?>
                    </h2>
                    <p class="text-gray-300">
                        <?php echo str_replace('{name}', $shows['orga_name'], $languages[$current_language]['store_lock_message']); ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="animate-fade-in">
                <div class="relative w-full min-h-[40vh] max-h-[50vh] flex items-center justify-center mb-8 px-4">
                    <div id="bannerBackground" class="absolute inset-0 bg-cover bg-center transition-transform duration-700">
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {

                            const timestamp = new Date().getTime();
                            document.getElementById('bannerBackground').style.backgroundImage = `url('<?php echo API_BASE_URL; ?>/api/image/get/banner.png?t=${timestamp}')`;
                        });
                    </script>
                    <div class="absolute inset-0 bg-black/60"></div>
                    <div class="relative z-10 text-center max-w-2xl w-full py-6 md:py-10 px-3 rounded-xl">
                        <h1 class="orga-name text-3xl md:text-5xl font-bold mb-2 animate-fade-in-up">
                            <?php echo htmlspecialchars($shows['orga_name']); ?>
                        </h1>
                        <h2 class="show-name text-xl md:text-3xl font-semibold mb-1 animate-fade-in-up">
                            <span class="px-2 py-1 rounded-md">
                                <?php echo htmlspecialchars($shows['title']); ?>
                            </span>
                        </h2>
                        <?php if (!empty(trim($shows['subtitle']))): ?>
                            <h3 class="text-lg md:text-xl mt-2 animate-fade-in-up opacity-90">
                                <?php echo htmlspecialchars($shows['subtitle']); ?>
                            </h3>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="container mx-auto px-4 py-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($shows['dates'] as $id => $show): ?>
                            <div class="card show-hover transform transition-all duration-300 hover:scale-105">
                                <div class="p-6">
                                    <header class="mb-8">
                                        <div class="flex justify-between items-center mb-6">
                                            <span class="font-bold text-3xl"
                                                style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="lucide lucide-calendar-days-icon lucide-calendar-days">
                                                    <path d="M8 2v4" />
                                                    <path d="M16 2v4" />
                                                    <rect width="18" height="18" x="3" y="4" rx="2" />
                                                    <path d="M3 10h18" />
                                                    <path d="M8 14h.01" />
                                                    <path d="M12 14h.01" />
                                                    <path d="M16 14h.01" />
                                                    <path d="M8 18h.01" />
                                                    <path d="M12 18h.01" />
                                                    <path d="M16 18h.01" />
                                                </svg>
                                                <?php $date = new DateTime($show['date']);
                                                echo $date->format('d.m.Y'); ?>
                                            </span>
                                            <?php if ($show['tickets_available'] <= 20 && $show['tickets_available'] > 0): ?>
                                                <span class="font-bold badge-primary animate-pulse">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="lucide lucide-triangle-alert-icon lucide-triangle-alert">
                                                        <path
                                                            d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                                                        <path d="M12 9v4" />
                                                        <path d="M12 17h.01" />
                                                    </svg>
                                                    <?php echo str_replace('{count}', $show['tickets_available'], $languages[$current_language]['tickets_left']); ?>
                                                </span>
                                            <?php elseif ($show['tickets_available'] == 0): ?>
                                                <span class="badge-destructive font-bold animate-pulse"><svg
                                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" class="lucide lucide-ticket-x-icon lucide-ticket-x">
                                                        <path
                                                            d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                                        <path d="m9.5 14.5 5-5" />
                                                        <path d="m9.5 9.5 5 5" />
                                                    </svg> <?php echo $languages[$current_language]['sold_out']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </header>
                                    <section>
                                        <div class="grid gap-3">
                                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" class="lucide lucide-euro-icon lucide-euro">
                                                    <path d="M4 10h12" />
                                                    <path d="M4 14h9" />
                                                    <path
                                                        d="M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" />
                                                </svg>
                                                <span><?php echo htmlspecialchars($show['price']); ?></span>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" class="lucide lucide-clock2-icon lucide-clock-2">
                                                    <path d="M12 6v6l4-2" />
                                                    <circle cx="12" cy="12" r="10" />
                                                </svg>
                                                <?php echo htmlspecialchars($show['time']); ?>
                                            </span>
                                            <div class="relative pt-1 mb-4">
                                                <div class="flex mb-2 items-center justify-between">
                                                    <div class="text-right">
                                                        <span class="text-xs font-semibold inline-block bar-text">
                                                            <?php echo str_replace(['{available}', '{total}'], [htmlspecialchars($show['tickets_available']), htmlspecialchars($show['tickets'])], $languages[$current_language]['tickets_available']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="overflow-hidden h-2 text-xs flex rounded bar-dark">
                                                    <?php
                                                    $occupiedtickets = $show['tickets'] - $show['tickets_available'];
                                                    $percentage = ($occupiedtickets / $show['tickets']) * 100;
                                                    echo "<div style='width: {$percentage}%' class='shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bar'></div>";
                                                    ?>
                                                </div>
                                            </div>
                                            <?php if ($show['tickets_available'] > 0): ?>
                                                <button
                                                    onclick="showBookingForm('<?php echo $id; ?>', '<?php echo $show['date']; ?>', '<?php echo $show['price']; ?>', '<?php echo $show['tickets_available']; ?>')"
                                                    class="btn-primary"
                                                    aria-label="<?php echo $languages[$current_language]['buy_tickets']; ?> - <?php $date = new DateTime($show['date']); echo $date->format('d.m.Y'); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" class="lucide lucide-tickets-icon lucide-tickets" aria-hidden="true">
                                                        <path d="m3.173 8.18 11-5a2 2 0 0 1 2.647.993L18.56 8" />
                                                        <path d="M6 10V8" />
                                                        <path d="M6 14v1" />
                                                        <path d="M6 19v2" />
                                                        <rect x="2" y="8" width="20" height="13" rx="2" />
                                                    </svg>
                                                    <?php echo $languages[$current_language]['buy_tickets']; ?>
                                                </button>
                                            <?php elseif ($show['tickets_available'] == 0): ?>
                                                <button disabled class="btn-destructive cursor-not-allowed"
                                                    aria-label="<?php echo $languages[$current_language]['sold_out']; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" class="lucide lucide-ticket-x-icon lucide-ticket-x">
                                                        <path
                                                            d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                                        <path d="m9.5 14.5 5-5" />
                                                        <path d="m9.5 9.5 5 5" />
                                                    </svg> <?php echo $languages[$current_language]['sold_out']; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <script>
                let lastFocusedElement = null;

                document.addEventListener('DOMContentLoaded', initPaymentMethods);

                function closeModal() {
                    const modal = document.getElementById('bookingModal');
                    document.getElementById('bookingModal').close();
                    modal.classList.remove('modal-exit');
                    document.body.style.overflow = 'auto';

                    // Reset form state
                    document.getElementById('paymentMethodSelection').classList.remove('hidden');
                    document.getElementById('cashConfirmButton').classList.add('hidden');
                    document.getElementById('paypalButtons').classList.add('hidden');
                    document.getElementById('paymentMethodInput').value = '';

                    if (window.paypalButtons && typeof window.paypalButtons.close === 'function') {
                        window.paypalButtons.close();
                    }
                    document.getElementById('paypalButtons').innerHTML = '';

                    // Restore focus to the element that opened the modal
                    if (lastFocusedElement) {
                        lastFocusedElement.focus();
                    }
                }


                function selectCashPayment() {


                    document.getElementById('paymentMethodInput').value = 'bar';
                    document.getElementById('paymentMethodSelection').classList.add('hidden');
                    document.getElementById('cashConfirmButton').classList.remove('hidden');


                    setTimeout(() => {
                        document.getElementById('cashConfirmButton').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }

                function selectPayPalPayment() {


                    document.getElementById('paymentMethodInput').value = 'paypal';
                    document.getElementById('paymentMethodSelection').classList.add('hidden');
                    document.getElementById('paypalButtons').classList.remove('hidden');

                    if (window.paypalButtons && typeof window.paypalButtons.close === 'function') {
                        window.paypalButtons.close();
                    }

                    const price = parseFloat(document.getElementById('ticketPrice').value);
                    createPayPalButtons(price);

                    const bookingModal = document.getElementById('bookingModal');
                    bookingModal.style.zIndex = '1000';

                    document.body.style.overflow = 'auto';


                    setTimeout(() => {
                        document.getElementById('paypalButtons').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }

                document.getElementById('cashConfirmButton').addEventListener('click', function (e) {
                    e.preventDefault();

                    if (!validateForm()) {
                        return;
                    }

                    this.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="status" aria-label="Loading" class="animate-spin">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
        </svg>
        Processing
    `;
                    this.disabled = true;

                    document.getElementById('bookingForm').submit();
                });

                function validateForm() {
                    const firstName = document.querySelector('input[name="first_name"]').value.trim();
                    const lastName = document.querySelector('input[name="last_name"]').value.trim();
                    const email = document.querySelector('input[name="email"]').value.trim();
                    const tickets = document.querySelector('select[name="tickets"]').value;
                    const additionalNameInputs = document.querySelectorAll('input[name="add_people[]"]');

                    const currentLang = '<?php echo $current_language; ?>';
                    const messages = {
                        en: {
                            firstName: 'Please enter your first name.',
                            lastName: 'Please enter your last name.',
                            emailRequired: 'Please enter your email.',
                            emailInvalid: 'Please enter a valid email address.',
                            tickets: 'Please select the number of tickets.',
                            missingAdditionalName: 'Please enter a name for all additional tickets.'
                        },
                        de: {
                            firstName: 'Bitte geben Sie Ihren Vornamen ein.',
                            lastName: 'Bitte geben Sie Ihren Nachnamen ein.',
                            emailRequired: 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
                            emailInvalid: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
                            tickets: 'Bitte wählen Sie die Anzahl der Tickets aus.',
                            missingAdditionalName: 'Bitte geben Sie für alle zusätzlichen Tickets einen Namen an.'
                        }
                    };

                    const msg = messages[currentLang] || messages.en;
                    let errors = [];

                    if (!firstName) errors.push(msg.firstName);
                    if (!lastName) errors.push(msg.lastName);
                    if (!email) {
                        errors.push(msg.emailRequired);
                    } else {
                        const emailRegex = /^[-a-z0-9!#$%&'*+/=?^_`{|}~]+(?:\.[-a-z0-9!#$%&'*+/=?^_`{|}~]+)*@(?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)+[a-z0-9](?:[-a-z0-9]*[a-z0-9])?/;
                        if (!emailRegex.test(email)) {
                            errors.push(msg.emailInvalid);
                        }
                    }
                    if (!tickets || tickets < 1) errors.push(msg.tickets);

                    for (const input of additionalNameInputs) {
                        if (!input.value.trim()) {
                            errors.push(msg.missingAdditionalName);
                            break;
                        }
                    }

                    if (errors.length > 0) {
                        showErrorDialog(errors.join('\n'));
                        return false;
                    }
                    return true;
                }

                function showError(message) {
                    const errorDiv = document.getElementById('modalError');
                    errorDiv.textContent = message;
                    errorDiv.classList.remove('hidden');


                    setTimeout(() => {
                        errorDiv.classList.add('hidden');
                    }, 5000);
                }

                document.getElementById('paymentMethod').addEventListener('change', function (e) {
                    const paypalButtons = document.getElementById('paypalButtons');
                    const submitButton = document.getElementById('submitButton');

                    if (e.target.value === 'paypal') {
                        paypalButtons.classList.remove('hidden');
                        submitButton.classList.add('hidden');
                    } else {
                        paypalButtons.classList.add('hidden');
                        submitButton.classList.remove('hidden');
                    }
                });

                function initPayPalButton(price) {
                    const ticketsSelect = document.querySelector('select[name="tickets"]');
                    let currentPrice = parseFloat(price);

                    ticketsSelect.addEventListener('change', function () {
                        currentPrice = parseFloat(price) * parseInt(this.value);
                        if (window.paypalButtons && typeof window.paypalButtons.close === 'function') {
                            window.paypalButtons.close();
                        }
                        createPayPalButtons(currentPrice);
                    });

                    createPayPalButtons(currentPrice);
                }

                function createPayPalButtons(price) {

                    if (window.paypalButtons && typeof window.paypalButtons.close === 'function') {
                        window.paypalButtons.close();
                    }

                    window.paypalButtons = paypal.Buttons({
                        style: {
                            layout: 'vertical',
                            backgroundColor: '#0a0b0b',
                            color: 'black',
                            shape: 'rect',
                            label: 'pay',
                        },
                        createOrder: function (data, actions) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: {
                                        currency_code: "EUR",
                                        value: price.toString()
                                    }
                                }]
                            });
                        },
                        onApprove: function (data, actions) {
                            return actions.order.capture().then(function (details) {
                                if (details.status === 'COMPLETED') {
                                    document.getElementById('bookingForm').submit();
                                } else {
                                    showError('Zahlung fehlgeschlagen. Bitte versuchen Sie es erneut.');
                                }
                            });
                        },
                        onError: function (err) {
                            showError('PayPal error: ' + err.message);
                        }
                    });

                    window.paypalButtons.render('#paypalButtons');
                }

                document.getElementById('bookingForm').addEventListener('submit', function (e) {
                    const firstName = this.elements['first_name'].value.trim();
                    const lastName = this.elements['last_name'].value.trim();
                    const email = this.elements['email'].value.trim();
                    const tickets = this.elements['tickets'].value;

                    let errors = [];

                    if (!firstName) {
                        errors.push('Please enter your first name.');
                    }

                    if (!lastName) {
                        errors.push('Please enter your last name.');
                    }

                    if (!email) {
                        errors.push('Please enter your email.');
                    }

                    if (!tickets || tickets < 1) {
                        errors.push('Please select the number of tickets.');
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        showError(errors.join('\n'));
                        return false;
                    }
                });


                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });


                document.getElementById('bookingModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });

                function updateNameFields() {
                    const ticketsSelect = document.querySelector('select[name="tickets"]');
                    const nameFieldsContainer = document.getElementById('nameFieldsContainer');

                    if (!ticketsSelect || !nameFieldsContainer) return;

                    const numberOfTickets = parseInt(ticketsSelect.value, 10) || 1;


                    nameFieldsContainer.replaceChildren();


                    if (numberOfTickets > 1) {
                        for (let i = 2; i <= numberOfTickets; i++) {
                            const fieldGroup = document.createElement('div');
                            fieldGroup.className = 'space-y-2';

                            const label = document.createElement('label');
                            label.className = 'block text-sm font-medium text-gray-200';

                            label.textContent = `Name for Ticket ${i}`;

                            const input = document.createElement('input');
                            input.type = 'text';
                            input.name = 'add_people[]';
                            input.placeholder = 'Max Mustermann';
                            input.required = true;
                            input.className = 'w-full p-4 rounded-lg text-white inputs transition-all';

                            fieldGroup.appendChild(label);
                            fieldGroup.appendChild(input);
                            nameFieldsContainer.appendChild(fieldGroup);
                        }
                    }
                }


                function initTicketSelector() {
                    const ticketsSelect = document.querySelector('select[name="tickets"]');
                    if (!ticketsSelect) return;


                    ticketsSelect.removeEventListener('change', updateNameFields);
                    ticketsSelect.addEventListener('change', updateNameFields);


                    updateNameFields();
                }


                document.addEventListener('DOMContentLoaded', initTicketSelector);


                function updatePaymentMethodButtons() {
                    const paymentMethodButtons = document.querySelectorAll('.payment-method-btn');
                    const container = document.getElementById('paymentMethodSelection');

                    paymentMethodButtons.forEach(button => {
                        const method = button.getAttribute('data-method');


                        button.style.display = 'flex';


                        if (availablePaymentMethods === 'cash' && method === 'online') {
                            button.style.display = 'none';
                        } else if (availablePaymentMethods === 'online' && method === 'cash') {
                            button.style.display = 'none';
                        }
                    });


                    const visibleButtons = Array.from(paymentMethodButtons).filter(btn => btn.style.display !== 'none');
                    if (visibleButtons.length === 1) {
                        const method = visibleButtons[0].getAttribute('data-method');
                        if (method === 'cash') {
                            selectCashPayment();
                        } else if (method === 'online') {
                            selectPayPalPayment();
                        }
                    }


                    if (visibleButtons.length === 1) {
                        container.style.minHeight = 'auto';
                        visibleButtons[0].style.width = '100%';
                    } else {
                        container.style.minHeight = '56px';
                        paymentMethodButtons.forEach(btn => {
                            btn.style.width = '';
                        });
                    }
                }


                async function initPaymentMethods() {
                    await loadPaymentMethods();
                    updatePaymentMethodButtons();
                }


                function showBookingForm(showId, date, price, ticketsAvailable) {
                    lastFocusedElement = document.activeElement;
                    const modal = document.getElementById('bookingModal');
                    document.getElementById('bookingModal').showModal();

                    document.getElementById('paymentMethodSelection').classList.remove('hidden');
                    document.getElementById('cashConfirmButton').classList.add('hidden');
                    document.getElementById('paypalButtons').classList.add('hidden');

                    if (window.paypalButtons && typeof window.paypalButtons.close === 'function') {
                        window.paypalButtons.close();
                    }

                    document.getElementById('paypalButtons').innerHTML = '';

                    document.getElementById('validDate').value = date;
                    document.getElementById('ticketPrice').value = price;
                    document.body.style.overflow = 'hidden';

                    const ticketsSelect = document.querySelector('select[name="tickets"]');
                    ticketsSelect.innerHTML = '';

                    console.log("Available tickets:", ticketsAvailable);

                    const maxtickets = Math.min(ticketsAvailable, 10);
                    console.log("Max tickets to display:", maxtickets);

                    for (let i = 1; i <= maxtickets; i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = `${i} Ticket${i > 1 ? 's' : ''}`;
                        ticketsSelect.appendChild(option);
                    }

                    initPayPalButton(price);
                    initTicketSelector();
                    updatePaymentMethodButtons();
                }


                async function processPayment(paymentDetails) {

                    const available = await checkAvailability(paymentDetails.showId, paymentDetails.ticketCount);
                    if (!available) {
                        throw new Error("Nicht genügend Plätze verfügbar.");
                    }


                    initPayPalButton(paymentDetails.price);
                }

                async function checkAvailability(showId, ticketCount) {

                    const response = await fetch(`/api/ticket/available_tickets/${showId}`);
                    const data = await response.json();

                    if (data.status === "error") {
                        throw new Error(data.message);
                    }


                    return data.available_tickets >= ticketCount;
                }

                function showErrorDialog(message) {
                    const dialog = document.getElementById('error-dialog');
                    const description = dialog.querySelector('#error-dialog-description');


                    description.innerHTML = '';


                    const lines = message.split('\n');

                    lines.forEach((line, index) => {

                        description.appendChild(document.createTextNode(line));


                        if (index < lines.length - 1) {
                            description.appendChild(document.createElement('br'));
                        }
                    });

                    return new Promise((resolve) => {
                        dialog.addEventListener('close', () => resolve(false), { once: true });
                        dialog.showModal();
                    });
                }

            </script>

        <?php endif; ?>
    <?php endif; ?>
</body>

</html>
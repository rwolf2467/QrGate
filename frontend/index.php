<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: index.php");
    exit();
}

$languages = [
    'en' => [
        'flag' => '🇬🇧',
        'name' => 'Englisch',
        'error_loading_shows' => "We can't load events right now",
        'try_again' => 'Please try again shortly. If this keeps happening, contact the organizer.',
        'try_again_button' => 'Try again',
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
        'booking_notice_title' => 'Binding booking',
        'binding_notice' => 'Your booking is binding. By confirming, you commit to attending on the selected date.',
        'storno_card' => 'Paid by card: in case of cancellation, refunds are handled by the organizer.',
        'storno_cash' => 'Cash payment: you pay on site — nothing is charged now, but the booking is still binding.',
        'storno_contact' => 'Questions or cancellation? Please contact {contact}.',
        'the_organizer' => 'the organizer',
        'consent_label' => 'I confirm that this booking is binding and that I will attend on the selected date.',
        'consent_required' => 'Please confirm the booking notice to continue.',
        'step_of' => 'Step {n} of 4',
        'step1_title' => 'Your details',
        'step2_title' => 'Tickets',
        'step3_title' => 'Payment method',
        'step4_title' => 'Confirm &amp; pay',
        'back' => 'Back',
        'next' => 'Next',
        'pay_now' => 'Pay now',
        'summary' => 'Summary',
        'total' => 'Total',
        'choose_payment' => 'How would you like to pay?',
    ],
    'de' => [
        'flag' => '🇩🇪',
        'name' => 'Deutsch',
        'error_loading_shows' => 'Veranstaltungen können gerade nicht geladen werden',
        'try_again' => 'Bitte versuchen Sie es in Kürze erneut. Falls das Problem weiterhin besteht, kontaktieren Sie den Veranstalter.',
        'try_again_button' => 'Erneut versuchen',
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
        'booking_notice_title' => 'Verbindliche Buchung',
        'binding_notice' => 'Ihre Buchung ist verbindlich. Mit der Bestätigung verpflichten Sie sich, am gewählten Termin zu erscheinen.',
        'storno_card' => 'Zahlung per Karte: Bei Storno werden Rückerstattungen vom Veranstalter abgewickelt.',
        'storno_cash' => 'Barzahlung: Sie zahlen vor Ort — es wird jetzt nichts abgebucht, die Buchung ist dennoch verbindlich.',
        'storno_contact' => 'Fragen oder Storno? Bitte kontaktieren Sie {contact}.',
        'the_organizer' => 'den Veranstalter',
        'consent_label' => 'Ich bestätige, dass diese Buchung verbindlich ist und ich am gewählten Termin erscheine.',
        'consent_required' => 'Bitte bestätigen Sie den Buchungshinweis, um fortzufahren.',
        'step_of' => 'Schritt {n} von 4',
        'step1_title' => 'Ihre Daten',
        'step2_title' => 'Tickets',
        'step3_title' => 'Zahlungsart',
        'step4_title' => 'Bestätigen &amp; bezahlen',
        'back' => 'Zurück',
        'next' => 'Weiter',
        'pay_now' => 'Jetzt bezahlen',
        'summary' => 'Übersicht',
        'total' => 'Gesamt',
        'choose_payment' => 'Wie möchten Sie bezahlen?',
    ],
];

$current_language = $_SESSION['language'] ?? 'en';
if (!isset($languages[$current_language])) {
    $current_language = 'en';
}
$shows = getShows();

$pageTitle = htmlspecialchars($shows['orga_name'] ?? 'QrGate') . ' - Tickets';
$assetBase = '';
$extraHead = '';
if ($shows !== null) {
    $extraHead = <<<HTML
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            let availablePaymentMethods = 'both';
            let stripePublishableKey = '';

            async function loadPaymentMethods() {
                try {
                    const response = await fetch('api-proxy.php?endpoint=payment_methods');
                    const data = await response.json();
                    if (data.status === 'success') {
                        availablePaymentMethods = data.payment_methods;
                    }
                } catch (error) {
                    console.error('Error loading payment methods:', error);
                }
            }

            async function loadStripeKey() {
                try {
                    const response = await fetch('api-proxy.php?endpoint=stripe_pub_key');
                    const data = await response.json();
                    if (data.publishable_key) {
                        stripePublishableKey = data.publishable_key;
                    }
                } catch (error) {
                    console.error('Error loading Stripe key:', error);
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                loadPaymentMethods();
                loadStripeKey();
            });
        </script>
HTML;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">

<?php include __DIR__ . '/partials/head.php'; ?>
<body>
    <style>
        .orga-name {
            color: var(--avo-text);
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
                <p id="message-dialog-description" class="avo-text"></p>
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
    <style>
        .wizard-dot { flex: 1; height: 4px; border-radius: 999px; background: var(--avo-border); transition: background .2s ease; }
        .wizard-dot.active { background: var(--avo-primary); }
        .wizard-dot.done { background: color-mix(in oklab, var(--avo-primary) 55%, transparent); }
    </style>
    <dialog id="bookingModal" class="dialog w-full sm:max-w-[425px]" aria-labelledby="demo-dialog-edit-profile-title"
        onclick="if (event.target === this) this.close()">
        <div class="max-h-[80vh] overflow-y-auto p-6">
            <header class="mb-4">
                <h2 id="demo-dialog-edit-profile-title" class="text-xl font-bold">
                    <?php echo $languages[$current_language]['buy_tickets']; ?>
                </h2>
                <p class="animate-pulse demo-dialog-edit-profile-description text-sm mt-1">
                    <i class="fa-solid fa-circle-question"></i>
                    <a href="./help/buy_ticket.php" class="avo-link" target="_blank">
                        <span><?php echo $languages[$current_language]['need_help']; ?></span>
                    </a>
                </p>
            </header>
            <section>
                <form class="form grid gap-4" id="bookingForm" action="buy.php" method="POST">
                    <?php echo csrfField(); ?>
                    <!-- Honeypot: hidden from real users; bots that fill it are rejected. -->
                    <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="valid_date" id="validDate">
                    <input type="hidden" name="price" id="ticketPrice">
                    <input type="hidden" name="payment_intent_id" id="paymentIntentId">
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="">
                    <?php
                    $L = $languages[$current_language];
                    $contactEmail = $shows['contact_email'] ?? '';
                    $contactDisplay = $contactEmail !== ''
                        ? '<a href="mailto:' . htmlspecialchars($contactEmail) . '" class="avo-link">' . htmlspecialchars($contactEmail) . '</a>'
                        : htmlspecialchars($L['the_organizer']);
                    $stornoContact = str_replace('{contact}', $contactDisplay, htmlspecialchars($L['storno_contact']));
                    ?>

                    <!-- Step indicator -->
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <span id="wizardStepTitle" class="font-semibold text-sm"></span>
                            <span id="wizardStepCount" class="text-xs" style="color:var(--avo-text-muted);"></span>
                        </div>
                        <div class="flex gap-1.5">
                            <span class="wizard-dot" data-dot="1"></span>
                            <span class="wizard-dot" data-dot="2"></span>
                            <span class="wizard-dot" data-dot="3"></span>
                            <span class="wizard-dot" data-dot="4"></span>
                        </div>
                    </div>

                    <!-- STEP 1 — personal details -->
                    <div class="wizard-step grid gap-4" data-step="1">
                        <div class="grid gap-2">
                            <label for="first_name"><?php echo $L['first_name']; ?></label>
                            <input type="text" name="first_name" placeholder="Max" required autofocus>
                        </div>
                        <div class="grid gap-2">
                            <label for="last_name"><?php echo $L['last_name']; ?></label>
                            <input type="text" name="last_name" placeholder="Mustermann" required>
                        </div>
                        <div class="grid gap-2">
                            <label for="email"><?php echo $L['email']; ?></label>
                            <input type="email" name="email" placeholder="max@mustermann.de" required>
                        </div>
                    </div>

                    <!-- STEP 2 — tickets -->
                    <div class="wizard-step hidden grid gap-4" data-step="2">
                        <div class="grid gap-2">
                            <label for="tickets"><?php echo $L['number_of_tickets']; ?></label>
                            <select name="tickets" required>
                                <?php for ($i = 1; $i <= 10; $i++) { ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo $i . ' Ticket' . (($i > 1 ? 's' : '')); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div id="nameFieldsContainer" class="grid gap-3"></div>
                    </div>

                    <!-- STEP 3 — payment method -->
                    <div class="wizard-step hidden grid gap-3" data-step="3">
                        <label><?php echo $L['choose_payment']; ?></label>
                        <div id="paymentMethodSelection" class="grid gap-3">
                            <button type="button" id="cashButton" class="btn-secondary payment-method-btn"
                                onclick="pickMethod('bar')" data-method="cash"
                                aria-label="<?php echo $L['cash_payment']; ?>"
                                style="display:flex;align-items:center;justify-content:center;gap:0.5rem;min-height:56px;padding:0 1rem;width:100%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-coins">
                                    <circle cx="8" cy="8" r="6" />
                                    <path d="M18.09 10.37A6 6 0 1 1 10.34 18" />
                                    <path d="M7 6h1v4" />
                                    <path d="m16.71 13.88.7.71-2.82 2.82" />
                                </svg>
                                <span><?php echo $L['cash_payment']; ?></span>
                            </button>
                            <button type="button" id="stripeButton" class="btn-primary payment-method-btn"
                                onclick="pickMethod('stripe')" data-method="online"
                                aria-label="<?php echo $L['online_payment']; ?>"
                                style="display:flex;align-items:center;justify-content:center;gap:0.5rem;min-height:56px;padding:0 1rem;width:100%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-credit-card">
                                    <rect width="20" height="14" x="2" y="5" rx="2" />
                                    <line x1="2" x2="22" y1="10" y2="10" />
                                </svg>
                                <span><?php echo $L['online_payment']; ?> / Card &amp; More</span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4 — confirm & pay -->
                    <div class="wizard-step hidden grid gap-4" data-step="4">
                        <div class="grid gap-2" style="border:1px solid var(--avo-border);border-radius:12px;padding:12px 14px;background-color:var(--avo-surface);">
                            <div class="font-semibold text-sm"><?php echo $L['summary']; ?></div>
                            <div id="orderSummary" class="text-sm grid gap-1" style="color:var(--avo-text-muted);"></div>
                        </div>

                        <div class="grid gap-3" style="border:1px solid var(--avo-border);border-radius:12px;padding:14px 16px;background-color:var(--avo-surface);">
                            <div style="font-weight:700;color:var(--avo-primary);display:flex;align-items:center;gap:.45rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                    <path d="M12 9v4" /><path d="M12 17h.01" />
                                </svg>
                                <?php echo htmlspecialchars($L['booking_notice_title']); ?>
                            </div>
                            <p style="margin:0;font-size:.9rem;color:var(--avo-text);"><?php echo htmlspecialchars($L['binding_notice']); ?></p>
                            <ul style="margin:0;padding-left:1.1rem;list-style:disc;font-size:.85rem;color:var(--avo-text-muted);">
                                <li><?php echo htmlspecialchars($L['storno_card']); ?></li>
                                <li><?php echo htmlspecialchars($L['storno_cash']); ?></li>
                            </ul>
                            <p style="margin:0;font-size:.85rem;color:var(--avo-text-muted);"><?php echo $stornoContact; ?></p>
                            <label class="flex items-start gap-2" style="cursor:pointer;font-size:.9rem;color:var(--avo-text);">
                                <input type="checkbox" name="consent" id="consentCheckbox" value="1" required style="margin-top:.2rem;flex:none;">
                                <span><?php echo htmlspecialchars($L['consent_label']); ?></span>
                            </label>
                        </div>

                        <button type="submit" id="cashConfirmButton" class="btn-primary w-full hidden"
                            aria-label="<?php echo $L['book_tickets']; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-badge-check">
                                <path
                                    d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg> <?php echo $L['book_tickets']; ?>
                        </button>
                        <div id="stripePaymentContainer" class="hidden">
                            <div id="stripe-payment-element"></div>
                            <div id="stripe-payment-error" class="hidden mt-2 text-red-400 text-sm"></div>
                            <button type="button" id="stripeConfirmButton" class="btn-primary w-full mt-3 hidden"
                                onclick="confirmStripePayment()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-lock">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <?php echo $L['pay_now']; ?>
                            </button>
                        </div>
                    </div>

                    <!-- Wizard navigation -->
                    <div class="flex gap-3 mt-1">
                        <button type="button" id="wizardBack" class="btn-secondary flex-1 hidden" onclick="wizardGoBack()">
                            <?php echo $L['back']; ?>
                        </button>
                        <button type="button" id="wizardNext" class="btn-primary flex-1" onclick="wizardGoNext()">
                            <?php echo $L['next']; ?>
                        </button>
                    </div>
                </form>
            </section>
        </div>
        <button type="button" aria-label="Close dialog" onclick="this.closest('dialog').close()"
            class="absolute top-4 right-4 avo-muted hover:opacity-70 transition-opacity">
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
            <div class="backdrop-blur-md rounded-lg p-8 max-w-md w-full text-center"
                style="background-color: color-mix(in oklab, var(--avo-error) 18%, transparent); border: 1px solid color-mix(in oklab, var(--avo-error) 45%, transparent);">
                <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--avo-error);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-2xl font-bold mb-4"><?php echo $languages[$current_language]['error_loading_shows']; ?></h2>
                <p class="avo-muted"><?php echo $languages[$current_language]['try_again']; ?></p>
                <button onclick="location.reload()" class="btn-destructive mt-6">
                    <?php echo $languages[$current_language]['try_again_button']; ?>
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php if ($shows["store_lock"] === true): ?>
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="backdrop-blur-md rounded-lg p-8 max-w-md w-full text-center"
                    style="background-color: color-mix(in oklab, var(--avo-error) 18%, transparent); border: 1px solid color-mix(in oklab, var(--avo-error) 45%, transparent);">
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; "><svg
                            xmlns="http://www.w3.org/2000/svg" width="85" height="85" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-lock-icon lucide-lock">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg></div>
                    <h2 class="text-2xl font-bold mb-4"><br><?php echo $languages[$current_language]['store_lock_title']; ?>
                    </h2>
                    <p class="avo-muted">
                        <?php echo str_replace('{name}', htmlspecialchars($shows['orga_name']), $languages[$current_language]['store_lock_message']); ?>
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
                            document.getElementById('bannerBackground').style.backgroundImage = `url('<?php echo PUBLIC_API_BASE; ?>/api/image/get/banner.png?t=${timestamp}')`;
                        });
                    </script>
                    <div class="absolute inset-0 bg-black/60"></div>
                    <div class="relative z-10 text-center max-w-2xl w-full py-6 md:py-10 px-3 rounded-xl">
                        <div class="avo-kicker mb-2 animate-fade-in-up">// tickets</div>
                        <h1 class="orga-name text-3xl md:text-5xl font-bold mb-2 animate-fade-in-up">
                            <?php echo htmlspecialchars($shows['orga_name'] ?? ''); ?>
                        </h1>
                        <h2 class="show-name text-xl md:text-3xl font-semibold mb-1 animate-fade-in-up">
                            <span class="px-2 py-1 rounded-md">
                                <?php echo htmlspecialchars($shows['title'] ?? ''); ?>
                            </span>
                        </h2>
                        <?php if (!empty(trim($shows['subtitle'] ?? ''))): ?>
                            <h3 class="text-lg md:text-xl mt-2 animate-fade-in-up opacity-90">
                                <?php echo htmlspecialchars($shows['subtitle'] ?? ''); ?>
                            </h3>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="container mx-auto px-4 py-8">
                    <?php
                    // Group the available dates by their assigned location so the
                    // shop can render one section per venue. Dates without a
                    // location fall into the '' bucket, rendered last.
                    $locations = (isset($shows['locations']) && is_array($shows['locations'])) ? $shows['locations'] : [];
                    $grouped = [];
                    foreach ($shows['dates'] as $id => $show) {
                        $locId = $show['location'] ?? '';
                        if (!isset($locations[$locId])) {
                            $locId = '';
                        }
                        $grouped[$locId][$id] = $show;
                    }
                    // Order: known locations first (in their defined order), then ungrouped.
                    $groupOrder = [];
                    foreach (array_keys($locations) as $lid) {
                        if (!empty($grouped[$lid])) {
                            $groupOrder[] = $lid;
                        }
                    }
                    if (!empty($grouped[''])) {
                        $groupOrder[] = '';
                    }
                    $hasNamedLocations = false;
                    foreach ($groupOrder as $lid) {
                        if ($lid !== '') { $hasNamedLocations = true; break; }
                    }
                    foreach ($groupOrder as $locId):
                        $group = $grouped[$locId];
                        $loc = $locations[$locId] ?? null;
                        ?>
                        <?php if ($locId !== '' && $loc): ?>
                            <div class="mb-6 mt-4 animate-fade-in-up">
                                <div class="avo-kicker mb-2">// location</div>
                                <h2 class="text-2xl md:text-4xl font-bold">
                                    <?php echo htmlspecialchars($loc['name'] ?? ''); ?>
                                </h2>
                                <?php if (!empty(trim($loc['address'] ?? ''))): ?>
                                    <p class="opacity-80 mt-1" style="display:inline-flex;align-items:center;gap:0.4rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="lucide lucide-map-pin-icon lucide-map-pin">
                                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                        <?php echo htmlspecialchars($loc['address']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($hasNamedLocations): ?>
                            <div class="mb-6 mt-4 animate-fade-in-up">
                                <div class="avo-kicker mb-2">// more</div>
                                <h2 class="text-2xl md:text-4xl font-bold">
                                    <?php echo $languages[$current_language]['other_dates'] ?? 'Other dates'; ?>
                                </h2>
                            </div>
                        <?php endif; ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                        <?php foreach ($group as $id => $show): ?>
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
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
                let lastFocusedElement = null;

                document.addEventListener('DOMContentLoaded', initPaymentMethods);

                function closeModal() {
                    const modal = document.getElementById('bookingModal');
                    modal.close();
                    modal.classList.remove('modal-exit');
                    document.body.style.overflow = 'auto';
                    resetWizard();
                    if (lastFocusedElement) lastFocusedElement.focus();
                }

                // ---- Multi-step booking wizard --------------------------------
                let wizardStep = 1;
                const WIZARD_TITLES = {
                    1: '<?php echo addslashes($L['step1_title']); ?>',
                    2: '<?php echo addslashes($L['step2_title']); ?>',
                    3: '<?php echo addslashes($L['step3_title']); ?>',
                    4: '<?php echo addslashes($L['step4_title']); ?>'
                };
                const STEP_OF_TPL = '<?php echo addslashes($L['step_of']); ?>';

                function resetWizard() {
                    document.getElementById('paymentMethodInput').value = '';
                    document.getElementById('paymentIntentId').value = '';
                    document.getElementById('cashConfirmButton').classList.add('hidden');
                    document.getElementById('stripePaymentContainer').classList.add('hidden');
                    document.getElementById('stripeConfirmButton').classList.add('hidden');
                    document.getElementById('stripe-payment-element').innerHTML = '';
                    const consent = document.getElementById('consentCheckbox');
                    if (consent) consent.checked = false;
                    window.stripeElements = null;
                    window.stripeInstance = null;
                    goToStep(1);
                }

                function goToStep(n) {
                    wizardStep = n;
                    document.querySelectorAll('.wizard-step').forEach(el => {
                        el.classList.toggle('hidden', parseInt(el.dataset.step, 10) !== n);
                    });
                    document.querySelectorAll('.wizard-dot').forEach(d => {
                        const i = parseInt(d.dataset.dot, 10);
                        d.classList.toggle('active', i === n);
                        d.classList.toggle('done', i < n);
                    });
                    document.getElementById('wizardStepTitle').innerHTML = WIZARD_TITLES[n] || '';
                    document.getElementById('wizardStepCount').textContent = STEP_OF_TPL.replace('{n}', n);
                    document.getElementById('wizardBack').classList.toggle('hidden', n === 1);
                    // Next is hidden on step 3 (advance by picking a method) and step 4 (pay/book live there).
                    document.getElementById('wizardNext').classList.toggle('hidden', n === 3 || n === 4);
                    const stepEl = document.querySelector('.wizard-step[data-step="' + n + '"]');
                    const firstInput = stepEl && stepEl.querySelector('input:not([type=hidden]), select');
                    if (firstInput && n !== 3) setTimeout(() => { try { firstInput.focus(); } catch (e) {} }, 60);
                }

                // NOTE: do not name these wizardNext/wizardBack — those ids exist on
                // the buttons inside the <form>, and an inline onclick resolves that
                // name to the form's control (the button element), shadowing the
                // function and making the click a no-op. Distinct names avoid the clash.
                function wizardGoNext() {
                    if (wizardStep === 1) {
                        if (!validateStep1()) return;
                        goToStep(2);
                    } else if (wizardStep === 2) {
                        if (!validateStep2()) return;
                        goToStep(3);
                        maybeAutoMethod();
                    }
                }

                function wizardGoBack() {
                    if (wizardStep === 4) {
                        // Leaving confirm — clear payment-specific state so re-picking is clean.
                        document.getElementById('cashConfirmButton').classList.add('hidden');
                        document.getElementById('stripePaymentContainer').classList.add('hidden');
                        document.getElementById('stripeConfirmButton').classList.add('hidden');
                        document.getElementById('stripe-payment-element').innerHTML = '';
                        document.getElementById('paymentMethodInput').value = '';
                        document.getElementById('paymentIntentId').value = '';
                        window.stripeElements = null;
                        window.stripeInstance = null;
                        goToStep(3);
                        return;
                    }
                    if (wizardStep > 1) goToStep(wizardStep - 1);
                }

                async function pickMethod(method) {
                    document.getElementById('paymentMethodInput').value = method; // 'bar' | 'stripe'
                    buildSummary(method);
                    goToStep(4);

                    const cashBtn = document.getElementById('cashConfirmButton');
                    const stripeBox = document.getElementById('stripePaymentContainer');
                    if (method === 'bar') {
                        stripeBox.classList.add('hidden');
                        cashBtn.classList.remove('hidden');
                    } else {
                        cashBtn.classList.add('hidden');
                        stripeBox.classList.remove('hidden');
                        const price = parseFloat(document.getElementById('ticketPrice').value);
                        const tickets = parseInt(document.querySelector('select[name="tickets"]').value);
                        await createStripeElements(price, tickets);
                    }
                }

                // Skip the choice screen if only one method is offered.
                function maybeAutoMethod() {
                    const cash = document.getElementById('cashButton');
                    const card = document.getElementById('stripeButton');
                    const cashVisible = cash && cash.style.display !== 'none';
                    const cardVisible = card && card.style.display !== 'none';
                    if (cashVisible && !cardVisible) pickMethod('bar');
                    else if (cardVisible && !cashVisible) pickMethod('stripe');
                }

                function buildSummary(method) {
                    const fn = document.querySelector('input[name="first_name"]').value.trim();
                    const ln = document.querySelector('input[name="last_name"]').value.trim();
                    const tickets = parseInt(document.querySelector('select[name="tickets"]').value, 10) || 1;
                    const price = parseFloat(document.getElementById('ticketPrice').value) || 0;
                    const total = (price * tickets).toFixed(2);
                    const methodLabel = method === 'bar'
                        ? '<?php echo addslashes($L['cash_payment']); ?>'
                        : '<?php echo addslashes($L['online_payment']); ?>';
                    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
                    const rows = [
                        ['<?php echo addslashes($L['first_name']); ?> / <?php echo addslashes($L['last_name']); ?>', (fn + ' ' + ln).trim()],
                        ['<?php echo addslashes($L['number_of_tickets']); ?>', tickets],
                        ['<?php echo addslashes($L['payment_method']); ?>', methodLabel],
                        ['<?php echo addslashes($L['total']); ?>', total + ' €']
                    ];
                    document.getElementById('orderSummary').innerHTML = rows.map(r =>
                        '<div class="flex justify-between gap-3"><span>' + esc(r[0]) + '</span><span style="color:var(--avo-text);font-weight:600;">' + esc(r[1]) + '</span></div>'
                    ).join('');
                }

                function validateStep1() {
                    const msg = wizardMessages();
                    const firstName = document.querySelector('input[name="first_name"]').value.trim();
                    const lastName = document.querySelector('input[name="last_name"]').value.trim();
                    const email = document.querySelector('input[name="email"]').value.trim();
                    const errors = [];
                    if (!firstName) errors.push(msg.firstName);
                    if (!lastName) errors.push(msg.lastName);
                    if (!email) {
                        errors.push(msg.emailRequired);
                    } else {
                        const re = /^[-a-z0-9!#$%&'*+/=?^_`{|}~]+(?:\.[-a-z0-9!#$%&'*+/=?^_`{|}~]+)*@(?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)+[a-z0-9](?:[-a-z0-9]*[a-z0-9])?/;
                        if (!re.test(email)) errors.push(msg.emailInvalid);
                    }
                    if (errors.length) { showErrorDialog(errors.join('\n')); return false; }
                    return true;
                }

                function validateStep2() {
                    const msg = wizardMessages();
                    const tickets = document.querySelector('select[name="tickets"]').value;
                    const errors = [];
                    if (!tickets || tickets < 1) errors.push(msg.tickets);
                    for (const input of document.querySelectorAll('input[name="add_people[]"]')) {
                        if (!input.value.trim()) { errors.push(msg.missingAdditionalName); break; }
                    }
                    if (errors.length) { showErrorDialog(errors.join('\n')); return false; }
                    return true;
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

                function wizardMessages() {
                    const currentLang = '<?php echo $current_language; ?>';
                    const messages = {
                        en: {
                            firstName: 'Please enter your first name.',
                            lastName: 'Please enter your last name.',
                            emailRequired: 'Please enter your email.',
                            emailInvalid: 'Please enter a valid email address.',
                            tickets: 'Please select the number of tickets.',
                            missingAdditionalName: 'Please enter a name for all additional tickets.',
                            consent: '<?php echo addslashes($languages[$current_language]['consent_required']); ?>'
                        },
                        de: {
                            firstName: 'Bitte geben Sie Ihren Vornamen ein.',
                            lastName: 'Bitte geben Sie Ihren Nachnamen ein.',
                            emailRequired: 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
                            emailInvalid: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
                            tickets: 'Bitte wählen Sie die Anzahl der Tickets aus.',
                            missingAdditionalName: 'Bitte geben Sie für alle zusätzlichen Tickets einen Namen an.',
                            consent: '<?php echo addslashes($languages[$current_language]['consent_required']); ?>'
                        }
                    };
                    return messages[currentLang] || messages.en;
                }

                function validateForm() {
                    const firstName = document.querySelector('input[name="first_name"]').value.trim();
                    const lastName = document.querySelector('input[name="last_name"]').value.trim();
                    const email = document.querySelector('input[name="email"]').value.trim();
                    const tickets = document.querySelector('select[name="tickets"]').value;
                    const additionalNameInputs = document.querySelectorAll('input[name="add_people[]"]');

                    const msg = wizardMessages();
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

                    const consent = document.getElementById('consentCheckbox');
                    if (!consent || !consent.checked) errors.push(msg.consent);

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

                async function createStripeElements(pricePerTicket, tickets) {
                    const container = document.getElementById('stripe-payment-element');
                    const errorDiv = document.getElementById('stripe-payment-error');
                    const confirmBtn = document.getElementById('stripeConfirmButton');

                    container.innerHTML = '<p style="color: var(--text-secondary); text-align: center;">Loading payment form…</p>';
                    errorDiv.classList.add('hidden');
                    confirmBtn.classList.add('hidden');

                    if (!stripePublishableKey) {
                        container.innerHTML = '';
                        errorDiv.textContent = 'Payment is not configured. Please contact the organizer.';
                        errorDiv.classList.remove('hidden');
                        return;
                    }

                    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('price', pricePerTicket);
                    formData.append('tickets', tickets);

                    let intentData;
                    try {
                        const resp = await fetch('stripe-intent.php', { method: 'POST', body: formData });
                        intentData = await resp.json();
                    } catch (err) {
                        container.innerHTML = '';
                        errorDiv.textContent = 'Network error. Please try again.';
                        errorDiv.classList.remove('hidden');
                        return;
                    }

                    if (intentData.error) {
                        container.innerHTML = '';
                        errorDiv.textContent = intentData.error;
                        errorDiv.classList.remove('hidden');
                        return;
                    }

                    document.getElementById('paymentIntentId').value = intentData.payment_intent_id;

                    const stripe = Stripe(stripePublishableKey);
                    window.stripeInstance = stripe;

                    const elements = stripe.elements({ clientSecret: intentData.client_secret });
                    window.stripeElements = elements;

                    const paymentElement = elements.create('payment');
                    container.innerHTML = '';
                    paymentElement.mount('#stripe-payment-element');
                    paymentElement.on('ready', () => {
                        confirmBtn.classList.remove('hidden');
                    });
                }

                async function confirmStripePayment() {
                    const confirmBtn = document.getElementById('stripeConfirmButton');
                    const errorDiv = document.getElementById('stripe-payment-error');

                    // Final guard: name/email/tickets/consent must all be valid before paying.
                    if (!validateForm()) return;
                    if (!window.stripeInstance || !window.stripeElements) return;

                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="status" aria-label="Loading" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg> Processing…`;

                    const { error } = await window.stripeInstance.confirmPayment({
                        elements: window.stripeElements,
                        redirect: 'if_required',
                    });

                    if (error) {
                        errorDiv.textContent = error.message;
                        errorDiv.classList.remove('hidden');
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock-icon lucide-lock"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Pay Now`;
                        return;
                    }

                    // Payment succeeded — submit form to buy.php for server-side verification
                    document.getElementById('bookingForm').submit();
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
                            label.className = 'block text-sm font-medium avo-muted';

                            label.textContent = `Name for Ticket ${i}`;

                            const input = document.createElement('input');
                            input.type = 'text';
                            input.name = 'add_people[]';
                            input.placeholder = 'Max Mustermann';
                            input.required = true;
                            input.className = 'input w-full';

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
                    // Show/hide the method buttons on step 3 according to what the
                    // organizer enabled. Auto-skipping the step when only one method
                    // is available is handled by maybeAutoMethod() on entering step 3.
                    document.querySelectorAll('.payment-method-btn').forEach(button => {
                        const method = button.getAttribute('data-method');
                        button.style.display = 'flex';
                        if (availablePaymentMethods === 'cash' && method === 'online') {
                            button.style.display = 'none';
                        } else if (availablePaymentMethods === 'online' && method === 'cash') {
                            button.style.display = 'none';
                        }
                    });
                }


                async function initPaymentMethods() {
                    await loadPaymentMethods();
                    updatePaymentMethodButtons();
                }


                function showBookingForm(showId, date, price, ticketsAvailable) {
                    lastFocusedElement = document.activeElement;
                    document.getElementById('bookingModal').showModal();

                    document.getElementById('validDate').value = date;
                    document.getElementById('ticketPrice').value = price;
                    document.body.style.overflow = 'hidden';

                    const ticketsSelect = document.querySelector('select[name="tickets"]');
                    ticketsSelect.innerHTML = '';
                    const maxtickets = Math.min(ticketsAvailable, 10);
                    for (let i = 1; i <= maxtickets; i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = `${i} Ticket${i > 1 ? 's' : ''}`;
                        ticketsSelect.appendChild(option);
                    }

                    // Clear any name/email from a previous booking, then start fresh at step 1.
                    document.querySelector('input[name="first_name"]').value = '';
                    document.querySelector('input[name="last_name"]').value = '';
                    document.querySelector('input[name="email"]').value = '';

                    initTicketSelector();
                    updatePaymentMethodButtons();
                    resetWizard();
                }


                async function processPayment(paymentDetails) {
                    const available = await checkAvailability(paymentDetails.showId, paymentDetails.ticketCount);
                    if (!available) {
                        throw new Error("Nicht genügend Plätze verfügbar.");
                    }
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

    <?php
    $orgName = $shows['orga_name'] ?? '';
    include __DIR__ . '/partials/footer.php';
    ?>
</body>

</html>
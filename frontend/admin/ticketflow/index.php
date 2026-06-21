<?php
require_once "../../config.php";

if (!isset($_SESSION["admin"]) && !isset($_SESSION["ticketflow_access"])) {
    header("Location: ../login.php?redirect=ticketflow");
    exit();
}
if (!empty($_SESSION["must_change_pw"])) {
    header("Location: ../change_password.php");
    exit();
}

// CSRF guard for state-changing POST handlers. Token is accepted from the
// X-CSRF-Token header (AJAX/fetch) or a csrf_token POST field (HTML forms).
// Read-only lookups (ticket_id lookup, no action) are not guarded.
function tf_require_csrf($asJson = false) {
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? $_POST["csrf_token"] ?? "";
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        if ($asJson) {
            header("Content-Type: application/json");
            echo json_encode(["status" => "error", "message" => "Invalid or missing CSRF token"]);
        } else {
            echo "Invalid request. Please reload the page and try again.";
        }
        exit();
    }
}

// --- language switch ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["language"])) {
    tf_require_csrf(false);
    $_SESSION["language"] = in_array($_POST["language"], ["de", "en"], true) ? $_POST["language"] : "de";
    header("Location: index.php");
    exit();
}

// --- same-origin PDF proxy ---------------------------------------------------
// Streams the combined multi-page ticket PDF from the backend so it can be
// loaded into a same-origin <iframe> and printed directly (no popups, one job).
if (isset($_GET["print"])) {
    $tids = preg_replace('/[^0-9A-Za-z,\-]/', '', $_GET["print"]);
    // The backend's /codes/pdf is gated by a per-ticket HMAC token. Build a
    // parallel ?tokens= list (same order as tids) so the batch print is accepted.
    $tidList = array_values(array_filter(array_map('trim', explode(',', $tids)), 'strlen'));
    $tokenList = array_map(
        fn($tid) => substr(hash_hmac('sha256', $tid, API_KEY), 0, 16),
        $tidList
    );
    $ch = curl_init(
        API_BASE_URL . "codes/pdf?tids=" . urlencode(implode(',', $tidList))
        . "&tokens=" . urlencode(implode(',', $tokenList))
    );
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ["Authorization: " . API_KEY],
    ]);
    $pdf  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $pdf !== false && $pdf !== "") {
        header("Content-Type: application/pdf");
        header('Content-Disposition: inline; filename="tickets.pdf"');
        echo $pdf;
    } else {
        http_response_code(404);
        echo "PDF not found";
    }
    exit();
}

// --- backend proxy (keeps API_KEY server-side) -------------------------------
function call_api($endpoint, $data, $method = 'POST') {
    $ch = curl_init(API_BASE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: " . API_KEY,
            "Content-Type: application/json",
        ],
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($data),
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$http_code, json_decode($response, true)];
}

// --- AJAX: fast box-office create --------------------------------------------
// One create call per ticket so every seat gets its own QR / PDF.
if (($_POST["ajax"] ?? "") === "create") {
    tf_require_csrf(true);
    header("Content-Type: application/json");

    $type       = $_POST["type"] ?? "visitor";
    $paid       = ($_POST["paid"] ?? "true") === "true";
    $valid_date = trim($_POST["valid_date"] ?? "");
    $qty        = max(1, min(50, (int) ($_POST["qty"] ?? 1)));
    $firstname  = trim($_POST["firstname"] ?? "");
    $lastname   = trim($_POST["lastname"] ?? "");
    $email      = trim($_POST["email"] ?? "");

    if ($type === "visitor" && $valid_date === "") {
        echo json_encode(["status" => "error", "message" => "no_date"]);
        exit();
    }

    $tids  = [];
    $error = null;
    for ($i = 0; $i < $qty; $i++) {
        [$code, $resp] = call_api("api/ticketflow/create", [
            "type"       => $type,
            "paid"       => $paid,
            "valid_date" => $valid_date,
            "tickets"    => 1,
            "first_name" => $firstname,
            "last_name"  => $lastname,
            "email"      => $email ?: null,
        ]);
        if ($code === 200 && !empty($resp["tid"])) {
            $tids[] = $resp["tid"];
        } else {
            $error = $resp["message"] ?? "unknown";
            break;
        }
    }

    echo json_encode([
        "status"  => $tids ? "success" : "error",
        "tids"    => $tids,
        "message" => $error,
    ]);
    exit();
}

$shows = getShows() ?: [];

// --- i18n --------------------------------------------------------------------
$orga = htmlspecialchars($shows["orga_name"] ?? "");
$languages = [
    "en" => [
        "flag" => "🇬🇧", "name" => "English",
        "subtitle" => "Box office for " . $orga,
        "tab_sell" => "Sell", "tab_edit" => "Edit ticket",
        "sell_kicker" => "// box office", "sell_heading" => "New sale",
        "step_date" => "Date", "step_qty" => "Quantity",
        "step_type" => "Type", "step_paid" => "Payment",
        "no_dates" => "No event dates configured.",
        "sold_out" => "Sold out", "left" => "left", "from" => "from",
        "type_visitor" => "Visitor", "type_vip" => "VIP", "type_admin" => "Admin",
        "paid_label" => "Paid (cash)", "paid_hint" => "On = ticket valid immediately",
        "details_optional" => "Name / email (optional)",
        "first_name" => "First name", "last_name" => "Last name", "email" => "Email",
        "email_hint" => "If set, the ticket is also emailed",
        "total" => "Total", "create_print" => "Create &amp; print",
        "creating" => "Creating…",
        "success_title" => "Created", "print" => "Print", "print_all" => "Print all",
        "new_sale" => "Next sale", "err_no_date" => "Please pick a date first.",
        "err_generic" => "Could not create ticket",
        "edit_intro" => "Look up a ticket by its ID to edit or reprint it.",
        "ticket_id" => "Ticket ID", "lookup" => "Look up",
        "id_date_label" => "Date → ID start", "id_date_none" => "— pick date —",
        "type" => "Type", "paid" => "Paid", "usable" => "Usable",
        "date_custom" => "Other date (calendar)…",
        "valid_date" => "Valid date", "save" => "Save", "cancel" => "Cancel",
        "saved" => "Ticket saved.", "save_err" => "Error saving ticket",
        "true" => "True", "false" => "False",
        "cancel_ticket" => "Cancel / Refund",
        "cancel_reason" => "Reason (optional)",
        "cancel_confirm" => "Cancel this ticket? If it was paid by card it will be refunded. This cannot be undone.",
        "cancel_ok" => "Ticket cancelled.", "cancel_err" => "Could not cancel ticket",
        "switch_app" => "Switch app",
    ],
    "de" => [
        "flag" => "🇩🇪", "name" => "Deutsch",
        "subtitle" => "Abendkasse für " . $orga,
        "tab_sell" => "Verkaufen", "tab_edit" => "Ticket bearbeiten",
        "sell_kicker" => "// abendkasse", "sell_heading" => "Neuer Verkauf",
        "step_date" => "Datum", "step_qty" => "Anzahl",
        "step_type" => "Typ", "step_paid" => "Zahlung",
        "no_dates" => "Keine Veranstaltungstermine konfiguriert.",
        "sold_out" => "Ausverkauft", "left" => "frei", "from" => "ab",
        "type_visitor" => "Besucher", "type_vip" => "VIP", "type_admin" => "Admin",
        "paid_label" => "Bezahlt (bar)", "paid_hint" => "An = Ticket sofort gültig",
        "details_optional" => "Name / E-Mail (optional)",
        "first_name" => "Vorname", "last_name" => "Nachname", "email" => "E-Mail",
        "email_hint" => "Wenn gesetzt, wird das Ticket zusätzlich per Mail verschickt",
        "total" => "Summe", "create_print" => "Erstellen &amp; drucken",
        "creating" => "Erstelle…",
        "success_title" => "Erstellt", "print" => "Drucken", "print_all" => "Alle drucken",
        "new_sale" => "Nächster Verkauf", "err_no_date" => "Bitte zuerst ein Datum wählen.",
        "err_generic" => "Ticket konnte nicht erstellt werden",
        "edit_intro" => "Ticket per ID suchen, um es zu bearbeiten oder neu zu drucken.",
        "ticket_id" => "Ticket-ID", "lookup" => "Suchen",
        "id_date_label" => "Datum → ID-Anfang", "id_date_none" => "— Datum wählen —",
        "type" => "Typ", "paid" => "Bezahlt", "usable" => "Benutzbar",
        "date_custom" => "Anderes Datum (Kalender)…",
        "valid_date" => "Gültigkeitsdatum", "save" => "Speichern", "cancel" => "Abbrechen",
        "saved" => "Ticket gespeichert.", "save_err" => "Fehler beim Speichern",
        "true" => "Wahr", "false" => "Falsch",
        "cancel_ticket" => "Stornieren / Erstatten",
        "cancel_reason" => "Grund (optional)",
        "cancel_confirm" => "Dieses Ticket stornieren? Bei Kartenzahlung wird der Betrag erstattet. Das kann nicht rückgängig gemacht werden.",
        "cancel_ok" => "Ticket storniert.", "cancel_err" => "Ticket konnte nicht storniert werden",
        "switch_app" => "App wechseln",
    ],
];
$lang_code = $_SESSION["language"] ?? "de";
if (!isset($languages[$lang_code])) {
    $lang_code = "de";
}
$L = $languages[$lang_code];

// --- edit tab: lookup + save (server-side, rare action) ----------------------
$firstname = $lastname = $type = $valid_date = $ticket_id = "";
$paid = $valid = "false";
$flash = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ticket_id"]) && !isset($_POST["action"])) {
    $ticket_id = $_POST["ticket_id"];
    [$code, $td] = call_api("api/ticket/get", ["tid" => $ticket_id], "POST");
    if ($code === 200 && isset($td["data"])) {
        $firstname  = $td["data"]["first_name"] ?? "";
        $lastname   = $td["data"]["last_name"] ?? "";
        $type       = $td["data"]["type"] ?? "";
        $paid       = !empty($td["data"]["paid"]) ? "true" : "false";
        $valid_date = $td["data"]["valid_date"] ?? "";
        $valid      = !empty($td["data"]["valid"]) ? "true" : "false";
    } else {
        $flash = ["err", $L["err_generic"]];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? "") === "cancel_ticket") {
    tf_require_csrf(false);
    $cancel_tid = $_POST["ticket_id"] ?? "";
    [$code, $resp] = call_api("api/ticket/cancel", [
        "tid"     => $cancel_tid,
        "reason"  => trim($_POST["cancel_reason"] ?? ""),
        "scanner" => "ticketflow:" . ($_SESSION["username"] ?? "staff"),
    ]);
    if ($code === 200 && ($resp["status"] ?? "") === "success") {
        $flash = ["ok", $resp["message"] ?? $L["cancel_ok"]];
        $firstname = $lastname = $type = $valid_date = $ticket_id = "";
        $paid = $valid = "false";
    } else {
        $flash = ["err", ($resp["message"] ?? $L["cancel_err"])];
        // keep the looked-up ticket on screen so the operator can retry
        $ticket_id = $cancel_tid;
        [$code2, $td2] = call_api("api/ticket/get", ["tid" => $cancel_tid], "POST");
        if ($code2 === 200 && isset($td2["data"])) {
            $firstname  = $td2["data"]["first_name"] ?? "";
            $lastname   = $td2["data"]["last_name"] ?? "";
            $type       = $td2["data"]["type"] ?? "";
            $paid       = !empty($td2["data"]["paid"]) ? "true" : "false";
            $valid_date = $td2["data"]["valid_date"] ?? "";
            $valid      = !empty($td2["data"]["valid"]) ? "true" : "false";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? "") === "save_ticket") {
    tf_require_csrf(false);
    [$code, $resp] = call_api("api/ticket/edit", [
        "tid"        => $_POST["ticket_id"],
        "first_name" => $_POST["firstname"],
        "last_name"  => $_POST["lastname"],
        "type"       => $_POST["type"],
        "paid"       => $_POST["paid"] === "true",
        "valid"      => $_POST["valid"] === "true",
        "valid_date" => $_POST["valid_date"],
    ]);
    if ($code === 200) {
        $flash = ["ok", $L["saved"]];
        $firstname = $lastname = $type = $valid_date = $ticket_id = "";
        $paid = $valid = "false";
    } else {
        $flash = ["err", $L["save_err"] . ": " . ($resp["message"] ?? "")];
    }
}

$active_tab = !empty($ticket_id) ? "edit" : "sell";

// --- sorted date list for the sell grid --------------------------------------
$dates = [];
if (!empty($shows["dates"]) && is_array($shows["dates"])) {
    foreach ($shows["dates"] as $d) {
        if (!empty($d["date"])) $dates[] = $d;
    }
    usort($dates, fn($a, $b) => strcmp($a["date"], $b["date"]));
}
$today_iso = (new DateTime("now", new DateTimeZone("Europe/Berlin")))->format("Y-m-d");
$today_in_dates = false;
foreach ($dates as $d) { if (($d["date"] ?? "") === $today_iso) { $today_in_dates = true; break; } }
function tf_fmt_date($iso, $lang) {
    $ts = strtotime($iso);
    if (!$ts) return htmlspecialchars($iso);
    $wd = [
        "de" => ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
        "en" => ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    ];
    return $wd[$lang][(int) date("w", $ts)] . " " . date("d.m.Y", $ts);
}

$pageTitle = "TicketFlow";
$assetBase = "../../";
$csrfToken = generateCsrfToken();
$extraHead = '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES) . '">';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<?php include __DIR__ . '/../../partials/head.php'; ?>
<body class="min-h-screen">
<style>
    .tf-tabs { display: flex; gap: 6px; }
    .tf-tab {
        flex: 1; padding: 12px 14px; border-radius: var(--avo-radius-md);
        border: 1px solid var(--avo-border); background: transparent;
        color: var(--avo-text-muted); font-weight: 700; font-size: 0.95rem; cursor: pointer;
        font-family: var(--avo-font-display);
    }
    .tf-tab[aria-selected="true"] {
        background: var(--avo-primary); color: #fff; border-color: var(--avo-primary);
    }
    .tf-panel { display: none; }
    .tf-panel.active { display: block; }
    /* basecoat .card is flex+gap with padding only on header/section children;
       we render content directly, so reset to a normal padded block. */
    .tf-panel .card { display: block; gap: 0; padding: 32px 36px; }
    @media (max-width: 640px) { .tf-panel .card { padding: 20px 20px; } }
    .tf-step-label {
        font-family: var(--avo-font-mono); font-size: 0.8rem; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase; color: var(--avo-text-muted);
        margin-bottom: 12px; display: block;
    }
    /* date grid */
    .tf-dates { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
    .tf-date {
        text-align: left; padding: 16px; border-radius: var(--avo-radius-lg);
        border: 2px solid var(--avo-border); background: var(--avo-bg); cursor: pointer;
        transition: border-color .12s, background .12s; color: var(--avo-text);
    }
    .tf-date:hover:not(.disabled) { border-color: var(--avo-primary); }
    .tf-date.active { border-color: var(--avo-primary); background: color-mix(in oklab, var(--avo-primary) 12%, transparent); }
    .tf-date.disabled { opacity: .45; cursor: not-allowed; }
    .tf-date .d-day { font-family: var(--avo-font-display); font-weight: 800; font-size: 1.15rem; line-height: 1.15; }
    .tf-date .d-meta { font-size: 0.85rem; color: var(--avo-text-muted); margin-top: 5px; }
    .tf-date .d-left { font-family: var(--avo-font-mono); font-size: 0.76rem; margin-top: 7px; }
    .tf-date .d-left.low { color: var(--avo-warning); }
    .tf-date .d-left.out { color: var(--avo-error); }
    /* stepper */
    .tf-stepper { display: inline-flex; align-items: center; gap: 0; border: 2px solid var(--avo-border); border-radius: var(--avo-radius-lg); overflow: hidden; }
    .tf-stepper button {
        width: 66px; height: 66px; font-size: 1.9rem; font-weight: 700;
        background: var(--avo-bg); color: var(--avo-text); border: none; cursor: pointer;
    }
    .tf-stepper button:hover { background: var(--avo-surface); }
    .tf-stepper input {
        width: 86px; height: 66px; text-align: center; font-size: 1.7rem; font-weight: 800;
        border: none; border-left: 2px solid var(--avo-border); border-right: 2px solid var(--avo-border);
        background: var(--avo-bg); color: var(--avo-text); font-family: var(--avo-font-display);
        -moz-appearance: textfield;
    }
    .tf-stepper input::-webkit-outer-spin-button, .tf-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    /* segmented */
    .tf-seg { display: inline-flex; border: 2px solid var(--avo-border); border-radius: var(--avo-radius-lg); overflow: hidden; }
    .tf-seg button { padding: 18px 28px; border: none; background: var(--avo-bg); color: var(--avo-text-muted); font-weight: 700; cursor: pointer; font-size: 1.05rem; }
    .tf-seg button.active { background: var(--avo-primary); color: #fff; }
    /* switch */
    .tf-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
    .tf-switch input { opacity: 0; width: 0; height: 0; }
    .tf-switch .slider { position: absolute; inset: 0; background: var(--avo-border); border-radius: 999px; transition: .15s; }
    .tf-switch .slider:before { content: ""; position: absolute; height: 26px; width: 26px; left: 4px; top: 4px; background: #fff; border-radius: 50%; transition: .15s; }
    .tf-switch input:checked + .slider { background: var(--avo-success); }
    .tf-switch input:checked + .slider:before { transform: translateX(26px); }
    /* total + cta */
    .tf-total { font-family: var(--avo-font-display); font-weight: 800; font-size: 2.4rem; }
    .tf-cta { width: 100%; padding: 22px; font-size: 1.3rem; }
    .tf-cta:disabled { opacity: .5; cursor: not-allowed; }
    details.tf-details summary { cursor: pointer; font-weight: 700; color: var(--avo-primary); list-style: none; }
    details.tf-details summary::-webkit-details-marker { display: none; }
    /* toast */
    #tf-toast { position: fixed; top: 18px; right: 18px; z-index: 60; display: flex; flex-direction: column; gap: 8px; }
    .tf-toastmsg { padding: 12px 18px; border-radius: var(--avo-radius-md); color: #fff; font-weight: 700; box-shadow: 0 6px 20px rgba(0,0,0,.25); animation: avoFadeInUp .25s ease-out; }
    .tf-toastmsg.ok { background: var(--avo-success); }
    .tf-toastmsg.err { background: var(--avo-error); }
    /* result */
    #tf-result { display: none; }
    .tf-tid { font-family: var(--avo-font-mono); }
</style>

<div class="avo-topbar" aria-hidden="true"></div>
<div id="tf-toast"></div>

<!-- Header -->
<header class="px-6 py-4 flex justify-between items-center border-b" style="border-color: var(--avo-border); background: var(--avo-surface);">
    <div class="flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="avo-coral">
            <rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/>
            <rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
            <path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/>
            <path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>
        </svg>
        <h1 class="text-2xl font-bold">Ticket<span class="avo-hl">Flow</span></h1>
    </div>
    <div class="flex items-center gap-4">
        <form method="post" id="langForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
            <select name="language" onchange="this.form.submit()" class="select w-[150px]">
                <?php foreach ($languages as $code => $lang): ?>
                    <option value="<?php echo $code; ?>" <?php echo $lang_code === $code ? "selected" : ""; ?>>
                        <?php echo $lang["flag"] . " " . $lang["name"]; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="../apps.php" class="btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/>
                <rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
            </svg> <?php echo $L["switch_app"]; ?>
        </a>
        <a href="../logout.php" class="btn-destructive">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            </svg> Logout
        </a>
    </div>
</header>

<main class="container mx-auto px-6 py-8 max-w-3xl">
    <div class="mb-6">
        <div class="avo-kicker mb-1"><?php echo $L["sell_kicker"]; ?></div>
        <p class="avo-muted"><?php echo $L["subtitle"]; ?></p>
    </div>

    <!-- Tabs -->
    <div class="tf-tabs mb-6" role="tablist">
        <button class="tf-tab" role="tab" aria-selected="<?php echo $active_tab === "sell" ? "true" : "false"; ?>" data-tab="sell"><?php echo $L["tab_sell"]; ?></button>
        <button class="tf-tab" role="tab" aria-selected="<?php echo $active_tab === "edit" ? "true" : "false"; ?>" data-tab="edit"><?php echo $L["tab_edit"]; ?></button>
    </div>

    <!-- ============ SELL ============ -->
    <section class="tf-panel <?php echo $active_tab === "sell" ? "active" : ""; ?>" id="panel-sell">
        <?php if (empty($dates)): ?>
            <div class="card"><p class="avo-muted"><?php echo $L["no_dates"]; ?></p></div>
        <?php else: ?>
        <div class="card">
            <h2 class="text-3xl font-bold mb-6"><?php echo $L["sell_heading"]; ?></h2>

            <!-- date -->
            <label class="tf-step-label"><?php echo $L["step_date"]; ?></label>
            <div class="tf-dates mb-7">
                <?php foreach ($dates as $d):
                    $avail = (int) ($d["tickets_available"] ?? 0);
                    $price = $d["price"] ?? "";
                    $out   = $avail <= 0;
                    $low   = $avail > 0 && $avail <= 10;
                ?>
                <button type="button" class="tf-date <?php echo $out ? "disabled" : ""; ?>"
                        data-date="<?php echo htmlspecialchars($d["date"]); ?>"
                        data-price="<?php echo htmlspecialchars($price); ?>"
                        data-avail="<?php echo $avail; ?>" <?php echo $out ? "disabled" : ""; ?>>
                    <div class="d-day"><?php echo tf_fmt_date($d["date"], $lang_code); ?></div>
                    <div class="d-meta">
                        <?php echo htmlspecialchars($d["time"] ?? ""); ?> · <?php echo htmlspecialchars($price); ?> €
                    </div>
                    <div class="d-left <?php echo $out ? "out" : ($low ? "low" : ""); ?>">
                        <?php echo $out ? $L["sold_out"] : ($avail . " " . $L["left"]); ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap items-end gap-x-14 gap-y-6 mb-6">
                <!-- qty -->
                <div>
                    <label class="tf-step-label"><?php echo $L["step_qty"]; ?></label>
                    <div class="tf-stepper">
                        <button type="button" id="qtyMinus">−</button>
                        <input type="number" id="qty" value="1" min="1" inputmode="numeric">
                        <button type="button" id="qtyPlus">+</button>
                    </div>
                </div>
                <!-- type -->
                <div>
                    <label class="tf-step-label"><?php echo $L["step_type"]; ?></label>
                    <div class="tf-seg" id="typeSeg">
                        <button type="button" class="active" data-type="visitor"><?php echo $L["type_visitor"]; ?></button>
                        <button type="button" data-type="vip"><?php echo $L["type_vip"]; ?></button>
                        <button type="button" data-type="admin"><?php echo $L["type_admin"]; ?></button>
                    </div>
                </div>
            </div>

            <!-- paid -->
            <div class="flex items-center justify-between gap-4 mb-5 px-5 py-4 rounded-lg" style="background: var(--avo-bg);">
                <div>
                    <div class="font-bold"><?php echo $L["paid_label"]; ?></div>
                    <div class="avo-muted text-sm"><?php echo $L["paid_hint"]; ?></div>
                </div>
                <label class="tf-switch">
                    <input type="checkbox" id="paid" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <!-- optional name/email -->
            <details class="tf-details mb-5">
                <summary>+ <?php echo $L["details_optional"]; ?></summary>
                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block mb-2 text-sm"><?php echo $L["first_name"]; ?></label>
                        <input type="text" id="firstname" class="input w-full">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm"><?php echo $L["last_name"]; ?></label>
                        <input type="text" id="lastname" class="input w-full">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm"><?php echo $L["email"]; ?></label>
                        <input type="email" id="email" class="input w-full">
                    </div>
                </div>
                <p class="avo-muted text-sm mt-2"><?php echo $L["email_hint"]; ?></p>
            </details>

            <!-- total + cta -->
            <hr class="avo-divider" style="margin: 12px 0;">
            <div class="flex items-center justify-between mb-4">
                <span class="avo-muted"><?php echo $L["total"]; ?></span>
                <span class="tf-total avo-coral" id="total">0,00 €</span>
            </div>
            <button type="button" class="btn-primary tf-cta" id="createBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <path d="M6 9V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4"/><rect x="6" y="14" width="12" height="8" rx="1"/>
                </svg>
                <span id="createBtnLabel"><?php echo $L["create_print"]; ?></span>
            </button>

            <!-- result -->
            <div id="tf-result" class="mt-6 p-4 rounded-lg" style="background: var(--avo-bg); border: 1px solid var(--avo-border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold avo-coral" id="resultTitle"></h3>
                    <div class="flex gap-2">
                        <button type="button" class="btn-secondary" id="printAllBtn"><?php echo $L["print_all"]; ?></button>
                        <button type="button" class="btn-primary" id="newSaleBtn"><?php echo $L["new_sale"]; ?></button>
                    </div>
                </div>
                <div id="resultList" class="grid gap-2"></div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============ EDIT ============ -->
    <section class="tf-panel <?php echo $active_tab === "edit" ? "active" : ""; ?>" id="panel-edit">
        <div class="card">
            <h2 class="text-2xl font-bold mb-2"><?php echo $L["tab_edit"]; ?></h2>
            <p class="avo-muted mb-5"><?php echo $L["edit_intro"]; ?></p>

            <form action="" method="POST" class="flex gap-3 mb-2 items-end flex-wrap">
                <?php if (!empty($dates)): ?>
                <div>
                    <label class="block mb-1 text-xs avo-muted"><?php echo $L["id_date_label"]; ?></label>
                    <select id="idDatePrefix" class="select w-[190px]">
                        <option value=""><?php echo $L["id_date_none"]; ?></option>
                        <?php foreach ($dates as $d): ?>
                            <option value="<?php echo htmlspecialchars($d["date"]); ?>"><?php echo tf_fmt_date($d["date"], $lang_code); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <input type="text" name="ticket_id" id="lookupTid" class="input flex-1 min-w-[160px]" placeholder="<?php echo $L["ticket_id"]; ?>"
                       value="<?php echo htmlspecialchars($ticket_id); ?>" required>
                <button type="submit" class="btn-primary"><?php echo $L["lookup"]; ?></button>
            </form>

            <?php if (!empty($ticket_id)): ?>
                <form action="" method="POST" class="grid gap-4 mt-6">
                    <input type="hidden" name="action" value="save_ticket">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["first_name"]; ?></label>
                            <input type="text" name="firstname" class="input w-full" value="<?php echo htmlspecialchars($firstname); ?>">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["last_name"]; ?></label>
                            <input type="text" name="lastname" class="input w-full" value="<?php echo htmlspecialchars($lastname); ?>">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["type"]; ?></label>
                            <select name="type" class="select w-full">
                                <option value="visitor" <?php echo $type === "visitor" ? "selected" : ""; ?>><?php echo $L["type_visitor"]; ?></option>
                                <option value="admin" <?php echo $type === "admin" ? "selected" : ""; ?>><?php echo $L["type_admin"]; ?></option>
                                <option value="vip" <?php echo $type === "vip" ? "selected" : ""; ?>><?php echo $L["type_vip"]; ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["paid"]; ?></label>
                            <select name="paid" class="select w-full">
                                <option value="true" <?php echo $paid === "true" ? "selected" : ""; ?>><?php echo $L["true"]; ?></option>
                                <option value="false" <?php echo $paid === "false" ? "selected" : ""; ?>><?php echo $L["false"]; ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["usable"]; ?></label>
                            <select name="valid" class="select w-full">
                                <option value="true" <?php echo $valid === "true" ? "selected" : ""; ?>><?php echo $L["true"]; ?></option>
                                <option value="false" <?php echo $valid === "false" ? "selected" : ""; ?>><?php echo $L["false"]; ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm"><?php echo $L["valid_date"]; ?></label>
                            <?php
                                $date_in_list = false;
                                foreach ($dates as $d) { if ($d["date"] === $valid_date) { $date_in_list = true; break; } }
                                $is_custom = (!$date_in_list && $valid_date !== "");
                                // default to today when the ticket has no date yet and today is a configured date
                                $selected_date = $valid_date !== "" ? $valid_date : ($today_in_dates ? $today_iso : "");
                            ?>
                            <input type="hidden" name="valid_date" id="editValidDate" value="<?php echo htmlspecialchars($selected_date); ?>">
                            <select id="editDateSelect" class="select w-full mb-2">
                                <option value="" <?php echo ($selected_date === "" && !$is_custom) ? "selected" : ""; ?>>—</option>
                                <?php foreach ($dates as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d["date"]); ?>" <?php echo $selected_date === $d["date"] ? "selected" : ""; ?>><?php echo tf_fmt_date($d["date"], $lang_code); ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?php echo $is_custom ? "selected" : ""; ?>><?php echo $L["date_custom"]; ?></option>
                            </select>
                            <input type="date" id="editDateCustom" class="input w-full <?php echo $is_custom ? "" : "hidden"; ?>" value="<?php echo htmlspecialchars($is_custom ? $valid_date : $today_iso); ?>">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn-primary"><?php echo $L["save"]; ?></button>
                        <a href="index.php" class="btn-secondary"><?php echo $L["cancel"]; ?></a>
                        <button type="button" class="btn-secondary" onclick="printTickets([<?php echo json_encode($ticket_id); ?>])"><?php echo $L["print"]; ?></button>
                    </div>
                </form>

                <!-- cancel / refund (separate form so it never carries the edit fields) -->
                <form action="" method="POST" class="grid gap-3 mt-8 pt-6"
                      style="border-top:1px solid var(--avo-border)"
                      onsubmit="return confirm(<?php echo htmlspecialchars(json_encode($L["cancel_confirm"]), ENT_QUOTES); ?>);">
                    <input type="hidden" name="action" value="cancel_ticket">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">
                    <div>
                        <label class="block mb-2 text-sm"><?php echo $L["cancel_reason"]; ?></label>
                        <input type="text" name="cancel_reason" class="input w-full" maxlength="200">
                    </div>
                    <div>
                        <button type="submit" class="btn-destructive"><?php echo $L["cancel_ticket"]; ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
const TF = {
    apiBase: <?php echo json_encode(API_BASE_URL); ?>,
    i18n: <?php echo json_encode([
        "creating" => $L["creating"], "create_print" => $L["create_print"],
        "err_no_date" => $L["err_no_date"], "err_generic" => $L["err_generic"],
        "success_title" => $L["success_title"], "print" => $L["print"], "total" => $L["total"],
    ]); ?>,
};

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const state = { date: null, price: 0, avail: 0, type: "visitor", qty: 1 };

// ---- tabs ----
document.querySelectorAll(".tf-tab").forEach(t => t.addEventListener("click", () => {
    document.querySelectorAll(".tf-tab").forEach(x => x.setAttribute("aria-selected", "false"));
    t.setAttribute("aria-selected", "true");
    document.querySelectorAll(".tf-panel").forEach(p => p.classList.remove("active"));
    document.getElementById("panel-" + t.dataset.tab).classList.add("active");
}));

// ---- edit: ID date prefix (ISO YYYY-MM-DD -> tid prefix YYYY-DDMM-) ----
(function () {
    const sel = document.getElementById("idDatePrefix");
    const tid = document.getElementById("lookupTid");
    if (!sel || !tid) return;
    sel.addEventListener("change", () => {
        if (!sel.value) return;
        const [y, m, d] = sel.value.split("-");
        const prefix = y + "-" + d + m + "-";
        tid.value = prefix + tid.value.replace(/^\d{4}-\d{4}-/, "");
        tid.focus();
        tid.setSelectionRange(tid.value.length, tid.value.length);
        sel.value = "";
    });
})();

// ---- edit: valid_date list + calendar -> hidden input ----
(function () {
    const sel = document.getElementById("editDateSelect");
    const cust = document.getElementById("editDateCustom");
    const hidden = document.getElementById("editValidDate");
    if (!sel || !hidden) return;
    function sync() {
        if (sel.value === "__custom__") {
            cust.classList.remove("hidden");
            hidden.value = cust.value || "";
        } else {
            cust.classList.add("hidden");
            hidden.value = sel.value;
        }
    }
    sel.addEventListener("change", sync);
    cust.addEventListener("input", () => { if (sel.value === "__custom__") hidden.value = cust.value; });
    sync();
})();

// ---- toast ----
function toast(msg, kind = "ok") {
    const el = document.createElement("div");
    el.className = "tf-toastmsg " + kind;
    el.textContent = msg;
    document.getElementById("tf-toast").appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function fmtMoney(n) { return n.toLocaleString("<?php echo $lang_code; ?>", { style: "currency", currency: "EUR" }); }

function updateTotal() {
    const t = document.getElementById("total");
    if (state.type !== "visitor" || !state.date) { t.textContent = fmtMoney(0); return; }
    t.textContent = fmtMoney(state.price * state.qty);
}

// ---- date tiles ----
document.querySelectorAll(".tf-date").forEach(tile => {
    if (tile.classList.contains("disabled")) return;
    tile.addEventListener("click", () => {
        document.querySelectorAll(".tf-date").forEach(x => x.classList.remove("active"));
        tile.classList.add("active");
        state.date = tile.dataset.date;
        state.price = parseFloat(tile.dataset.price) || 0;
        state.avail = parseInt(tile.dataset.avail) || 0;
        clampQty();
        updateTotal();
    });
});

// ---- qty stepper ----
const qtyInput = document.getElementById("qty");
function clampQty() {
    let q = parseInt(qtyInput.value) || 1;
    if (q < 1) q = 1;
    if (state.type === "visitor" && state.avail > 0 && q > state.avail) q = state.avail;
    qtyInput.value = q;
    state.qty = q;
}
document.getElementById("qtyPlus").addEventListener("click", () => { qtyInput.value = (parseInt(qtyInput.value) || 0) + 1; clampQty(); updateTotal(); });
document.getElementById("qtyMinus").addEventListener("click", () => { qtyInput.value = (parseInt(qtyInput.value) || 2) - 1; clampQty(); updateTotal(); });
qtyInput.addEventListener("input", () => { clampQty(); updateTotal(); });

// ---- type segmented ----
document.querySelectorAll("#typeSeg button").forEach(b => b.addEventListener("click", () => {
    document.querySelectorAll("#typeSeg button").forEach(x => x.classList.remove("active"));
    b.classList.add("active");
    state.type = b.dataset.type;
    updateTotal();
}));

// ---- create ----
const createBtn = document.getElementById("createBtn");
createBtn.addEventListener("click", async () => {
    if (state.type === "visitor" && !state.date) { toast(TF.i18n.err_no_date, "err"); return; }

    const body = new URLSearchParams({
        ajax: "create",
        csrf_token: CSRF_TOKEN,
        type: state.type,
        paid: document.getElementById("paid").checked ? "true" : "false",
        valid_date: state.type === "visitor" ? state.date : "",
        qty: String(state.qty),
        firstname: document.getElementById("firstname").value.trim(),
        lastname: document.getElementById("lastname").value.trim(),
        email: document.getElementById("email").value.trim(),
    });

    createBtn.disabled = true;
    document.getElementById("createBtnLabel").textContent = TF.i18n.creating;
    try {
        const res = await fetch("", { method: "POST", headers: { "X-CSRF-Token": CSRF_TOKEN }, body });
        const data = await res.json();
        if (data.status === "success" && data.tids.length) {
            showResult(data.tids);
            printTickets(data.tids);
            // local availability decrement
            if (state.type === "visitor") {
                const tile = document.querySelector('.tf-date[data-date="' + state.date + '"]');
                if (tile) {
                    const left = Math.max(0, (parseInt(tile.dataset.avail) || 0) - data.tids.length);
                    tile.dataset.avail = left; state.avail = left;
                    const lbl = tile.querySelector(".d-left");
                    lbl.textContent = left > 0 ? left + " <?php echo $L["left"]; ?>" : "<?php echo $L["sold_out"]; ?>";
                    lbl.className = "d-left " + (left <= 0 ? "out" : (left <= 10 ? "low" : ""));
                    if (left <= 0) { tile.classList.add("disabled"); tile.disabled = true; }
                }
            }
        } else {
            toast(TF.i18n.err_generic + (data.message ? ": " + data.message : ""), "err");
        }
    } catch (e) {
        toast(TF.i18n.err_generic, "err");
    } finally {
        createBtn.disabled = false;
        document.getElementById("createBtnLabel").innerHTML = '<?php echo $L["create_print"]; ?>';
    }
});

// print one or many tickets as a single same-origin PDF, straight to the
// print dialog via a hidden iframe (no new tabs, all pages in one job).
function printTickets(tids) {
    if (!tids || !tids.length) return;
    const src = "?print=" + encodeURIComponent(tids.join(","));
    const old = document.getElementById("tf-printframe");
    if (old) old.remove();
    const f = document.createElement("iframe");
    f.id = "tf-printframe";
    f.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0;";
    f.src = src;
    f.onload = () => {
        try { f.contentWindow.focus(); f.contentWindow.print(); }
        catch (e) { window.open(src, "_blank"); }
    };
    document.body.appendChild(f);
}

let lastTids = [];
function showResult(tids) {
    lastTids = tids;
    document.getElementById("resultTitle").textContent = TF.i18n.success_title + " · " + tids.length + "×";
    const list = document.getElementById("resultList");
    list.innerHTML = "";
    tids.forEach(tid => {
        const row = document.createElement("div");
        row.className = "flex items-center justify-between";
        row.innerHTML = '<span class="tf-tid">' + tid + '</span>';
        const a = document.createElement("button");
        a.type = "button"; a.className = "avo-link"; a.textContent = TF.i18n.print;
        a.style.background = "none"; a.style.border = "none"; a.style.cursor = "pointer";
        a.addEventListener("click", () => printTickets([tid]));
        row.appendChild(a);
        list.appendChild(row);
    });
    document.getElementById("tf-result").style.display = "block";
}

document.getElementById("printAllBtn").addEventListener("click", () => printTickets(lastTids));
document.getElementById("newSaleBtn").addEventListener("click", () => {
    document.getElementById("tf-result").style.display = "none";
    qtyInput.value = 1; clampQty(); updateTotal();
    document.getElementById("firstname").value = "";
    document.getElementById("lastname").value = "";
    document.getElementById("email").value = "";
});

// auto-select single date
const tiles = document.querySelectorAll(".tf-date:not(.disabled)");
if (tiles.length === 1) tiles[0].click();

<?php if ($flash): ?>toast(<?php echo json_encode($flash[1]); ?>, <?php echo json_encode($flash[0]); ?>);<?php endif; ?>
</script>

<?php
$orgName = $shows['orga_name'] ?? '';
include __DIR__ . '/../../partials/footer.php';
?>
</body>
</html>

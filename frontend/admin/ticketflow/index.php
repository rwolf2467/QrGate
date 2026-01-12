<?php
require_once "../../config.php";

// Check if user is authorized to access ticketflow
if (!isset($_SESSION["admin"]) && !isset($_SESSION["ticketflow_access"])) {
    header("Location: ../login.php?redirect=ticketflow");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["language"])) {
    $_SESSION["language"] = $_POST["language"];
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}
$shows = getShows();

$languages = [
    "en" => [
        "flag" => "🇬🇧",
        "name" => "English",
        "title_description" =>
            "This is the Cash register web-app for " .
            $shows["orga_name"] .
            " .",
        "section_title_edit" => "EDIT TICKET",
        "title_edit" => "Edit a existing ticket.",
        "title_create" => "Create a new ticket.",
        "first_name" => "First Name",
        "last_name" => "Last Name",
        "email" => "Email",
        "number_of_tickets" => "Number of Tickets",
        "valid_date" => "Valid Date",
        "paid" => "Paid",
        "used" => "Usable",
        "type" => "Type",
        "create_ticket" => "Create Ticket",
        "cancel" => "Cancel",
        "save" => "Save",
        "send_request" => "Send Request",
        "please_enter_ticket_id" => "Please enter a ticket ID to edit a ticket",
        "ticket_id" => "Ticket ID",
        "create_new" => "Create New",
        "visitor" => "Visitor",
        "admin" => "Admin",
        "vip" => "VIP",
        "true" => "True",
        "false" => "False",
    ],
    "de" => [
        "flag" => "🇩🇪",
        "name" => "Deutsch",
        "title_description" =>
            "Dies ist die Cash register web-app für " .
            $shows["orga_name"] .
            " .",
        "section_title_edit" => "TICKET EDITIEREN",
        "title_edit" => "Einen vorhandenes Ticket bearbeiten.",
        "title_create" => "Ein neues Ticket erstellen.",
        "first_name" => "Vorname",
        "last_name" => "Nachname",
        "email" => "Email",
        "number_of_tickets" => "Anzahl der Tickets",
        "valid_date" => "Gültigkeitsdatum",
        "paid" => "Bezahlt",
        "used" => "Benutzbar",
        "type" => "Typ",
        "create_ticket" => "Ticket erstellen",
        "cancel" => "Abbrechen",
        "save" => "Speichern",
        "send_request" => "Anfrage senden",
        "please_enter_ticket_id" =>
            "Bitte geben Sie eine Ticket ID ein, um ein Ticket zu bearbeiten",
        "ticket_id" => "Ticket ID",
        "create_new" => "Neu erstellen",
        "visitor" => "Besucher",
        "admin" => "Admin",
        "vip" => "VIP",
        "true" => "Wahr",
        "false" => "Falsch",
    ],
];

$current_language = $_SESSION["language"] ?? "en";
?>
<?php
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

// --- Edit logic (unchanged structure) ---
$firstname = "";
$lastname = "";
$type = "";
$paid = "false";
$valid_date = "";
$valid = "false";
$ticket_id = "";

if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST["ticket_id"]) &&
    !isset($_POST["action"])
) {
    $ticket_id = $_POST["ticket_id"];
    [$http_code, $ticket_data] = call_api("api/ticket/get", ["tid" => $ticket_id], "POST");
    if ($http_code === 200 && isset($ticket_data["data"])) {
        $firstname = $ticket_data["data"]["first_name"] ?? "";
        $lastname = $ticket_data["data"]["last_name"] ?? "";
        $type = $ticket_data["data"]["type"] ?? "";
        $paid = isset($ticket_data["data"]["paid"])
            ? ($ticket_data["data"]["paid"] ? "true" : "false")
            : "false";
        $valid_date = $ticket_data["data"]["valid_date"] ?? "";
        $valid = isset($ticket_data["data"]["valid"])
            ? ($ticket_data["data"]["valid"] ? "true" : "false")
            : "false";
    }
}

// --- Save edited ticket ---
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "save_ticket"
) {
    $ticket_id = $_POST["ticket_id"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $type = $_POST["type"];
    $paid = $_POST["paid"] === "true";
    $valid_date = $_POST["valid_date"];
    $valid = $_POST["valid"] === "true";

    $data = [
        "tid" => $ticket_id,
        "first_name" => $firstname,
        "last_name" => $lastname,
        "type" => $type,
        "paid" => $paid,
        "valid" => $valid,
        "valid_date" => $valid_date,
    ];

    [$http_code, $response_data] = call_api("api/ticket/edit", $data);

    if ($http_code === 200) {
        echo "<script>alert('Ticket erfolgreich gespeichert!');</script>";
        // Reset form
        $ticket_id = "";
        $firstname = "";
        $lastname = "";
        $type = "";
        $paid = "false";
        $valid_date = "";
        $valid = "false";
    } else {
        $error_message = $response_data["message"] ?? "Unbekannter Fehler";
        echo "<script>alert('Fehler beim Speichern des Tickets: $error_message');</script>";
    }
}

// --- Create new ticket (OVERHAULED for speed & print) ---
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "create_ticket"
) {
    $email = $_POST["email"] ?? "";
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $type = $_POST["type"];
    $paid = $_POST["paid"] === "true";
    $valid_date = $_POST["valid_date"];
    $tickets = (int) ($_POST["tickets"] ?? 1);

    $data = [
        "email" => $email ?: null,
        "first_name" => $firstname,
        "last_name" => $lastname,
        "type" => $type,
        "paid" => $paid,
        "valid_date" => $valid_date,
        "tickets" => $tickets,
        // Note: 'tid' and 'valid' are now handled by backend automatically
    ];

    [$http_code, $response_data] = call_api("api/ticketflow/create", $data);

    if ($http_code === 200 && !empty($response_data["tid"])) {
        $tid = $response_data["tid"];
        // ✅ OPEN PDF IN NEW TAB FOR PRINTING
        echo "<script>
            alert('Ticket erfolgreich erstellt!');
            window.open('" . API_BASE_URL . "codes/pdf?tid=" . urlencode($tid) . "', '_blank');
        </script>";
    } else {
        $error_message = $response_data["message"] ?? "Unbekannter Fehler";
        echo "<script>alert('Fehler beim Erstellen des Tickets: $error_message');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Flow</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: var(--dark);
            color: white;
            font-family: 'Quicksand', sans-serif;
        }

        .card {
            background-color: var(--darker);
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--primary);
        }

        h3 {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>

<body class="bg-dark min-h-screen">
    <div id="currentDateTime"></div>

    <!-- Header -->
    <header class="bg-darker border-b border-border px-6 py-4 flex justify-between items-center">
        <div class="flex items-center">
            <i class="fas fa-qrcode text-2xl text-purple-400 mr-3"></i>
            <h1 class="text-2xl font-bold">TicketFlow</h1>
        </div>
        <div class="flex items-center gap-4">
            <div class="language-selector">
                <form method="post" id="langForm">
                    <select name="language" onchange="this.form.submit()" class="select w-[180px]">
                        <?php foreach ($languages as $code => $lang): ?>
                            <option value="<?php echo $code; ?>" <?php echo $current_language ==
                                   $code
                                   ? "selected"
                                   : ""; ?>>
                                <?php echo $lang[
                                    "flag"
                                ]; ?>     <?php echo $lang["name"]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <a href="../logout.php" class="btn-destructive">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-log-out-icon lucide-log-out">
                    <path d="m16 17 5-5-5-5" />
                    <path d="M21 12H9" />
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                </svg> Logout
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold mb-2">TicketFlow</h2>
            <p class="text-gray-400"><?php echo $languages[$current_language][
                "title_description"
            ]; ?></p>
        </div>

        <div class="card">
            <header>
                <h3 class="text-xl font-bold mb-4"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-user-pen-icon lucide-user-pen">
                        <path d="M11.5 15H7a4 4 0 0 0-4 4v2" />
                        <path
                            d="M21.378 16.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" />
                        <circle cx="10" cy="7" r="4" />
                    </svg><?php echo $languages[
                        $current_language
                    ]["title_edit"]; ?></h3>
            </header>
            <section>
                <form action="" method="POST" class="form grid gap-6">
                    <input type="text" placeholder="Enter Ticket ID Here" id="idinput" name="ticket_id"
                        value="" required>
                    <button type="submit" class="btn-primary mt-2">
                        <i class="fas fa-search mr-2"></i> <?php echo $languages[
                            $current_language
                        ]["send_request"]; ?>
                    </button>
                </form>
                <br>
                <br>
                <?php if (!empty($ticket_id)): ?>
                    <form action="" method="POST" id="saveForm" class="form grid gap-6">
                        <input type="hidden" name="action" value="save_ticket">
                        <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars(
                            $ticket_id
                        ); ?>">

                        <div id="ticketinfo" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="firstname" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["first_name"]; ?></label>
                                <input type="text" name="firstname" value="<?php echo htmlspecialchars(
                                    $firstname
                                ); ?>">
                            </div>
                            <div>
                                <label for="lastname" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["last_name"]; ?></label>
                                <input type="text" name="lastname" value="<?php echo htmlspecialchars(
                                    $lastname
                                ); ?>">
                            </div>
                            <div>
                                <label for="type" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["type"]; ?></label>
                                <select name="type">
                                    <option value="visitor" <?php echo $type ===
                                        "visitor"
                                        ? "selected"
                                        : ""; ?>>Visitor
                                    </option>
                                    <option value="admin" <?php echo $type ===
                                        "admin"
                                        ? "selected"
                                        : ""; ?>>Admin</option>
                                    <option value="vip" <?php echo $type ===
                                        "vip"
                                        ? "selected"
                                        : ""; ?>>VIP</option>
                                </select>
                            </div>
                            <div>
                                <label for="paid" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["paid"]; ?></label>
                                <select name="paid">
                                    <option value="true" <?php echo $paid ===
                                        "true"
                                        ? "selected"
                                        : ""; ?>>True</option>
                                    <option value="false" <?php echo $paid ===
                                        "false"
                                        ? "selected"
                                        : ""; ?>>False</option>
                                </select>
                            </div>
                            <div>
                                <label for="valid" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["used"]; ?></label>
                                <select name="valid">
                                    <option value="true" <?php echo $valid ===
                                        "true"
                                        ? "selected"
                                        : ""; ?>>True</option>
                                    <option value="false" <?php echo $valid ===
                                        "false"
                                        ? "selected"
                                        : ""; ?>>False</option>
                                </select>
                            </div>
                            <div>
                                <label for="valid_date" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["valid_date"]; ?></label>
                                <input type="date" name="valid_date" value="<?php echo htmlspecialchars(
                                    $valid_date
                                ); ?>">
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-save-icon lucide-save">
                                    <path
                                        d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                    <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
                                    <path d="M7 3v4a1 1 0 0 0 1 1h7" />
                                </svg> <?php echo $languages[$current_language][
                                    "save"
                                ]; ?>
                            </button>
                            <button type="button" onclick="window.location.href='<?php echo $_SERVER[
                                "PHP_SELF"
                            ]; ?>'" class="btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg> <?php echo $languages[$current_language][
                                    "cancel"
                                ]; ?>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div id="ticketinfo" class="text-gray-400">
                        <p><?php echo $languages[$current_language][
                            "please_enter_ticket_id"
                        ]; ?></p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <br>
        <br>
        <div class="card">
            <header>
                <h3 class="text-xl font-bold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-ticket-plus-icon lucide-ticket-plus">
                        <path
                            d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                        <path d="M9 12h6" />
                        <path d="M12 9v6" />
                    </svg>
                    <span><?php echo $languages[$current_language][
                        "title_create"
                    ]; ?></span>
                </h3>
            </header>
            <section>
                <button id="createNewButton" onclick="showCreateTicketForm()" class="btn-primary mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-plus-icon lucide-plus">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg> <?php echo $languages[
                        $current_language
                    ]["create_new"]; ?>
                </button>
                <div id="createTicketForm" style="display: none;">
                    <form action="" method="POST" id="createForm" class="form grid gap-6">
                        <input type="hidden" name="action" value="create_ticket">
                        <!-- Removed manual tid input -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- tid field removed -->
                            <div>
                                <label for="email" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["email"]; ?></label>
                                <input type="email" name="email" placeholder="Enter Email Here">
                            </div>
                            <div>
                                <label for="firstname" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["first_name"]; ?></label>
                                <input type="text" name="firstname" required>
                            </div>
                            <div>
                                <label for="lastname" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["last_name"]; ?></label>
                                <input type="text" name="lastname" required>
                            </div>
                            <div>
                                <label for="tickets" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["number_of_tickets"]; ?></label>
                                <input type="number" name="tickets" value="1" min="1" required>
                            </div>
                            <div>
                                <label for="type" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["type"]; ?></label>
                                <select name="type" id="type" required>
                                    <option value="visitor"><?php echo $languages[
                                        $current_language
                                    ]["visitor"]; ?></option>
                                    <option value="admin"><?php echo $languages[
                                        $current_language
                                    ]["admin"]; ?></option>
                                    <option value="vip"><?php echo $languages[
                                        $current_language
                                    ]["vip"]; ?></option>
                                </select>
                            </div>
                            <div>
                                <label for="paid" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["paid"]; ?></label>
                                <select name="paid" required>
                                    <option value="true"><?php echo $languages[
                                        $current_language
                                    ]["true"]; ?></option>
                                    <option value="false" selected><?php echo $languages[
                                        $current_language
                                    ]["false"]; ?></option>
                                </select>
                            </div>
                            <div>
                                <label for="valid_date" class="block mb-2 text-sm"><?php echo $languages[
                                    $current_language
                                ]["valid_date"]; ?></label>
                                <input type="date" name="valid_date">
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <button type="submit" class="btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-printer-icon lucide-printer">
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                    <path d="M6 9V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
                                    <rect x="6" y="14" width="12" height="8" rx="1" />
                                </svg>
                                <?php echo $languages[$current_language][
                                    "create_ticket"
                                ]; ?>
                            </button>
                            <button type="button" onclick="showCreateTicketForm()" class="btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg> <?php echo $languages[
                                    $current_language
                                ]["cancel"]; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script>
        function changeLanguage(language) {
            document.getElementById('langForm').submit();
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

        function showCreateTicketForm() {
            var form = document.getElementById('createTicketForm');
            var button = document.getElementById('createNewButton');
            if (form.style.display === "none") {
                form.style.display = "block";
                button.style.display = "none";
            } else {
                form.style.display = "none";
                button.style.display = "block";
            }
        }
    </script>
</body>
</html>
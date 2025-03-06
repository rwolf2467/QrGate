<?php
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$shows = getShows();

$languages = [
    'en' => [
        'flag' => '🇬🇧',
        'name' => 'English',
        'title_description' => 'This is the Cash register web-app for ' . $shows['orga_name'] . ' .',
        'section_title_edit' => 'EDIT TICKET',
        'title_edit' => 'Edit a existing ticket.',
        'title_create' => 'Create a new ticket.',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'number_of_tickets' => 'Number of Tickets',
        'valid_date' => 'Valid Date',
        'paid' => 'Paid',
        'used' => 'Usable',
        'type' => 'Type',
        'create_ticket' => 'Create Ticket',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'send_request' => 'Send Request',
        'please_enter_ticket_id' => 'Please enter a ticket ID to edit a ticket',
        'ticket_id' => 'Ticket ID',
        'create_new' => 'Create New',
        'visitor' => 'Visitor',
        'admin' => 'Admin',
        'vip' => 'VIP',
        'true' => 'True',
        'false' => 'False',
    ],
    'de' => [
        'flag' => '🇩🇪',
        'name' => 'Deutsch',
        'title_description' => 'Dies ist die Cash register web-app für ' . $shows['orga_name'] . ' .',
        'section_title_edit' => 'TICKET EDITIEREN',
        'title_edit' => 'Einen vorhandenes Ticket bearbeiten.',
        'title_create' => 'Ein neues Ticket erstellen.',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'Email',
        'number_of_tickets' => 'Anzahl der Tickets',
        'valid_date' => 'Gültigkeitsdatum',
        'paid' => 'Bezahlt',
        'used' => 'Benutzbar',
        'type' => 'Typ',
        'create_ticket' => 'Ticket erstellen',
        'cancel' => 'Abbrechen',
        'save' => 'Speichern',
        'send_request' => 'Anfrage senden',
        'please_enter_ticket_id' => 'Bitte geben Sie eine Ticket ID ein, um ein Ticket zu bearbeiten',
        'ticket_id' => 'Ticket ID',
        'create_new' => 'Neu erstellen',
        'visitor' => 'Besucher',
        'admin' => 'Admin',
        'vip' => 'VIP',
        'true' => 'Wahr',
        'false' => 'Falsch',
    ]
];

$current_language = $_SESSION['language'] ?? 'en';
?>
<?php
function titleedit($id)
{
    $baseurl = API_BASE_URL;
    $ch = curl_init($baseurl . "api/ticket/get");

    $data = json_encode(["tid" => $id]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$firstname = '';
$lastname = '';
$type = '';
$paid = 'false';
$valid_date = '';
$used = 'false';
$ticket_id = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticket_id']) && !isset($_POST['action'])) {
    $ticket_id = $_POST['ticket_id'];
    $response = titleedit($ticket_id);
    $ticket_data = json_decode($response, true);

    if (isset($ticket_data['data'])) {
        $firstname = $ticket_data['data']['first_name'] ?? '';
        $lastname = $ticket_data['data']['last_name'] ?? '';
        $type = $ticket_data['data']['type'] ?? '';
        $paid = isset($ticket_data['data']['paid']) ? ($ticket_data['data']['paid'] ? 'true' : 'false') : 'false';
        $valid_date = $ticket_data['data']['valid_date'] ?? '';
        $valid = isset($ticket_data['data']['valid']) ? ($ticket_data['data']['valid'] ? 'true' : 'false') : 'false';
    }
} else {
    $ticket_id = '';
}

$firstname = $firstname ?? '';
$lastname = $lastname ?? '';
$type = $type ?? '';
$paid = $paid ?? 'false';
$valid_date = $valid_date ?? '';
$valid = $valid ?? 'false';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'save_ticket') {
    $ticket_id = $_POST['ticket_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $type = $_POST['type'];
    $paid = $_POST['paid'] === 'true';
    $valid_date = $_POST['valid_date'];
    $valid = $_POST['valid'] === 'true';

    $data = [
        'tid' => $ticket_id,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'type' => $type,
        'paid' => $paid,
        'valid' => $valid,
        'valid_date' => $valid_date
    ];

    $baseurl = API_BASE_URL;
    $ch = curl_init($baseurl . "api/ticket/edit");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response, true);
    echo $response_data['message'];

    if ($http_code === 200) {
        echo "<script>alert('Ticket erfolgreich gespeichert!');</script>";


        $ticket_id = '';
        $firstname = '';
        $lastname = '';
        $type = '';
        $paid = 'false';
        $valid_date = '';
        $valid = 'false';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = $response_data['message'] ?? 'Unbekannter Fehler';
        echo "<script>alert('Fehler beim Speichern des Tickets: $error_message');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    $ticket_id = $_POST['tid'] ?? '';
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $type = $_POST['type'];
    $paid = $_POST['paid'] === 'true';
    $valid_date = $_POST['valid_date'];
    $tickets = (int) ($_POST['tickets'] ?? 0);

    $data = [
        'tid' => $ticket_id,
        'email' => $email,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'type' => $type,
        'paid' => $paid,
        'valid' => $used,
        'valid_date' => $valid_date,
        'tickets' => $tickets
    ];

    $baseurl = API_BASE_URL;
    $ch = curl_init($baseurl . "api/ticketflow/create");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response, true);
    echo $response_data['message'];

    if ($http_code === 200) {
        echo "<script>alert('Ticket erfolgreich erstellt!');</script>";


        $ticket_id = '';
        $email = '';
        $firstname = '';
        $lastname = '';
        $type = '';
        $paid = 'false';
        $valid_date = '';


        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = $response_data['message'] ?? 'Unbekannter Fehler';
        echo "<script>alert('Fehler beim Erstellen des Tickets: $error_message');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
    <title>Ticket Flow</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

        :root {
            --background-color: #0a0a0a;
            --card-background: #111111;
            --text-color: #ffffff;
            --text-secondary: #888888;
            --border-color: #222222;
            --font-family: "Quicksand", sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            background-color: #0a0a0a;
            background-size: 31px 31px;
            background-image: repeating-linear-gradient(45deg, #222222 0, #222222 3.1px, #0a0a0a 0, #0a0a0a 50%);
            background-attachment: fixed;
        }

        #gradientbar {
            height: 14px;
            background: linear-gradient(90deg, #9333ea, #ec4899, #eab308);
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1000;
            background-size: 200% 200%;
            animation: gradient 10s ease infinite;
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

        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding-top: 14px;
        }

        .hero-content {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scroll-indicator {
            position: absolute;
            top: 90vh;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
            cursor: pointer;
            opacity: 0.7;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0) translateX(-50%);
            }

            40% {
                transform: translateY(-20px) translateX(-50%);
            }

            60% {
                transform: translateY(-10px) translateX(-50%);
            }
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 24px;
            line-height: 1.2;
        }

        .highlight-purple {
            background-color: rgba(147, 51, 234, 0.2);
            padding: 2px 8px;
            color: rgb(216, 180, 254);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .highlight-purple:hover {
            background-color: rgba(147, 51, 234, 0.4);
            transform: translateY(-2px);
            cursor: pointer;
        }

        .highlight-purple:active {
            background-color: rgba(147, 51, 234, 0.6);
        }

        .highlight-yellow {
            background-color: rgba(234, 179, 8, 0.2);
            padding: 2px 8px;
            color: rgb(253, 224, 71);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .highlight-yellow:hover {
            background-color: rgba(234, 179, 8, 0.4);
            transform: translateY(-2px);
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 1.3rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .section-header {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 1.1rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .section-title::before {
            content: '';
            width: 96px;
            height: 2px;
            background: linear-gradient(90deg, #9333ea, #ec4899);
            display: block;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }

        .project-card {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            transition: transform 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            transform: translateY(-4px);
        }

        .project-title {
            font-size: 1.25rem;
            margin-bottom: 12px;
        }

        .project-description {
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .project-list {
            color: var(--text-secondary);
            margin-bottom: 16px;
            margin-left: 2%;
            font-size: 0.95rem;
        }

        .project-list-2 {
            color: var(--text-secondary);
            margin-bottom: 16px;
            margin-left: 4%;
            font-size: 0.95rem;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .tag {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .project-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        main {
            position: relative;
            z-index: 2;
        }

        .project-profile {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .project-profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
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

        #currentDateTime {
            text-align: center;
            font-size: 1.5rem;
            color: var(--text-color);
            margin-top: 10px;
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
        }

        .inputs {
            background-color: rgb(17, 17, 17);
            border: 1px solid #222222;
            color: black;
            outline: none;
            color: whitesmoke;
            padding: 8px;
            border-radius: 4px;
        }

        .inputs:focus {
            background-color: rgb(17, 17, 17);
            border: 1px solid #9333ea;
            color: whitesmoke;
            box-shadow: 0 0 5px #9333ea;
        }

        .inputs:active {
            background-color: rgb(17, 17, 17);
            border: 1px solid #9333ea;
            color: whitesmoke;
        }

        button {
            background: linear-gradient(90deg, #9333ea, #ec4899);
            transition: opacity 0.2s;
            color: whitesmoke;
            border-radius: 4px;
            padding: 8px;
            min-width: 80px;
            max-width: 150px;
            margin-top: 10px;
        }

        button:hover {
            opacity: 0.9;
            cursor: pointer;
        }

        button:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <div id="gradientbar"></div>
    <div id="currentDateTime"></div>
    <div class="language-selector">
        <form method="post" id="langForm">
            <select name="language" onchange="this.form.submit()">
                <?php foreach ($languages as $code => $lang): ?>
                    <option value="<?php echo $code; ?>"
                        <?php echo ($current_language == $code) ? 'selected' : ''; ?>>
                        <span class="flag"><?php echo $lang['flag']; ?></span>
                        <?php echo $lang['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="container">
        <main>

            <section style="margin-top: 15vh" id="about">
                <div>
                    <h2>TicketFlow</h2>
                    <h3><?php echo $languages[$current_language]['title_description']; ?></h3>
                </div>
                <br>
                <div class="section-header">
                    <div class="section-title"><?php echo $languages[$current_language]['section_title_edit']; ?></div>
                </div>

                <div class="project-card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-file-pen"></i> <span><?php echo $languages[$current_language]['title_edit']; ?></span>
                    </h3>
                    <form action="" method="POST">
                        <input class="inputs" type="text" placeholder="Enter Ticket ID Here" id="idinput" name="ticket_id" required>
                        <button type="submit"><?php echo $languages[$current_language]['send_request']; ?></button>
                    </form>
                    <br>
                    <?php if (!empty($ticket_id)): ?>
                        <form action="" method="POST" id="saveForm">
                            <input type="hidden" name="action" value="save_ticket">
                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">

                            <div id="ticketinfo">
                                <label for="firstname"><?php echo $languages[$current_language]['first_name']; ?></label> <br>
                                <input class="inputs" type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>"> <br>
                                <label for="lastname"><?php echo $languages[$current_language]['last_name']; ?></label> <br>
                                <input class="inputs" type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>"> <br>
                                <label for="type"><?php echo $languages[$current_language]['type']; ?></label> <br>
                                <select class="inputs" name="type">
                                    <option value="visitor" <?php echo ($type === 'visitor') ? 'selected' : ''; ?>>Visitor</option>
                                    <option value="admin" <?php echo ($type === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="vip" <?php echo ($type === 'vip') ? 'selected' : ''; ?>>VIP</option>
                                </select> <br>
                                <label for="paid"><?php echo $languages[$current_language]['paid']; ?></label> <br>
                                <select class="inputs" name="paid">
                                    <option value="true" <?php echo ($paid === 'true') ? 'selected' : ''; ?>>True</option>
                                    <option value="false" <?php echo ($paid === 'false') ? 'selected' : ''; ?>>False</option>
                                </select> <br>
                                <label for="valid"><?php echo $languages[$current_language]['used']; ?></label> <br>
                                <select class="inputs" name="valid">
                                    <option value="true" <?php echo ($valid === 'true') ? 'selected' : ''; ?>>True</option>
                                    <option value="false" <?php echo ($valid === 'false') ? 'selected' : ''; ?>>False</option>
                                </select> <br>
                                <label for="valid_date"><?php echo $languages[$current_language]['valid_date']; ?></label> <br>
                                <input class="inputs" type="date" name="valid_date" value="<?php echo htmlspecialchars($valid_date); ?>"> <br>
                            </div>
                            <br>
                            <button type="submit"><?php echo $languages[$current_language]['save']; ?></button>
                            <button type="button" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'"><?php echo $languages[$current_language]['cancel']; ?></button>
                        </form>
                    <?php else: ?>
                        <div id="ticketinfo">
                            <p><?php echo $languages[$current_language]['please_enter_ticket_id']; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <br>
                <br>
                <div class="project-card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-plus"></i> <span><?php echo $languages[$current_language]['title_create']; ?></span>
                    </h3>
                    <button id="createNewButton" onclick="showCreateTicketForm()"><?php echo $languages[$current_language]['create_new']; ?></button>
                    <div id="createTicketForm" style="display: none;">
                        <form action="" method="POST" id="createForm">
                            <input type="hidden" name="action" value="create_ticket">
                            <label for="tid"><?php echo $languages[$current_language]['ticket_id']; ?></label> <br>
                            <input class="inputs" type="text" name="tid" placeholder="Enter Ticket ID Here"> <br>
                            <label for="email"><?php echo $languages[$current_language]['email']; ?></label> <br>
                            <input class="inputs" type="email" name="email" placeholder="Enter Email Here"> <br>
                            <label for="firstname"><?php echo $languages[$current_language]['first_name']; ?></label> <br>
                            <input class="inputs" type="text" name="firstname" required> <br>
                            <label for="lastname"><?php echo $languages[$current_language]['last_name']; ?></label> <br>
                            <input class="inputs" type="text" name="lastname" required> <br>
                            <label for="tickets"><?php echo $languages[$current_language]['number_of_tickets']; ?></label> <br>
                            <input class="inputs" type="number" name="tickets" value="1" required> <br>
                            <label for="type"><?php echo $languages[$current_language]['type']; ?></label> <br>
                            <select class="inputs" name="type" id="type" required>
                                <option value="visitor"><?php echo $languages[$current_language]['visitor']; ?></option>
                                <option value="admin"><?php echo $languages[$current_language]['admin']; ?></option>
                                <option value="vip"><?php echo $languages[$current_language]['vip']; ?></option>
                            </select> <br>
                            <label for="paid"><?php echo $languages[$current_language]['paid']; ?></label> <br>
                            <select class="inputs" name="paid" required>
                                <option value="true"><?php echo $languages[$current_language]['true']; ?></option>
                                <option value="false"><?php echo $languages[$current_language]['false']; ?></option>
                            </select> <br>
                            <label for="valid_date"><?php echo $languages[$current_language]['valid_date']; ?></label> <br>
                            <input class="inputs" type="date" name="valid_date"> <br>
                            <button type="submit"><?php echo $languages[$current_language]['create_ticket']; ?></button>
                            <button type="button" onclick="showCreateTicketForm()"><?php echo $languages[$current_language]['cancel']; ?></button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script>
        function changeLanguage(language) {
            document.getElementById('langForm').submit();
        }

        function formatDateTime(date) {
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
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
<?php
require_once '../../config.php';

// Check if user is authorized to access ticketflow
if (!isset($_SESSION['admin']) && !isset($_SESSION['ticketflow_access'])) {
    header('Location: ../login.php?redirect=ticketflow');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$shows = getShows();

$ticket_id_value = date('Y-dm-');

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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #9333ea;
            --secondary: #ec4899;
            --dark: #0a0a0a;
            --darker: #111111;
            --border: #222222;
        }
        
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
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: opacity 0.2s;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .input-field {
            background-color: var(--dark);
            border: 1px solid var(--border);
            color: white;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
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
        
        .project-card {
            background: var(--darker);
            border: 1px solid var(--border);
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
        
        .language-selector select {
            background-color: var(--darker);
            color: white;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 8px;
            cursor: pointer;
        }
        
        #currentDateTime {
            text-align: center;
            font-size: 1.5rem;
            color: white;
            margin-top: 10px;
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
        }
        
        .inputs {
            background-color: var(--dark);
            border: 1px solid var(--border);
            color: white;
            outline: none;
            padding: 8px;
            border-radius: 4px;
            width: 100%;
        }
        
        .inputs:focus {
            background-color: var(--dark);
            border: 1px solid #9333ea;
            color: white;
            box-shadow: 0 0 5px #9333ea;
        }
        
        button {
            background: linear-gradient(90deg, #9333ea, #ec4899);
            transition: opacity 0.2s;
            color: white;
            border-radius: 4px;
            padding: 8px;
            min-width: 80px;
            max-width: 150px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        button:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
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
                    <select name="language" onchange="this.form.submit()" class="input-field">
                        <?php foreach ($languages as $code => $lang): ?>
                            <option value="<?php echo $code; ?>"
                                <?php echo ($current_language == $code) ? 'selected' : ''; ?>>
                                <?php echo $lang['flag']; ?> <?php echo $lang['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <a href="../logout.php" class="btn-primary">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold mb-2">TicketFlow</h2>
            <p class="text-gray-400"><?php echo $languages[$current_language]['title_description']; ?></p>
        </div>
        
        <div class="section-header mb-8">
            <div class="section-title"><?php echo $languages[$current_language]['section_title_edit']; ?></div>
        </div>

                <div class="project-card card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-file-pen"></i> <span><?php echo $languages[$current_language]['title_edit']; ?></span>
                    </h3>
                    <form action="" method="POST" class="mb-4">
                        <input class="inputs" type="text" placeholder="Enter Ticket ID Here" id="idinput" name="ticket_id" value="<?php echo $ticket_id_value?>" required>
                        <button type="submit" class="btn-primary mt-2">
                            <i class="fas fa-search mr-2"></i> <?php echo $languages[$current_language]['send_request']; ?>
                        </button>
                    </form>
                    
                    <?php if (!empty($ticket_id)): ?>
                        <form action="" method="POST" id="saveForm">
                            <input type="hidden" name="action" value="save_ticket">
                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">

                            <div id="ticketinfo" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="firstname" class="block mb-2 text-sm"><?php echo $languages[$current_language]['first_name']; ?></label>
                                    <input class="inputs" type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>">
                                </div>
                                <div>
                                    <label for="lastname" class="block mb-2 text-sm"><?php echo $languages[$current_language]['last_name']; ?></label>
                                    <input class="inputs" type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>">
                                </div>
                                <div>
                                    <label for="type" class="block mb-2 text-sm"><?php echo $languages[$current_language]['type']; ?></label>
                                    <select class="inputs" name="type">
                                        <option value="visitor" <?php echo ($type === 'visitor') ? 'selected' : ''; ?>>Visitor</option>
                                        <option value="admin" <?php echo ($type === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="vip" <?php echo ($type === 'vip') ? 'selected' : ''; ?>>VIP</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="paid" class="block mb-2 text-sm"><?php echo $languages[$current_language]['paid']; ?></label>
                                    <select class="inputs" name="paid">
                                        <option value="true" <?php echo ($paid === 'true') ? 'selected' : ''; ?>>True</option>
                                        <option value="false" <?php echo ($paid === 'false') ? 'selected' : ''; ?>>False</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="valid" class="block mb-2 text-sm"><?php echo $languages[$current_language]['used']; ?></label>
                                    <select class="inputs" name="valid">
                                        <option value="true" <?php echo ($valid === 'true') ? 'selected' : ''; ?>>True</option>
                                        <option value="false" <?php echo ($valid === 'false') ? 'selected' : ''; ?>>False</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="valid_date" class="block mb-2 text-sm"><?php echo $languages[$current_language]['valid_date']; ?></label>
                                    <input class="inputs" type="date" name="valid_date" value="<?php echo htmlspecialchars($valid_date); ?>">
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save mr-2"></i> <?php echo $languages[$current_language]['save']; ?>
                                </button>
                                <button type="button" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                                    <i class="fas fa-times mr-2"></i> <?php echo $languages[$current_language]['cancel']; ?>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div id="ticketinfo" class="text-gray-400">
                            <p><?php echo $languages[$current_language]['please_enter_ticket_id']; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <br>
                <br>
                <div class="project-card card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-plus"></i> <span><?php echo $languages[$current_language]['title_create']; ?></span>
                    </h3>
                    <button id="createNewButton" onclick="showCreateTicketForm()" class="btn-primary mb-4">
                        <i class="fas fa-plus mr-2"></i> <?php echo $languages[$current_language]['create_new']; ?>
                    </button>
                    <div id="createTicketForm" style="display: none;">
                        <form action="" method="POST" id="createForm">
                            <input type="hidden" name="action" value="create_ticket">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="tid" class="block mb-2 text-sm"><?php echo $languages[$current_language]['ticket_id']; ?></label>
                                    <input class="inputs" type="text" name="tid" placeholder="Enter Ticket ID Here">
                                </div>
                                <div>
                                    <label for="email" class="block mb-2 text-sm"><?php echo $languages[$current_language]['email']; ?></label>
                                    <input class="inputs" type="email" name="email" placeholder="Enter Email Here">
                                </div>
                                <div>
                                    <label for="firstname" class="block mb-2 text-sm"><?php echo $languages[$current_language]['first_name']; ?></label>
                                    <input class="inputs" type="text" name="firstname" required>
                                </div>
                                <div>
                                    <label for="lastname" class="block mb-2 text-sm"><?php echo $languages[$current_language]['last_name']; ?></label>
                                    <input class="inputs" type="text" name="lastname" required>
                                </div>
                                <div>
                                    <label for="tickets" class="block mb-2 text-sm"><?php echo $languages[$current_language]['number_of_tickets']; ?></label>
                                    <input class="inputs" type="number" name="tickets" value="1" required>
                                </div>
                                <div>
                                    <label for="type" class="block mb-2 text-sm"><?php echo $languages[$current_language]['type']; ?></label>
                                    <select class="inputs" name="type" id="type" required>
                                        <option value="visitor"><?php echo $languages[$current_language]['visitor']; ?></option>
                                        <option value="admin"><?php echo $languages[$current_language]['admin']; ?></option>
                                        <option value="vip"><?php echo $languages[$current_language]['vip']; ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="paid" class="block mb-2 text-sm"><?php echo $languages[$current_language]['paid']; ?></label>
                                    <select class="inputs" name="paid" required>
                                        <option value="true"><?php echo $languages[$current_language]['true']; ?></option>
                                        <option value="false"><?php echo $languages[$current_language]['false']; ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="valid_date" class="block mb-2 text-sm"><?php echo $languages[$current_language]['valid_date']; ?></label>
                                    <input class="inputs" type="date" name="valid_date">
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-plus mr-2"></i> <?php echo $languages[$current_language]['create_ticket']; ?>
                                </button>
                                <button type="button" onclick="showCreateTicketForm()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                                    <i class="fas fa-times mr-2"></i> <?php echo $languages[$current_language]['cancel']; ?>
                                </button>
                            </div>
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
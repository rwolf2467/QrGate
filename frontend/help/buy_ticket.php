<?php


require_once '../config.php';
$shows = getShows();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$languages = [
    'en' => [
        'flag' => '🇬🇧',
        'name' => 'English',
        'how_to_buy_ticket' => 'How can I buy a ticket?',
        'instructions' => 'INSTRUCTIONS',
        'book_ticket' => 'How do I book a ticket?',
        'buy_ticket' => 'How do I buy a ticket?',
        'subtitle' => 'Here you can find all the information you need to buy a ticket for the event.',
        'step1' => 'Choose your date and click on "Buy Tickets".',
        'step2' => 'Fill in the fields with the correct information.',
        'step3' => 'For payment method, select "Cash payment".',
        'step4' => 'Now click on "Book Tickets".',
        'step5' => 'You will receive your ticket via email. But you have to pay it at the day of the event at the entrance.',
        'step6' => 'Choose your desired date and click on "Buy Tickets".',
        'step7' => 'Fill in all fields with the correct information.',
        'step8' => 'For payment method, select "Online payment".',
        'step9' => 'Now you have to choose whether you want to pay with a debit/credit card or with PayPal. Click on the appropriate option.',
        'step10' => 'Fill in all the required fields correctly.',
        'step11' => 'Now click on "Pay". And wait for the payment to be completed.',
        'step12' => 'You will receive your ticket via email.',
    ],
    'de' => [
        'flag' => '🇩🇪',
        'name' => 'Deutsch',
        'how_to_buy_ticket' => 'Wie kann ich ein Ticket kaufen?',
        'instructions' => 'ANLEITUNGEN',
        'book_ticket' => 'Wie buche ich ein Ticket?',
        'buy_ticket' => 'Wie kaufe ich ein Ticket?',
        'subtitle' => 'Hier findest du alle Informationen, die du benötigst, um ein Ticket für die Veranstaltung zu kaufen.',
        'step1' => 'Wählen dein Datum aus und klick auf "Tickets kaufen".',
        'step2' => 'Füll die Felder mit den richtigen Informationen aus.',
        'step3' => 'Wähle als Zahlungsmethode "Barzahlung".',
        'step4' => 'Klick jetzt auf "Tickets buchen".',
        'step5' => 'Du erhälst dein Ticket per E-Mail. Aber du musst es am Tag der Veranstaltung am Eingang bezahlen.',
        'step6' => 'Wähle dein gewünschtes Datum aus und klick auf "Tickets kaufen".',
        'step7' => 'Füll die Felder mit den richtigen Informationen aus.',
        'step8' => 'Wähle als Zahlungsmethode "Online-Zahlung".',
        'step9' => 'Jetzt musst du wählen, ob du mit einer Debit-/Kreditkarte oder mit PayPal bezahlen möchtest. Klick auf die entsprechende Option.',
        'step10' => 'Füll die Felder mit den richtigen Informationen aus.',
        'step11' => 'Klick jetzt auf "Zahlen". Und warte bis die Zahlung abgeschlossen ist.',
        'step12' => 'Du erhälst dein Ticket per E-Mail.',
    ],
];
$current_language = $_SESSION['language'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($shows['orga_name']); ?> - Help</title>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
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
    </style>
</head>

<body>
    <div id="gradientbar"></div>
    <div class="language-selector">
        <form method="post" id="langForm">
            <select name="language" onchange="changeLanguage(this.value)">
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

        <section class="hero">
            <div class="hero-content">
                <h1>
                    <?php echo $languages[$current_language]['how_to_buy_ticket']; ?>
                </h1>
                <p class="subtitle">
                    <?php echo $languages[$current_language]['subtitle']; ?>
                </p>
            </div>
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <main>
            <section style="margin-top: 15vh" id="about">
                <div class="section-header">
                    <div class="section-title"><?php echo $languages[$current_language]['instructions']; ?></div>
                </div>

                <div class="project-card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-money-bill"></i> <span><?php echo $languages[$current_language]['book_ticket']; ?></span>
                    </h3>
                    <p class="project-description">
                        <span><?php echo $languages[$current_language]['step1']; ?></span>
                        <br>
                    <ol class="project-list">
                        <li><?php echo $languages[$current_language]['step1']; ?></li>
                        <li><?php echo $languages[$current_language]['step2']; ?></li>
                        <li><?php echo $languages[$current_language]['step3']; ?></li>
                        <li><?php echo $languages[$current_language]['step4']; ?></li>
                        <li><?php echo $languages[$current_language]['step5']; ?></li>
                    </ol>
                    </p>
                </div>
                <br>
                <br>
                <div class="project-card">
                    <div class="project-profile">
                    </div>
                    <h3 class="project-title">
                        <i class="fa-solid fa-credit-card"></i> <span><?php echo $languages[$current_language]['buy_ticket']; ?></span>
                    </h3>
                    <p class="project-description">
                        <span><?php echo $languages[$current_language]['step6']; ?></span>
                        <br>
                    <ol class="project-list">
                        <li><?php echo $languages[$current_language]['step6']; ?></li>
                        <li><?php echo $languages[$current_language]['step7']; ?></li>
                        <li><?php echo $languages[$current_language]['step8']; ?></li>
                        <li><?php echo $languages[$current_language]['step9']; ?></li>
                        <li><?php echo $languages[$current_language]['step10']; ?></li>
                        <li><?php echo $languages[$current_language]['step11']; ?></li>
                        <li><?php echo $languages[$current_language]['step12']; ?></li>
                    </ol>
                    </p>
                </div>
            </section>


            <footer style="margin-top: 15vh"></footer>
        </main>
    </div>
</body>
<script>
    function changeLanguage(language) {
        document.getElementById('langForm').submit();
    }
</script>

</html>

</html>
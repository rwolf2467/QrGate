<?php
require_once '../config.php';

$shows = getShows();

if ($shows && isset($shows['orga_name'], $shows['title'])) {
    $orgaName = htmlspecialchars($shows['orga_name']);
    $showTitle = htmlspecialchars($shows['title']);
    $showSubtitle = htmlspecialchars($shows['subtitle']);
    $wallpaper_url = API_BASE_URL . "api/show/get/wallpaper";
    $logo_url = API_BASE_URL . "api/show/get/logo";
} else {
    $orgaName = "Error loading show data";
    $showTitle = "Error loading show title";
    $showSubtitle = "Error loading show subtitle";
    $wallpaper_url = "";
    $logo_url = "";
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" async>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

        :root {
            --background-color: #0a0a0a;
            --card-background: #111111;
            --text-color: #ffffff;
            --text-secondary: rgb(157, 157, 157);
            --border-color: #222222;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Quicksand', sans-serif;
            background-color: #0a0a0a;
            color: var(--text-color);
            line-height: 1.6;
            background-color: #0a0a0a;
            background-size: 31px 31px;
            background-image: repeating-linear-gradient(45deg, #222222 0, #222222 3.1px, #0a0a0a 0, #0a0a0a 50%);
            background-attachment: fixed;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
            animation: fadeIn 4s ease forwards;
        }

        .container:hover {
            transform: scale(1.02);
        }

        .orga-name {
            transition: color 0.3s ease;
            animation: textAnimation 6s ease infinite alternate;
        }

        #gradientbar {
            height: 20px;
            background: linear-gradient(90deg, #9333ea, #ec4899, #eab308);
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 2;
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

        .orga-name-span {
            padding: 2px 8px;
            background-color: rgba(147, 51, 234, 0.2);
            border-radius: 4px;
            color: rgb(216, 180, 254);
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes textAnimation {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-2px);
            }
        }


        .welcome-text {
            position: relative;
            width: 100%;
            text-align: center;
            margin: 0;
        }

        .welcome-text {
            font-size: 5em;
            color: var(--text-color);
            animation: fadeIn 2s ease forwards;
        }


        .language-switcher {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            vertical-align: middle;
            text-align: center;
        }

        .inactive {
            opacity: 0.5;
            font-size: 1.5em;
            vertical-align: middle;
        }

        .active {
            opacity: 1;
            font-size: 2em;
            vertical-align: middle;
        }

        .language-switcher i {
            margin-left: 5px;
            font-size: 0.9em;
            color: var(--text-color);
        }

        .welcome-text i {
            margin-right: 10px;
            color: var(--text-color);
            font-size: 1.2em;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            align-items: center;
            justify-content: center;
            display: flex;
            flex-direction: column;
            padding: 10px;
            color: var(--text-secondary);
            font-size: 1.3em;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes wobble {
            0% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(5deg);
            }

            50% {
                transform: rotate(-5deg);
            }

            75% {
                transform: rotate(5deg);
            }

            100% {
                transform: rotate(0deg);
            }
        }

        @keyframes laugh {
            0% {
                transform: translateY(0);
            }

            25% {
                transform: translateY(-5px);
            }

            50% {
                transform: translateY(0);
            }

            75% {
                transform: translateY(5px);
            }

            100% {
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .welcome-text {
                font-size: 3em;
            }
        }

        #progressbar {
            height: 20px;
            background-color: rgba(0, 0, 0, 0.43);
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            opacity: 1;
            z-index: 3;
            transition: width 0.4s ease, opacity 1s ease;
        }


        .show-subtitle {
            font-size: 0.5em;
            color: var(--text-secondary);
        }

        .logo {
            bottom: 0;
            right: 0;
            margin: 30px;
            position: fixed;
            z-index: 2;
            border-radius: 18px;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        .logo img {
            width: 11vh;
            height: 11vh;
            border-radius: 12px;
            border: 5px solid transparent;
        }

        .wallpaper-div {
            background-image: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.7)), url(<?php echo $wallpaper_url; ?>);
            width:100%;
            height: 100%;
            background-size: cover;
            position: absolute;
            z-index: -2;
            opacity: .7;
        }
    </style>

</head>

<body>
    <div id="gradientbar"></div>
    <div id="progressbar"></div>
    <div class="wallpaper-div"></div>
    <div class="logo">
        <img src="<?php echo $logo_url; ?>" alt="">
    </div>

    <main>
        <div class="language-switcher">
            <span id="flag-en" class="active" aria-label="Switch to English"><b>EN</b></span>
            <span id="flag-de" class="inactive" aria-label="Switch to German"><b>DE</b></span>
        </div>
        <h1 class="welcome-text" id="welcomeText" style="display: block;" aria-live="polite">
            <i class="fas fa-smile" style="animation: laugh 0.5s infinite;" aria-hidden="true"></i>

            <br>
            Welcome to<br>
            <span class="orga-name-span"><?php echo $orgaName; ?></span>
        </h1>
        <h1 class="welcome-text" id="showText" style="display: none;">
            <i class="fas fa-theater-masks" style="animation: bounce 1s infinite;"></i>

            <br>
            <span class="show-title"><?php echo $showTitle; ?></span></br>
            <span class="show-subtitle"><?php echo $showSubtitle; ?></span>
        </h1>
        <h1 class="welcome-text" id="welcomeText1" style="display: none;">
            <i class="fas fa-heart" style="animation: pulse 1s infinite;"> </i>

            <br>
            We are so happy to see you here!
        </h1>
        <h1 class="welcome-text" id="ticket-text" style="display: none;">
            <i class="fa-solid fa-ticket" style="animation: wobble 1s infinite;"> </i>

            <br>
            To ensure a quick and smooth check-in,<br>
            please have your ticket ready before entering.
        </h1>
    </main>
    <footer>
        <hr style="width: 10%; border: 1px solid rgba(255, 255, 255, 0.5); margin-bottom: 5px;">
        <p>Powered by QrGate - avocloud.net
        </p>
    </footer>
    <script>
        let currentTextIndex = 0;
        const texts = [
            document.getElementById('welcomeText'),
            document.getElementById('showText'),
            document.getElementById('welcomeText1'),
            document.getElementById('ticket-text')
        ];

        let currentLanguage = 'en';
        let progressBarInterval;

        function switchText() {
            const currentText = texts[currentTextIndex];
            currentText.style.animation = 'fadeOut 2s forwards';
            setTimeout(function() {
                currentText.style.display = 'none';
                currentTextIndex = (currentTextIndex + 1) % texts.length;
                const nextText = texts[currentTextIndex];
                nextText.style.display = 'block';
                nextText.style.animation = 'fadeIn 2s forwards';

                if (currentTextIndex === 0) {
                    currentLanguage = currentLanguage === 'en' ? 'de' : 'en';
                    updateTextLanguage(currentLanguage);
                    updateFlags(currentLanguage);

                }

            }, 2000);
        }

        function updateTextLanguage(language) {
            const translations = {
                en: {
                    welcome: `<i class='fas fa-smile' style="animation: laugh 0.5s infinite;"></i> <br>Welcome to<br><span class='orga-name-span'><?php echo $orgaName; ?></span>`,
                    show: `<i class="fas fa-theater-masks" style="animation: bounce 1s infinite;"></i> <br><span class="show-title"><?php echo $showTitle; ?></span> <br> <span class="show-subtitle"><?php echo $showSubtitle; ?></span>`,
                    welcomeText1: `<i class='fas fa-heart' style="animation: pulse 1s infinite;"> </i> <br>We are so happy to see you here!`,
                    ticket: `<i class="fa-solid fa-ticket" style="animation: wobble 1s infinite;"> </i> <br>To ensure a quick and smooth check-in,<br>please have your ticket ready before entering.`
                },
                de: {
                    welcome: `<i class="fas fa-smile" style="animation: laugh 0.5s infinite;"></i> <br>Willkommen bei der <br><span class='orga-name-span'><?php echo $orgaName; ?></span>`,
                    show: `<i class="fas fa-theater-masks" style="animation: bounce 1s infinite;"></i> <br><span class="show-title"><?php echo $showTitle; ?></span> <br> <span class="show-subtitle"><?php echo $showSubtitle; ?></span>`,
                    welcomeText1: `<i class="fas fa-heart" style="animation: pulse 1s infinite;"> </i> <br>Wir freuen uns sehr, dich hier zu sehen!`,
                    ticket: `<i class="fa-solid fa-ticket" style="animation: wobble 1s infinite;"> </i> <br>Um einen zügigen Check-in zu ermöglichen,<br>halte bitte dein Ticket vor dem Einlass bereit.`
                }
            };

            document.getElementById('welcomeText').innerHTML = translations[language].welcome;
            document.getElementById('showText').innerHTML = translations[language].show;
            document.getElementById('welcomeText1').innerHTML = translations[language].welcomeText1;
            document.getElementById('ticket-text').innerHTML = translations[language].ticket;
        }

        function updateFlags(language) {
            const flagEn = document.getElementById('flag-en');
            const flagDe = document.getElementById('flag-de');
            resetProgressBar();
            if (language === 'en') {
                flagEn.classList.add('active');
                flagEn.classList.remove('inactive');
                flagDe.classList.add('inactive');
                flagDe.classList.remove('active');
            } else {
                flagDe.classList.add('active');
                flagDe.classList.remove('inactive');
                flagEn.classList.add('inactive');
                flagEn.classList.remove('active');
            }
        }

        function resetProgressBar() {
            clearInterval(progressBarInterval);
            const progressBar = document.getElementById('progressbar');
            progressBar.style.width = '0%';
            startProgressBar();
        }

        function startProgressBar() {
            const progressBar = document.getElementById('progressbar');
            progressBar.style.width = '0%';

            let width = 0;
            progressBarInterval = setInterval(() => {
                width++;
                progressBar.style.width = width + '%';
            }, 397);
        }

        setInterval(switchText, 10000);
        startProgressBar();
    </script>
</body>

</html>
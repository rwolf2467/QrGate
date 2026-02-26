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

// Load screens config or use defaults
$screens = null;
if ($shows && isset($shows['screens'])) {
    $screens = $shows['screens'];
}

$languageMode = $screens ? ($screens['language_mode'] ?? 'both') : 'both';

// Default slides when no screens config
$defaultSlides = [
    [
        'id' => 'slide_1',
        'icon' => 'fa-smile',
        'icon_animation' => 'laugh 0.5s infinite',
        'text_en' => "Welcome to\n{orga_name}",
        'text_de' => "Willkommen bei der\n{orga_name}",
        'cast' => []
    ],
    [
        'id' => 'slide_2',
        'icon' => 'fa-theater-masks',
        'icon_animation' => 'bounce 1s infinite',
        'text_en' => "{show_title}\n{show_subtitle}",
        'text_de' => "{show_title}\n{show_subtitle}",
        'cast' => []
    ],
    [
        'id' => 'slide_3',
        'icon' => 'fa-heart',
        'icon_animation' => 'pulse 1s infinite',
        'text_en' => 'We are so happy to see you here!',
        'text_de' => 'Wir freuen uns sehr, dich hier zu sehen!',
        'cast' => []
    ],
    [
        'id' => 'slide_4',
        'icon' => 'fa-ticket',
        'icon_animation' => 'wobble 1s infinite',
        'text_en' => "To ensure a quick and smooth check-in,\nplease have your ticket ready before entering.",
        'text_de' => "Um einen zügigen Check-in zu ermöglichen,\nhalte bitte dein Ticket vor dem Einlass bereit.",
        'cast' => []
    ]
];

$configSlides = ($screens && !empty($screens['slides'])) ? $screens['slides'] : $defaultSlides;

// Replace placeholders in text
function replacePlaceholders($text, $orgaName, $showTitle, $showSubtitle) {
    $text = str_replace('{orga_name}', $orgaName, $text);
    $text = str_replace('{show_title}', $showTitle, $text);
    $text = str_replace('{show_subtitle}', $showSubtitle, $text);
    return $text;
}

// Process slides - build HTML for each language
function renderSlideText($text, $orgaName, $showTitle, $showSubtitle) {
    $text = replacePlaceholders($text, $orgaName, $showTitle, $showSubtitle);
    $lines = explode("\n", $text);
    $html = '';
    foreach ($lines as $i => $line) {
        if ($i > 0) $html .= '<br>';
        if (strpos($line, $orgaName) !== false && $orgaName !== '') {
            $line = str_replace($orgaName, '<span class="orga-name-span">' . $orgaName . '</span>', $line);
        }
        if (strpos($line, $showSubtitle) !== false && $showSubtitle !== '' && $line === $showSubtitle) {
            $line = '<span class="show-subtitle">' . $line . '</span>';
        }
        $html .= $line;
    }
    return $html;
}

$castImageBase = API_BASE_URL . 'api/show/cast/image/';

// Build the final slide list: text slides stay as-is, cast slides get split into
// separate dedicated slides with max 2 members each.
$CAST_PER_SLIDE = 4;
$finalSlides = [];

foreach ($configSlides as $slide) {
    $cast = $slide['cast'] ?? [];
    $hasCast = !empty($cast);

    if (!$hasCast) {
        // Regular text slide
        $finalSlides[] = [
            'type' => 'text',
            'icon' => $slide['icon'] ?? 'fa-star',
            'icon_animation' => $slide['icon_animation'] ?? 'none',
            'text_en' => renderSlideText($slide['text_en'] ?? '', $orgaName, $showTitle, $showSubtitle),
            'text_de' => renderSlideText($slide['text_de'] ?? '', $orgaName, $showTitle, $showSubtitle),
        ];
    } else {
        // Split cast into chunks of CAST_PER_SLIDE, each becomes its own slide
        $castChunks = array_chunk($cast, $CAST_PER_SLIDE);
        foreach ($castChunks as $chunkIndex => $chunk) {
            $finalSlides[] = [
                'type' => 'cast',
                'icon' => $slide['icon'] ?? 'fa-users',
                'icon_animation' => $slide['icon_animation'] ?? 'pulse 1s infinite',
                'text_en' => renderSlideText($slide['text_en'] ?? '', $orgaName, $showTitle, $showSubtitle),
                'text_de' => renderSlideText($slide['text_de'] ?? '', $orgaName, $showTitle, $showSubtitle),
                'cast' => $chunk,
            ];
        }
    }
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
            background-size: 31px 31px;
            background-image: repeating-linear-gradient(45deg, #222222 0, #222222 3.1px, #0a0a0a 0, #0a0a0a 50%);
            background-repeat: repeat;
            background-attachment: fixed;
        }

        .orga-name-span {
            padding: 2px 8px;
            background-color: rgba(147, 51, 234, 0.2);
            border-radius: 4px;
            color: rgb(216, 180, 254);
        }

        #gradientbar {
            height: 20px;
            background: #555;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 2;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .welcome-text {
            position: relative;
            width: 100%;
            text-align: center;
            margin: 0;
            font-size: 5em;
            color: var(--text-color);
            animation: fadeIn 2s ease forwards;
        }

        .welcome-text i.slide-icon {
            margin-right: 10px;
            color: var(--text-color);
            font-size: 1.2em;
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
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes wobble {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(5deg); }
            50% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes laugh {
            0% { transform: translateY(0); }
            25% { transform: translateY(-5px); }
            50% { transform: translateY(0); }
            75% { transform: translateY(5px); }
            100% { transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .welcome-text {
                font-size: 3em;
            }
            .cast-image {
                width: 140px !important;
                height: 140px !important;
            }
            .cast-name {
                font-size: 1.2em !important;
            }
        }

        #progressbar {
            height: 20px;
            background-color: #e2e2e2;
            border-radius: 0 0 10px 10px;
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            opacity: 1;
            z-index: 3;
            transition: width 1.3s ease, opacity 1.5s ease;
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
            width: 100%;
            height: 100%;
            background-size: cover;
            position: absolute;
            z-index: -2;
            opacity: .7;
        }

        /* Cast slide styles */
        .cast-slide {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            width: 100%;
        }

        .cast-heading {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--text-color);
            text-align: center;
        }

        .cast-heading i.slide-icon {
            margin-right: 10px;
            font-size: 0.9em;
        }

        .cast-container {
            display: flex;
            justify-content: center;
            gap: 5rem;
            flex-wrap: wrap;
        }

        .cast-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.2rem;
        }

        .cast-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(147, 51, 234, 0.4);
            box-shadow: 0 8px 32px rgba(147, 51, 234, 0.15);
        }

        .cast-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.08);
            border: 4px solid rgba(147, 51, 234, 0.4);
            box-shadow: 0 8px 32px rgba(147, 51, 234, 0.15);
        }

        .cast-placeholder i {
            font-size: 4em;
            color: var(--text-secondary);
        }

        .cast-name {
            font-size: 1.5em;
            color: var(--text-color);
            font-weight: 600;
        }

        .cast-role {
            font-size: 1.1em;
            color: var(--text-secondary);
            font-style: italic;
            margin-top: -0.5rem;
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
        <?php if ($languageMode === 'both'): ?>
        <div class="language-switcher">
            <span id="flag-en" class="active" aria-label="Switch to English"><b>EN</b></span>
            <span id="flag-de" class="inactive" aria-label="Switch to German"><b>DE</b></span>
        </div>
        <?php endif; ?>

        <?php foreach ($finalSlides as $i => $slide):
            $icon = $slide['icon'] ?? 'fa-star';
            $animation = ($slide['icon_animation'] ?? 'none') !== 'none' ? 'animation: ' . htmlspecialchars($slide['icon_animation']) . ';' : '';
            $display = ($i === 0) ? 'block' : 'none';

            if ($languageMode === 'de') {
                $initialText = $slide['text_de'];
            } else {
                $initialText = $slide['text_en'];
            }

            if ($slide['type'] === 'cast'):
                $cast = $slide['cast'];
        ?>
        <div class="slide-element" id="slide_<?php echo $i; ?>" style="display: <?php echo $display; ?>;"
            data-type="cast"
            data-text-en="<?php echo htmlspecialchars($slide['text_en'], ENT_QUOTES); ?>"
            data-text-de="<?php echo htmlspecialchars($slide['text_de'], ENT_QUOTES); ?>"
            data-icon="<?php echo htmlspecialchars($icon); ?>"
            data-animation="<?php echo htmlspecialchars($slide['icon_animation'] ?? 'none'); ?>">
            <div class="cast-slide">
                <div class="cast-heading">
                    <i class="fas <?php echo htmlspecialchars($icon); ?> slide-icon" style="<?php echo $animation; ?>" aria-hidden="true"></i>
                    <span class="cast-heading-text"><?php echo $initialText; ?></span>
                </div>
                <div class="cast-container">
                    <?php foreach ($cast as $member): ?>
                    <div class="cast-card">
                        <?php if (!empty($member['image'])): ?>
                        <img class="cast-image" src="<?php echo $castImageBase . htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name'] ?? ''); ?>">
                        <?php else: ?>
                        <div class="cast-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <?php endif; ?>
                        <span class="cast-name"><?php echo htmlspecialchars($member['name'] ?? ''); ?></span>
                        <?php if (!empty($member['role'])): ?>
                        <span class="cast-role"><?php echo htmlspecialchars($member['role']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <h1 class="welcome-text slide-element" id="slide_<?php echo $i; ?>" style="display: <?php echo $display; ?>;" aria-live="polite"
            data-type="text"
            data-text-en="<?php echo htmlspecialchars($slide['text_en'], ENT_QUOTES); ?>"
            data-text-de="<?php echo htmlspecialchars($slide['text_de'], ENT_QUOTES); ?>"
            data-icon="<?php echo htmlspecialchars($icon); ?>"
            data-animation="<?php echo htmlspecialchars($slide['icon_animation'] ?? 'none'); ?>">
            <i class="fas <?php echo htmlspecialchars($icon); ?> slide-icon" style="<?php echo $animation; ?>" aria-hidden="true"></i>
            <br>
            <?php echo $initialText; ?>
        </h1>
        <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <footer>
        <hr style="width: 10%; border: 1px solid rgba(255, 255, 255, 0.5); margin-bottom: 5px;">
        <p>Powered by QrGate - avocloud.net</p>
    </footer>

    <script>
        const languageMode = '<?php echo $languageMode; ?>';
        let currentTextIndex = 0;
        const slides = document.querySelectorAll('.slide-element');
        let currentLanguage = languageMode === 'de' ? 'de' : 'en';
        let progressBarInterval;

        function switchText() {
            const currentSlide = slides[currentTextIndex];
            currentSlide.style.animation = 'fadeOut 2s forwards';
            setTimeout(function () {
                currentSlide.style.display = 'none';
                currentTextIndex = (currentTextIndex + 1) % slides.length;
                const nextSlide = slides[currentTextIndex];
                nextSlide.style.display = 'block';
                nextSlide.style.animation = 'fadeIn 2s forwards';

                if (currentTextIndex === 0 && languageMode === 'both') {
                    currentLanguage = currentLanguage === 'en' ? 'de' : 'en';
                    updateAllSlides(currentLanguage);
                    updateFlags(currentLanguage);
                }
            }, 2000);
        }

        function updateAllSlides(language) {
            slides.forEach(slide => {
                const textEn = slide.getAttribute('data-text-en');
                const textDe = slide.getAttribute('data-text-de');
                const icon = slide.getAttribute('data-icon');
                const anim = slide.getAttribute('data-animation');
                const animStyle = anim !== 'none' ? 'animation: ' + anim + ';' : '';
                const type = slide.getAttribute('data-type');
                const text = language === 'de' ? textDe : textEn;

                if (type === 'cast') {
                    const headingText = slide.querySelector('.cast-heading-text');
                    if (headingText) headingText.innerHTML = text;
                    const headingIcon = slide.querySelector('.cast-heading .slide-icon');
                    if (headingIcon) {
                        headingIcon.className = 'fas ' + icon + ' slide-icon';
                        headingIcon.style.cssText = animStyle;
                    }
                } else {
                    const iconHtml = `<i class="fas ${icon} slide-icon" style="${animStyle}" aria-hidden="true"></i><br>`;
                    slide.innerHTML = iconHtml + text;
                }
            });
        }

        function updateFlags(language) {
            const flagEn = document.getElementById('flag-en');
            const flagDe = document.getElementById('flag-de');
            if (!flagEn || !flagDe) return;
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

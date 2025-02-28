<?php

require_once '../config.php';
$shows = getShows();
$stars = htmlspecialchars($shows['votes']['average']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratingValue = $_POST['rating'] ?? 0;
    $comment = $_POST['comment'] ?? '';

    // Überprüfen, ob der Benutzer bereits abgestimmt hat
    if (isset($_SESSION['has_voted']) && $_SESSION['has_voted'] === true) {
        $error = 'Sie haben bereits abgestimmt.';
    } elseif ($ratingValue == 0) {
        $error = 'Bitte wählen Sie eine Bewertung aus.';
    } else {
        // API-Anfrage an die Vote-API
        $ch = curl_init(API_BASE_URL . 'api/vote');
        $requestData = json_encode(['value' => $ratingValue, 'comment' => $comment]);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . API_KEY
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $message = $result['message'] ?? 'Bewertung erfolgreich abgegeben.';
            $_SESSION['has_voted'] = true; // Abstimmungsstatus speichern
        } else {
            $message = 'Fehler bei der Abgabe der Bewertung.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bewertung - QR Gate</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Hier können Sie Ihre benutzerdefinierten Stile hinzufügen */

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
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #0a0a0a;
            color: var(--text-color);
            line-height: 1.6;
            background-size: 31px 31px;
            background-image: repeating-linear-gradient(45deg, #222222 0, #222222 3.1px, #0a0a0a 0, #0a0a0a 50%);
            background-attachment: fixed;
        }

        .container {
            max-width: 800px;
            width: 90%;
            /* Responsive width */
            margin: auto;
            padding: 20px;
            background: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: scale(1.02);
        }

        .icon {
            margin-right: 8px;
            /* Abstand zwischen Icon und Text */
        }

        .fa-star {
            font-size: 2rem;
            /* Größe der Sterne erhöhen */
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
                /* Weniger Padding auf kleinen Bildschirmen */
            }
        }

        .blur-sm {
            filter: blur(4px);
            /* Verwenden Sie einen Blur-Effekt */
            pointer-events: none;
            /* Verhindert Interaktionen mit dem Formular */
        }


        #responseMessage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            /* Zentriert das Element */
            text-align: center;
            z-index: 1000;
        }

        /* Weitere CSS-Regeln aus welcome.php können hier hinzugefügt werden */
    </style>
</head>

<body>
    <div class="container mx-auto px-4 py-8 <?php echo isset($_SESSION['has_voted']) && $_SESSION['has_voted'] === true ? 'blur-sm' : ''; ?>" style="position: relative;">
        <h1 class="text-3xl font-bold mb-4">
            <i class="fas fa-pencil-alt icon"></i>
            Submit your vote
        </h1>
        <p class="text-lg mb-4">Average rating: <?php echo $stars; ?> stars</p>
        <form method="POST" action="">
            <div class="flex items-center mb-4">
                <span class="mr-2">Your rating:</span>
                <div id="starRating" class="flex cursor-pointer">
                    <i class="fas fa-star text-gray-400" data-value="1" onclick="setRating(1)"></i>
                    <i class="fas fa-star text-gray-400" data-value="2" onclick="setRating(2)"></i>
                    <i class="fas fa-star text-gray-400" data-value="3" onclick="setRating(3)"></i>
                    <i class="fas fa-star text-gray-400" data-value="4" onclick="setRating(4)"></i>
                    <i class="fas fa-star text-gray-400" data-value="5" onclick="setRating(5)"></i>
                </div>
                <input type="hidden" name="rating" id="rating" value="0">
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-comment-dots icon"></i>
                <textarea id="comment" name="comment" placeholder="Your comment (optional)" class="w-full p-2 border border-gray-300 rounded mb-4"></textarea>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded" <?php echo isset($_SESSION['has_voted']) && $_SESSION['has_voted'] === true ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane icon"></i>
                Submit vote
            </button>
        </form>


    </div>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div id="responseMessage" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center z-10">
            <p class="text-lg mb-4">Average rating: <?php echo $stars; ?> stars</p>
            <?php if ($error): ?>
                <div style="padding: 2px 8px; background-color: rgba(234, 51, 51, 0.2); border-radius: 4px; color: rgb(255, 112, 112); font-size: 1.5rem; "><?php echo $error; ?></div>
            <?php elseif ($message): ?>
                <div style="padding: 2px 8px; background-color: rgba(124, 226, 83, 0.32); border-radius: 4px; color: rgb(71, 127, 37); font-size: 1.5rem;"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <script>
        function setRating(value) {
            document.getElementById('rating').value = value;
            const stars = document.querySelectorAll('#starRating i');
            stars.forEach(star => {
                star.classList.remove('text-yellow-500');
                star.classList.add('text-gray-400');
            });
            for (let i = 0; i < value; i++) {
                stars[i].classList.add('text-yellow-500');
            }
        }
    </script>
</body>

</html>
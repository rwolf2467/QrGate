<?php
require_once '../config.php';
require "../translate.php";

$shows = getShows();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? null;

    if ($rating) {
        $API_KEY = API_KEY;
        $API_ENDPOINT = API_BASE_URL . 'api/vote';

        $ch = curl_init($API_ENDPOINT);
        $requestData = json_encode(['value' => (float)$rating]);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $API_KEY
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo $response;
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No rating provided.'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Gate - Vote</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

        :root {
            --background-color: #0a0a0a;
            --card-background: #111111;
            --text-color: #ffffff;
            --text-secondary: #888888;
            --border-color: #222222;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
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

        .star {
            cursor: pointer;
            font-size: 2rem;
            color: #ccc;
        }

        .star.selected {
            color: #f39c12;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        button {
            background: linear-gradient(90deg, #9333ea, #ec4899);
            color: var(--text-color);
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        button:hover {
            opacity: 0.9;
        }

        #message {
            margin-top: 20px;
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-4">Submit Your Rating</h1>
        <form id="voteForm" method="POST" onsubmit="validateForm()">
            <div id="rating" class="flex mb-4">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="0">
            <button type="submit" id="submitButton" disabled>Vote</button>
        </form>
        <div id="message"></div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const submitButton = document.getElementById('submitButton');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const selectedRating = parseFloat(star.getAttribute('data-value'));
                ratingInput.value = selectedRating; // Set the value of the hidden input
                stars.forEach(s => {
                    s.classList.remove('selected');
                    if (parseFloat(s.getAttribute('data-value')) <= selectedRating) {
                        s.classList.add('selected');
                    }
                });
                submitButton.disabled = false; // Enable the submit button
            });
        });

        function validateForm() {
            if (ratingInput.value === "0") {
                document.getElementById('message').innerText = 'Please select a rating before submitting.';
                return false; // Prevent form submission
            }
            console.log('Submitting rating:', ratingInput.value);
            return true;
        }
    </script>
</body>

</html>

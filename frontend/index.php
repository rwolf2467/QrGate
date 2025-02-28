<?php
require_once 'config.php';
require "translate.php";
$shows = getShows();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Gate - Theater Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if ($shows !== null): ?>
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=EUR"></script>
    <?php endif; ?>
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

        .group {
            background: rgba(17, 17, 17, 0.8);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            backdrop-filter: blur(30px);
            position: relative;
            z-index: 1;
            opacity: 0.8;
        }

        .group:hover {
            border-color: #9333ea;
            background: rgba(147, 51, 234, 0.1);
        }

        button {
            background: linear-gradient(90deg, #9333ea, #ec4899);
            transition: opacity 0.2s;
        }

        button:hover {
            opacity: 0.9;
        }

        button:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
        }

        .x {
            background: var(--border-color);
            border-radius: 100%;
            padding: 10px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            top: 0px;
            right: 0px;
            position: absolute;
            margin-top: 24px;
            margin-right: 24px;
        }

        .bar-text {
            color: rgb(216, 180, 254);
        }

        .bar {
            background: linear-gradient(90deg, #9333ea, #ec4899);
        }

        .bar-dark {
            background-color: rgb(40, 24, 44);
        }

        .nichtunsichbarbittediggi {
            background: var(--card-background);
            border: 1px solid var(--border-color);
            color: var(--text-color) !important;
        }

        .nichtunsichbarbittediggi input,
        .nichtunsichbarbittediggi select {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            color: var(--text-color) !important;
            border-radius: 4px;
            padding: 8px;
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

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .show-name {
            padding: 2px 8px;
            background-color: rgba(147, 51, 234, 0.2);
            border-radius: 4px;
            color: rgb(216, 180, 254);
            font-size: 3rem;
        }

        .sold-out {
            padding: 2px 8px;
            background-color: rgba(234, 51, 51, 0.2);
            border-radius: 4px;
            color: rgb(255, 112, 112);
            font-size: 1.5rem;
        }

        .soon-sold-out {
            padding: 2px 8px;
            background-color: rgba(234, 176, 51, 0.2);
            border-radius: 4px;
            color: rgb(255, 176, 112);
            font-size: 1rem;
        }

        .date-title {
            color: rgb(216, 180, 254);
            font-size: 2.5rem;
        }

        .show-hover:hover {
            color: rgb(216, 180, 254);
        }

        .orga-name {
            font-size: 5rem;
            color: var(--text-color);
        }

        .modal {
            backdrop-filter: blur(6px);
        }

        .inputs {
            background-color: rgb(17, 17, 17);
            border: 1px solid #222222;
            color: black;
            outline: none;
        }

        .inputs:focus {
            background-color: rgb(17, 17, 17);
            border: 1px solid #9333ea;
            color: black;
            box-shadow: 0 0 5px #9333ea;
        }

        .inputs:active {
            background-color: rgb(17, 17, 17);
            border: 1px solid #9333ea;
            color: black;
        }

        .modal-enter {
            opacity: 0;
            transform: scale(0.9);
        }

        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: opacity 0.3s, transform 0.3s;
        }

        .modal-exit {
            opacity: 1;
            transform: scale(1);
        }

        .modal-exit-active {
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.3s, transform 0.3s;
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
    <div class="language-selector">
        <form method="post" id="langForm">
            <select name="language" onchange="this.form.submit()">
                <?php foreach ($languages as $code => $lang): ?>
                    <option value="<?php echo $code; ?>"
                        <?php echo ($_SESSION['language'] == $code) ? 'selected' : ''; ?>>
                        <span class="flag"><?php echo $lang['flag']; ?></span>
                        <?php echo $lang['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if ($shows === null): ?>
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-red-900/50 backdrop-blur-md border border-red-700 rounded-lg p-8 max-w-md w-full text-center">
                <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-2xl font-bold mb-4" data-translate>Error loading shows</h2>
                <p class="text-gray-300" data-translate>Please try again later or contact support.</p>
                <button onclick="location.reload()" class="mt-6 px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg transition-colors" data-translate>
                    Try again
                </button>
            </div>
        </div>

    <?php else: ?>
        <!-- Smooth loading animation container -->
        <div class="animate-fade-in">
            <!-- Hero Section with Banner -->
            <div class="relative min-h-[60vh] flex items-center justify-center mb-12">
                <div class="absolute inset-0 bg-black opacity-50"></div>
                <div class="absolute inset-0 bg-cover bg-center transform scale-100 transition-transform duration-700"
                    style="background-image: url('<?php echo htmlspecialchars($shows['banner']); ?>')"></div>
                <div class="relative z-10 text-center px-4 py-16 backdrop-blur-sm bg-black/30 rounded-xl mx-4">
                    <h1 class="orga-name animate-fade-in-up">
                        <b><span data-translate><?php echo htmlspecialchars($shows['orga_name']); ?></span></b>
                    </h1>
                    <h2 class="animate-fade-in-up">
                        <span class="show-name"><b><span data-translate><?php echo htmlspecialchars($shows['title']); ?></span></b></span>
                    </h2>
                </div>
            </div>

            <div class="container mx-auto px-4 py-8">
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<div class='animate-fade-in bg-red-900/50 backdrop-blur-md border border-red-700 text-red-100 px-6 py-4 rounded-lg relative mb-6' role='alert' style='backdrop-filter: blur(10px);'>
                            <span class='block sm:inline animate-pulse'>{$_SESSION['error']}</span>
                          </div>";
                    unset($_SESSION['error']);
                }

                if (isset($_SESSION['success'])) {
                    echo "<div class='animate-fade-in bg-green-900/50 backdrop-blur-md border border-green-700 text-green-100 px-6 py-4 rounded-lg relative mb-6 ' role='alert' style='backdrop-filter: blur(10px);'>
                            <span class='block sm:inline animate-pulse'>{$_SESSION['success']}</span>
                          </div>";
                    unset($_SESSION['success']);
                }
                ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($shows['dates'] as $id => $show): ?>
                        <div class="show-hover transition-colors group rounded-xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:scale-105">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <span class="date-title font-bold">
                                        <i class="fa-solid fa-calendar-days"></i>
                                        <?php $date = new DateTime($show['date']);
                                        echo $date->format('d.m.Y'); ?>
                                    </span>
                                    <?php if ($show['seats_available'] <= 20 && $show['seats_available'] > 0): ?>
                                        <span class="soon-sold-out font-semibold animate-pulse" data-translate>
                                            Only <?php echo $show['seats_available']; ?> seats left!
                                        </span>
                                    <?php elseif ($show['seats_available'] == 0): ?>
                                        <span class="sold-out font-bold animate-pulse" data-translate>Sold out</span>
                                    <?php endif; ?>
                                </div>

                                <div class="space-y-4 mb-6 #">
                                    <p>
                                        <i class="fa-solid fa-euro-sign"></i> <span><?php echo htmlspecialchars($show['price']); ?></span>
                                    </p>
                                    <p>
                                        <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($show['time']); ?>
                                    </p>
                                    <div class="relative pt-1">
                                        <div class="flex mb-2 items-center justify-between">
                                            <div class="text-right">
                                                <span class="text-xs font-semibold inline-block bar-text" data-translate>
                                                    <?php echo htmlspecialchars($show['seats_available']); ?> of <?php echo htmlspecialchars($show['seats']); ?> seats available.
                                                </span>
                                            </div>
                                        </div>
                                        <div class="overflow-hidden h-2 text-xs flex rounded bar-dark">
                                            <?php
                                            $occupiedSeats = $show['seats'] - $show['seats_available'];
                                            $percentage = ($occupiedSeats / $show['seats']) * 100;
                                            echo "<div style='width: {$percentage}%' class='shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bar'></div>";
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($show['seats_available'] > 0): ?>
                                    <button
                                        onclick="showBookingForm('<?php echo $id; ?>', '<?php echo $show['date']; ?>', '<?php echo $show['price']; ?>', '<?php echo $show['seats_available']; ?>')"
                                        class="w-full text-gray-900 py-4 px-6 rounded-lg font-bold transform hover:-translate-y-1 transition-all duration-300">
                                        <span data-translate>Buy Tickets</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <style>
                    .nichtunsichbarbittediggi {
                        background-color: rgb(17, 17, 17);
                        border: 1px solid #222222;
                        color: black;
                    }

                    .nichtunsichbarbittediggi input {
                        color: black;
                    }

                    .nichtunsichbarbittediggi select {
                        color: black;
                    }
                </style>

                <!-- Modernized Booking Modal -->
                <div id="bookingModal" class="hidden fixed inset-0 overflow-y-auto h-full w-full z-50 modal">
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative w-full max-w-md border rounded-2xl shadow-2xl p-8 nichtunsichbarbittediggi">
                            <div id="modalError" class="hidden bg-red-900/50 backdrop-blur-md border border-red-700 text-red-100 px-4 py-3 rounded-lg relative mb-6">
                            </div>
                            <form id="bookingForm" action="buy.php" method="POST" class="space-y-6">
                                <input type="hidden" name="valid_date" id="validDate">
                                <input type="hidden" name="price" id="ticketPrice">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-200" data-translate>First Name</label>
                                    <input type="text" name="first_name" placeholder="Max" required
                                        class="w-full p-4 rounded-lg text-white inputs transition-all">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-200" data-translate>Last Name</label>
                                    <input type="text" name="last_name" placeholder="Mustermann" required
                                        class="w-full p-4 rounded-lg text-white inputs transition-all">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-200" data-translate>Email</label>
                                    <input type="text" name="email" placeholder="max.mustermann@example.com" required
                                        class="w-full p-4 rounded-lg text-white inputs transition-all">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-200" data-translate>Number of Tickets</label>
                                    <select name="seats" required class="w-full p-4 rounded-lg text-white inputs transition-all" onchange="updateNameFields()">
                                    </select>
                                </div>

                                <div id="nameFieldsContainer" class="space-y-2"></div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-200" data-translate>Payment Method</label>
                                    <select name="payment_method" id="paymentMethod" required
                                        class="w-full p-4 rounded-lg text-white inputs transition-all">
                                        <option value="bar" data-translate>Cash payment</option>
                                        <option value="paypal" data-translate>PayPal</option>
                                    </select>
                                </div>

                                <div id="paypalButtons" class="hidden mt-6"></div>

                                <button type="submit" id="submitButton"
                                    class="w-full text-gray-900 py-4 px-6 rounded-lg font-bold transform hover:-translate-y-1 transition-all duration-300" data-translate>
                                    Buy Tickets
                                </button>

                                <button type="button" onclick="closeModal()"
                                    class="x">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            body {
                font-family: 'Quicksand', sans-serif;
            }

            .animate-fade-in {
                animation: fadeIn 0.8s ease-in-out;
            }

            .animate-fade-in-up {
                animation: fadeInUp 0.8s ease-in-out;
            }

            @keyframes fadeIn {
                0% {
                    opacity: 0;
                    transform: scale(0.95);
                }

                100% {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            @keyframes fadeInUp {
                0% {
                    opacity: 0;
                    transform: translateY(20px) scale(0.95);
                }

                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
        </style>

        <script>
            function showBookingForm(showId, date, price, seatsAvailable) {
                const modal = document.getElementById('bookingModal');
                modal.classList.remove('hidden');
                modal.classList.add('modal-enter');
                setTimeout(() => {
                    modal.classList.add('modal-enter-active');
                }, 10);

                document.getElementById('validDate').value = date;
                document.getElementById('ticketPrice').value = price;
                initPayPalButton(price);
                document.body.style.overflow = 'hidden';

                const seatsSelect = document.querySelector('select[name="seats"]');
                seatsSelect.innerHTML = '';
                const maxSeats = Math.min(seatsAvailable, 10);
                for (let i = 1; i <= maxSeats; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `${i} Ticket${i > 1 ? 's' : ''}`;
                    seatsSelect.appendChild(option);
                }
            }

            function closeModal() {
                const modal = document.getElementById('bookingModal');
                modal.classList.remove('modal-enter-active');
                modal.classList.add('modal-exit');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('modal-exit');
                    document.body.style.overflow = 'auto';
                    location.reload();
                }, 300);
            }

            function showError(message) {
                const errorDiv = document.getElementById('modalError');
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');


                setTimeout(() => {
                    errorDiv.classList.add('hidden');
                }, 5000);
            }

            document.getElementById('paymentMethod').addEventListener('change', function(e) {
                const paypalButtons = document.getElementById('paypalButtons');
                const submitButton = document.getElementById('submitButton');

                if (e.target.value === 'paypal') {
                    paypalButtons.classList.remove('hidden');
                    submitButton.classList.add('hidden');
                } else {
                    paypalButtons.classList.add('hidden');
                    submitButton.classList.remove('hidden');
                }
            });

            function initPayPalButton(price) {
                const seatsSelect = document.querySelector('select[name="seats"]');
                let currentPrice = parseFloat(price);

                seatsSelect.addEventListener('change', function() {
                    currentPrice = parseFloat(price) * parseInt(this.value);
                    if (window.paypalButtons) {
                        window.paypalButtons.close();
                    }
                    createPayPalButtons(currentPrice);
                });

                createPayPalButtons(currentPrice);
            }

            function createPayPalButtons(price) {
                window.paypalButtons = paypal.Buttons({
                    style: {
                        layout: 'vertical',
                        color: 'gold',
                        shape: 'rect',
                        label: 'pay'
                    },
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    currency_code: "EUR",
                                    value: price.toString()
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            if (details.status === 'COMPLETED') {
                                document.getElementById('bookingForm').submit();
                            } else {
                                showError('Zahlung fehlgeschlagen. Bitte versuchen Sie es erneut.');
                            }
                        });
                    },
                    onError: function(err) {
                        showError('PayPal error: ' + err.message);
                    }
                });

                window.paypalButtons.render('#paypalButtons');
            }

            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                const firstName = this.elements['first_name'].value.trim();
                const lastName = this.elements['last_name'].value.trim();
                const email = this.elements['email'].value.trim();
                const seats = this.elements['seats'].value;

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

                if (!seats || seats < 1) {
                    errors.push('Please select the number of tickets.');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    showError(errors.join('\n'));
                    return false;
                }
            });


            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });


            document.getElementById('bookingModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            function updateNameFields() {
                const seatsSelect = document.querySelector('select[name="seats"]');
                const nameFieldsContainer = document.getElementById('nameFieldsContainer');
                const numberOfSeats = parseInt(seatsSelect.value);


                nameFieldsContainer.innerHTML = '';


                if (numberOfSeats > 1) {
                    for (let i = 1; i < numberOfSeats; i++) {
                        const inputField = document.createElement('div');
                        inputField.className = 'space-y-2';
                        inputField.innerHTML = `
                            <label class="block text-sm font-medium text-gray-200">Name for Ticket ${i + 1}</label>
                            <input type="text" name="add_people[]" placeholder="Name for ticket ${i + 1}" required class="w-full p-4 rounded-lg text-white inputs transition-all">
                        `;
                        nameFieldsContainer.appendChild(inputField);
                    }
                }
            }

            async function processPayment(paymentDetails) {

                const available = await checkAvailability(paymentDetails.showId, paymentDetails.ticketCount);
                if (!available) {
                    throw new Error("Nicht genügend Plätze verfügbar.");
                }


                initPayPalButton(paymentDetails.price);
            }

            async function checkAvailability(showId, ticketCount) {

                const response = await fetch(`/api/ticket/available_seats/${showId}`);
                const data = await response.json();

                if (data.status === "error") {
                    throw new Error(data.message);
                }


                return data.available_seats >= ticketCount;
            }
        </script>
    <?php endif; ?>
</body>

</html>
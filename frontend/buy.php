<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF validation
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception('Invalid request. Please try again.');
        }

        $requiredFields = ['first_name', 'last_name', 'tickets', 'valid_date', 'payment_method', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Feld '$field' ist erforderlich");
            }
        }

        $paymentMethodsResponse = makeApiCall('/api/show/get/payment_methods');
        $allowedPaymentMethods = ['cash', 'stripe', 'bar'];

        if (isset($paymentMethodsResponse['payment_methods'])) {
            $paymentMethods = $paymentMethodsResponse['payment_methods'];

            if ($paymentMethods === 'cash') {
                $allowedPaymentMethods = ['cash', 'bar'];
            } elseif ($paymentMethods === 'online') {
                $allowedPaymentMethods = ['stripe'];
            } elseif ($paymentMethods === 'both') {
                $allowedPaymentMethods = ['cash', 'stripe', 'bar'];
            }
        }

        $ticketData = [
            'first_name' => trim($_POST['first_name']),
            'last_name'  => trim($_POST['last_name']),
            'email'      => trim($_POST['email']),
            'tickets'    => (int)$_POST['tickets'],
            'valid_date' => $_POST['valid_date'],
            'price'      => (float)$_POST['price'],
            'paid'       => false,
            'method'     => $_POST['payment_method'],
            'add_people' => isset($_POST['add_people']) ? $_POST['add_people'] : []
        ];

        if ($ticketData['tickets'] < 1 || $ticketData['tickets'] > 11) {
            throw new Exception('Invalid number of people! Please do not play around with the code of this page, otherwise your access to this page will be denied.');
        }

        if (!in_array($ticketData['method'], $allowedPaymentMethods)) {
            throw new Exception('Invalid payment method selected! Please do not play around with the code of this page, otherwise your access to this page will be denied.');
        }

        if ($ticketData['method'] === 'bar') {
            $result = makeApiCall('/api/ticket/create', 'POST', $ticketData);

            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }

            if ($result['status'] === 'success') {
                $_SESSION['success'] = "Your tickets have been successfully submitted to the system. You will receive your tickets by email shortly. Please pay your tickets on the day of the event at our ticket counter.";
            } else {
                throw new Exception('Ticket could not be created!');
            }

            header('Location: index.php');
            exit;

        } elseif ($ticketData['method'] === 'stripe') {
            $paymentIntentId = trim($_POST['payment_intent_id'] ?? '');

            if (empty($paymentIntentId)) {
                throw new Exception('Payment verification failed: no payment reference provided.');
            }

            // Retrieve Stripe config (secret key) from backend
            $stripeConfig = makeApiCall('/api/show/get/stripe');

            if (isset($stripeConfig['error']) || empty($stripeConfig['secret_key'])) {
                throw new Exception('Payment configuration error. Please contact the organizer.');
            }

            // Verify payment server-side with Stripe (direct curl, no SDK)
            $secretKey = $stripeConfig['secret_key'];
            $intentId  = preg_replace('/[^a-zA-Z0-9_]/', '', $paymentIntentId); // sanitize

            $ch = curl_init('https://api.stripe.com/v1/payment_intents/' . $intentId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $secretKey . ':',
            ]);
            $stripeResponse = curl_exec($ch);
            $stripeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($stripeHttpCode !== 200) {
                throw new Exception('Payment verification failed. Please contact the organizer.');
            }

            $intent = json_decode($stripeResponse, true);

            if (($intent['status'] ?? '') !== 'succeeded') {
                throw new Exception('Payment has not been completed. Please try again.');
            }

            // Verify amount matches expected (price per ticket × number of tickets, in cents)
            $expectedCents = (int)round($ticketData['price'] * $ticketData['tickets'] * 100);
            if (($intent['amount_received'] ?? 0) !== $expectedCents) {
                throw new Exception('Payment amount mismatch. Please contact the organizer.');
            }

            $ticketData['paid'] = true;

            $result = makeApiCall('/api/ticket/create', 'POST', $ticketData);

            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }

            if ($result['status'] === 'success') {
                $_SESSION['success'] = "Your tickets have been successfully entered and paid for. You will receive your tickets by email shortly.";
            } else {
                throw new Exception('Ticket could not be created');
            }

            header('Location: index.php');
            exit;

        } else {
            throw new Exception('Unknown payment method! Please do not play around with the code of this page, otherwise your access to this page will be denied.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit;
    }
}


header('Location: index.php');
exit;

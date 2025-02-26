<?php

require_once 'config.php';
require 'vendor/autoload.php';

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        $requiredFields = ['first_name', 'last_name', 'seats', 'valid_date', 'payment_method', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Feld '$field' ist erforderlich");
            }
        }

        $ticketData = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'seats' => (int)$_POST['seats'],
            'valid_date' => $_POST['valid_date'],
            'price' => (float)$_POST['price'],
            'paid' => false,
            'method' => $_POST['payment_method'],
            'add_people' => isset($_POST['add_people']) ? $_POST['add_people'] : []
        ];


        if ($ticketData['seats'] < 1 || $ticketData['seats'] > 11) {
            throw new Exception('Invalid number of people! Please do not play around with the code of this page, otherwise your access to this page will be denied.');
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
        } elseif ($ticketData['method'] === 'paypal') {
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

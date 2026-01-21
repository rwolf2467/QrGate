<?php
session_start();



$admin_password = 'admin123'; 
$ticketflow_password = 'ticketflow123'; 
$handheld_password = 'handheld123'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $redirect = $_GET['redirect'] ?? 'admin';

    if ($password === $admin_password) {
        $_SESSION['admin'] = true;

        if ($redirect === 'ticketflow') {
            $_SESSION['ticketflow_access'] = true;
            header('Location: ticketflow/index.php');
        } elseif ($redirect === 'handheld') {
            $_SESSION['handheld_access'] = true;
            header('Location: handheld/index.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } elseif ($password === $ticketflow_password) {
        $_SESSION['ticketflow_access'] = true;
        header('Location: ticketflow/index.php');
        exit;
    } elseif ($password === $handheld_password) {
        $_SESSION['handheld_access'] = true;
        header('Location: handheld/index.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}


if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QRGate Admin Login</title>
    
    <!-- Basecoat CSS (ohne Tailwind!) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" type="image/png" href="<?php echo API_BASE_URL; ?>/api/image/get/logo.png?t=<?php echo time(); ?>">
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }

        .login-card {
            background-color: var(--darker);
            border: 1px solid var(--border);
            border-radius: 8px;
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }

        .logo-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background-color: #4c1d95;
            border-radius: 9999px;
            margin: 0 auto 1.5rem;
        }

        .logo-icon i {
            color: #c084fc;
            font-size: 1.5rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .subtitle {
            color: #a1a1aa;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .form {
            margin-bottom: 1.5rem;
        }

        .field {
            margin-bottom: 1.25rem;
        }

        .input {
            background-color: var(--dark);
            border: 1px solid var(--border);
            color: white;
            width: 100%;
            padding: 0.625rem;
            border-radius: 0.375rem;
        }

        .input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.3);
        }

        .btn {
            width: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert {
            background-color: #7f1d1d;
            border: 1px solid #991b1b;
            color: #fecaca;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .footer-hint {
            text-align: center;
            font-size: 0.8125rem;
            color: #71717a;
            margin-top: 1.5rem;
        }

        .footer-hint strong {
            color: #e9d5ff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-icon">
            <i class="fas fa-qrcode"></i>
        </div>
        <h1>QRGate Admin</h1>
        <p class="subtitle">Enter your admin credentials</p>

        <?php if (isset($error)): ?>
            <div class="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form">
            <div class="field">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="input" 
                    placeholder="Enter password" 
                    required 
                />
                <p class="text-muted" style="color: #a1a1aa; font-size: 0.8125rem; margin-top: 0.25rem;">
                    Enter the password for your application. After login, you will be redirected to the correct application.
                </p>
            </div>

            <button type="submit" class="btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                    <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                </svg>
                Submit
            </button>
        </form>

        <div class="footer-hint">
            <p>Default password: <strong>admin123</strong></p>
            <p class="mt-1">Remember to change this in production!</p>
        </div>
    </div>
</body>
</html>
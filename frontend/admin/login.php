<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $redirect = $_GET['redirect'] ?? 'admin';

        if ($password === ADMIN_PASSWORD) {
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
        } elseif ($password === TICKETFLOW_PASSWORD) {
            $_SESSION['ticketflow_access'] = true;
            header('Location: ticketflow/index.php');
            exit;
        } elseif ($password === HANDHELD_PASSWORD) {
            $_SESSION['handheld_access'] = true;
            header('Location: handheld/index.php');
            exit;
        } else {
            $error = 'Invalid password';
        }
    }
}


if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'QrGate Admin Login';
$assetBase = '../';
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/../partials/head.php'; ?>
<body class="min-h-screen flex flex-col">
    <div class="avo-topbar" aria-hidden="true"></div>
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="card w-full max-w-sm">
            <div class="card-header text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full"
                     style="background-color: color-mix(in oklab, var(--avo-primary) 16%, transparent);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="avo-coral" aria-hidden="true">
                        <rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/>
                        <rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                        <path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/>
                        <path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/>
                        <path d="M12 21v-1"/>
                    </svg>
                </div>
                <div class="avo-kicker mb-1">// access control</div>
                <h1 class="text-2xl">QrGate <span class="avo-hl">Admin</span></h1>
                <p class="avo-muted mt-1 text-sm">Enter your admin credentials</p>
            </div>

            <div class="card-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-destructive mb-6" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form grid gap-5">
                    <?php echo csrfField(); ?>
                    <div class="grid gap-2">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="input"
                            placeholder="Enter password"
                            required
                        />
                        <p class="avo-muted text-xs">
                            Enter the password for your application. After login, you will be redirected to the correct application.
                        </p>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                            <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                        </svg>
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php
    $orgName = '';
    $current_language = 'en';
    include __DIR__ . '/../partials/footer.php';
    ?>
</body>
</html>

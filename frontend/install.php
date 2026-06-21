<?php
/**
 * First-run setup wizard.
 *
 * Reached at /install (nginx rewrites to this file). Walks the operator through
 * SMTP settings, the first event and the admin password, then POSTs everything
 * to the backend /api/setup/complete endpoint. Once the backend reports the
 * system as installed, this page redirects away.
 */
require_once 'config.php';

// Already set up? Send the operator to the admin login instead.
$statusResp = makeApiCall('/api/setup/status');
if (is_array($statusResp) && !isset($statusResp['error']) && !empty($statusResp['installed'])) {
    header('Location: /admin/login.php');
    exit;
}

$error = null;
$step  = 'form';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid session token. Please reload the page and try again.';
    } else {
        $adminPw = $_POST['admin_password'] ?? '';
        $adminPw2 = $_POST['admin_password_confirm'] ?? '';

        if (strlen($adminPw) < 8) {
            $error = 'The admin password must be at least 8 characters long.';
        } elseif ($adminPw !== $adminPw2) {
            $error = 'The admin passwords do not match.';
        } else {
            $payload = [
                'smtp' => [
                    'server'   => trim($_POST['smtp_server'] ?? ''),
                    'port'     => (int)($_POST['smtp_port'] ?? 587),
                    'user'     => trim($_POST['smtp_user'] ?? ''),
                    'password' => $_POST['smtp_password'] ?? '',
                ],
                'event' => [
                    'orga_name'       => trim($_POST['orga_name'] ?? ''),
                    'title'           => trim($_POST['event_title'] ?? ''),
                    'subtitle'        => trim($_POST['event_subtitle'] ?? ''),
                    'date'            => trim($_POST['event_date'] ?? ''),
                    'time'            => trim($_POST['event_time'] ?? ''),
                    'tickets'         => (int)($_POST['event_tickets'] ?? 0),
                    'price'           => trim($_POST['event_price'] ?? '0'),
                    'payment_methods' => trim($_POST['payment_methods'] ?? 'both'),
                ],
                'admin' => [
                    'password' => $adminPw,
                ],
            ];

            $resp = makeApiCall('/api/setup/complete', 'POST', $payload);
            if (is_array($resp) && ($resp['status'] ?? '') === 'success') {
                $_SESSION['qrgate_installed'] = true;
                $step = 'done';
            } else {
                $error = 'Setup failed: ' . htmlspecialchars($resp['message'] ?? ($resp['error'] ?? 'Unknown error'));
            }
        }
    }
}

$assetBase = '';
$pageTitle = 'QrGate · Setup';
$forceDark = false;
$faviconUrl = $assetBase . 'assets/img/avocloud-appicon-dark.svg';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<?php include 'partials/head.php'; ?>
<body class="min-h-screen" style="background: var(--avo-bg); color: var(--avo-text);">
<div class="avo-topbar" aria-hidden="true"></div>

<main class="max-w-2xl mx-auto px-6 py-12">
    <div class="text-center mb-8">
        <div class="avo-kicker">// first-run setup</div>
        <h1 class="text-3xl font-bold mt-2" style="font-family:var(--avo-font-display);">Welcome to QrGate</h1>
        <p class="mt-2" style="color: var(--avo-text-muted);">Let's get your ticketing system ready. This takes about a minute.</p>
    </div>

<?php if ($step === 'done'): ?>
    <div class="card text-center gap-4 py-10">
        <div class="text-5xl">✅</div>
        <h2 class="text-2xl font-bold" style="font-family:var(--avo-font-display);">All set!</h2>
        <p style="color: var(--avo-text-muted);">QrGate is installed. Sales are still locked &mdash; open them from the admin dashboard when you're ready.</p>
        <a href="/admin/login.php" class="btn-primary mt-2 inline-block">Go to admin login</a>
    </div>
<?php else: ?>

    <?php if ($error): ?>
    <div class="card mb-6" style="border-color: var(--avo-coral);">
        <p style="color: var(--avo-coral);"><?php echo $error; // already escaped on build ?></p>
    </div>
    <?php endif; ?>

    <!-- Step indicator -->
    <div class="flex items-center justify-center gap-2 mb-6 text-xs" style="color: var(--avo-text-muted);">
        <span data-dot="1" class="setup-dot">1 · SMTP</span>
        <span>›</span>
        <span data-dot="2" class="setup-dot">2 · Event</span>
        <span>›</span>
        <span data-dot="3" class="setup-dot">3 · Admin</span>
    </div>

    <form method="POST" action="/install" id="setupForm">
        <?php echo csrfField(); ?>

        <!-- Step 1: SMTP -->
        <section data-step="1" class="card gap-4">
            <h2 class="text-xl font-bold" style="font-family:var(--avo-font-display);">E-mail (SMTP)</h2>
            <p class="text-sm" style="color: var(--avo-text-muted);">Used to send tickets to buyers. You can change this later in the config.</p>
            <div>
                <label class="label" for="smtp_server">SMTP server</label>
                <input class="input" id="smtp_server" name="smtp_server" placeholder="smtp.example.com">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="smtp_port">Port</label>
                    <input class="input" id="smtp_port" name="smtp_port" type="number" value="587">
                </div>
                <div>
                    <label class="label" for="smtp_user">Username / From</label>
                    <input class="input" id="smtp_user" name="smtp_user" placeholder="tickets@example.com">
                </div>
            </div>
            <div>
                <label class="label" for="smtp_password">Password</label>
                <input class="input" id="smtp_password" name="smtp_password" type="password" autocomplete="new-password">
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" class="btn-primary" data-next="2">Next</button>
            </div>
        </section>

        <!-- Step 2: Event -->
        <section data-step="2" class="card gap-4 hidden">
            <h2 class="text-xl font-bold" style="font-family:var(--avo-font-display);">Your first event</h2>
            <div>
                <label class="label" for="orga_name">Organizer name</label>
                <input class="input" id="orga_name" name="orga_name" placeholder="Acme Events">
            </div>
            <div>
                <label class="label" for="event_title">Event title</label>
                <input class="input" id="event_title" name="event_title" placeholder="Summer Festival 2026">
            </div>
            <div>
                <label class="label" for="event_subtitle">Subtitle</label>
                <input class="input" id="event_subtitle" name="event_subtitle" placeholder="Open air · two stages">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="event_date">Date</label>
                    <input class="input" id="event_date" name="event_date" type="date">
                </div>
                <div>
                    <label class="label" for="event_time">Time</label>
                    <input class="input" id="event_time" name="event_time" type="time">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="event_tickets">Available tickets</label>
                    <input class="input" id="event_tickets" name="event_tickets" type="number" min="0" value="100">
                </div>
                <div>
                    <label class="label" for="event_price">Price (€)</label>
                    <input class="input" id="event_price" name="event_price" type="number" min="0" step="0.01" value="10">
                </div>
            </div>
            <div>
                <label class="label" for="payment_methods">Payment methods</label>
                <select class="input" id="payment_methods" name="payment_methods">
                    <option value="both">Cash &amp; card (Stripe)</option>
                    <option value="cash">Cash only</option>
                    <option value="stripe">Card only (Stripe)</option>
                </select>
            </div>
            <div class="flex justify-between pt-2">
                <button type="button" class="btn-outline" data-prev="1">Back</button>
                <button type="button" class="btn-primary" data-next="3">Next</button>
            </div>
        </section>

        <!-- Step 3: Admin -->
        <section data-step="3" class="card gap-4 hidden">
            <h2 class="text-xl font-bold" style="font-family:var(--avo-font-display);">Admin account</h2>
            <p class="text-sm" style="color: var(--avo-text-muted);">Sets the password for the <code>admin</code> account. Minimum 8 characters.</p>
            <div>
                <label class="label" for="admin_password">Admin password</label>
                <input class="input" id="admin_password" name="admin_password" type="password" autocomplete="new-password" required minlength="8">
            </div>
            <div>
                <label class="label" for="admin_password_confirm">Confirm password</label>
                <input class="input" id="admin_password_confirm" name="admin_password_confirm" type="password" autocomplete="new-password" required minlength="8">
            </div>
            <div class="flex justify-between pt-2">
                <button type="button" class="btn-outline" data-prev="2">Back</button>
                <button type="submit" class="btn-primary">Finish setup</button>
            </div>
        </section>
    </form>
<?php endif; ?>
</main>

<script>
(function () {
    var form = document.getElementById('setupForm');
    if (!form) return;
    var sections = form.querySelectorAll('[data-step]');
    var dots = document.querySelectorAll('.setup-dot');

    function show(n) {
        sections.forEach(function (s) {
            s.classList.toggle('hidden', s.getAttribute('data-step') !== String(n));
        });
        dots.forEach(function (d) {
            var active = d.getAttribute('data-dot') === String(n);
            d.style.color = active ? 'var(--avo-text)' : 'var(--avo-text-muted)';
            d.style.fontWeight = active ? '700' : '400';
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    form.addEventListener('click', function (e) {
        var nx = e.target.getAttribute('data-next');
        var pv = e.target.getAttribute('data-prev');
        if (nx) { show(nx); }
        if (pv) { show(pv); }
    });

    show(1);
})();
</script>
</body>
</html>

<?php
/**
 * Shared <head> for QrGate (avocloud design system).
 *
 * Set these BEFORE including, all optional:
 *   $assetBase  string  web-relative path to /frontend root. '' for top-level
 *                       pages, '../' for admin/, '../../' for admin/handheld/,
 *                       '../' for screens/. Default ''.
 *   $pageTitle  string  document title. Default 'QrGate'.
 *   $faviconUrl string  favicon href. Default = organizer logo from the API
 *                       (white-label), else avocloud app icon.
 *   $forceDark  bool    force dark theme + no persisted toggle (kiosk screens).
 *   $extraHead  string  raw HTML appended inside <head> (Stripe, manifest, …).
 *
 * LOAD ORDER guaranteed here: tailwind → basecoat → avocloud.css (last).
 */
$assetBase  = $assetBase  ?? '';
$pageTitle  = $pageTitle  ?? 'QrGate';
$forceDark  = $forceDark  ?? false;
$extraHead  = $extraHead  ?? '';
if (!isset($faviconUrl)) {
    // Same-origin image path (nginx proxies /api/image/ to the backend); falls
    // back to the bundled icon if config isn't loaded.
    $faviconUrl = defined('PUBLIC_API_BASE')
        ? PUBLIC_API_BASE . '/api/image/get/logo.png?t=' . time()
        : $assetBase . 'assets/img/avocloud-appicon-dark.svg';
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="theme-color" content="#0B0B0B">

    <!-- early theme guard: apply stored preference before first paint (no FOUC) -->
    <script>
        (function () {
            try {
                <?php if ($forceDark): ?>
                document.documentElement.classList.add('dark');
                <?php else: ?>
                var t = localStorage.getItem('avo-theme');
                var sysDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                // explicit choice wins; otherwise follow the OS/system preference
                if (t === 'light') { document.documentElement.classList.remove('dark'); }
                else if (t === 'dark') { document.documentElement.classList.add('dark'); }
                else if (sysDark) { document.documentElement.classList.add('dark'); }
                else { document.documentElement.classList.remove('dark'); }
                <?php endif; ?>
            } catch (e) { document.documentElement.classList.add('dark'); }
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

    <!-- avocloud.css MUST load after basecoat so its token remap wins -->
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/avocloud.css">
    <?php if (!$forceDark): ?>
    <script src="<?php echo $assetBase; ?>assets/theme.js" defer></script>
    <?php endif; ?>

    <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <?php echo $extraHead; ?>
</head>

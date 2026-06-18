<?php
/**
 * Shared footer for QrGate (avocloud design system).
 *
 * Optional vars (set before include):
 *   $assetBase   string  web-relative path to /frontend root (see head.php). Default ''.
 *   $orgName     string  organizer name shown before "Powered by". Default ''.
 *   $current_language string 'de' | 'en' — picks privacy-link label. Default 'en'.
 *   $privacyHref string  href to the privacy page. Default $assetBase.'datenschutz.php'.
 *   $showToggle  bool    show light/dark toggle button. Default true.
 */
$assetBase   = $assetBase   ?? '';
$orgName     = $orgName     ?? '';
$current_language = $current_language ?? 'en';
$privacyHref = $privacyHref ?? ($assetBase . 'datenschutz.php');
$showToggle  = $showToggle  ?? true;
$privacyLabel = ($current_language === 'de') ? 'Datenschutzerklärung' : 'Privacy Policy';
?>
<div class="avo-topbar" aria-hidden="true"></div>
<footer class="mt-12 px-6 py-6 flex flex-col items-center gap-3 text-center" style="color: var(--avo-text-muted);">
    <div class="avo-kicker">// tools that just run</div>
    <div class="flex items-center gap-1.5 text-xs" style="color: var(--avo-text-muted);">
        <?php if ($orgName !== ''): ?><?php echo htmlspecialchars($orgName); ?> &mdash; <?php endif; ?>Powered by
        <a href="https://avocloud.net" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1.5 transition-colors"
           style="font-family:var(--avo-font-display);font-weight:800;letter-spacing:-0.01em;color:var(--avo-text);">
            <!-- avocloud bracket mark · adaptive (brackets = currentColor, cursor = coral) -->
            <svg viewBox="0 0 72 72" fill="none" class="h-4 w-4" aria-hidden="true">
                <path d="M22 18 L12 18 L12 54 L22 54" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M50 18 L60 18 L60 54 L50 54" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M26 30 L33 36 L26 42" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="40" y="28" width="4" height="16" rx="2" fill="#FF6B4A"/>
            </svg>
            <span>AVOCLOUD<span style="color:var(--avo-text-muted);font-weight:500;">.NET</span></span>
        </a>
    </div>
    <div class="flex items-center gap-4">
        <a href="<?php echo htmlspecialchars($privacyHref); ?>" class="avo-link text-xs"><?php echo $privacyLabel; ?></a>
        <?php if ($showToggle): ?>
        <button type="button" class="avo-theme-toggle" data-avo-theme-toggle aria-label="Toggle theme">
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
            </svg>
        </button>
        <?php endif; ?>
    </div>
</footer>

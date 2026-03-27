<?php
require '../connections/db.php';

// Redirect based on existing session
if (isset($_SESSION['user_id'])) {
    $role = strtolower($_SESSION['role'] ?? '');
    if ($role === 'admin' || $role === 'super') {
        header("Location: {$base}admin/index.php");
        exit();
    } elseif ($role === 'teacher' || $role === 'staff') {
        header("Location: {$base}staff/index.php");
        exit();
    } elseif ($role === 'student') {
        header("Location: {$base}student/index.php");
        exit();
    }
}

// Fetch school config
$stmt = $conn->prepare('SELECT * FROM school_config LIMIT 1');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if (($result->maintenance_mode ?? 0) == 1) {
    if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'super'])) {
        header("Location: {$base}maintenance.php");
        exit();
    }
}

// Fallbacks
$school_name    = $result->school_name    ?? 'School Portal';
$school_tagline = $result->school_tagline ?? 'Excellence in Education';
$school_logo    = $result->school_logo    ?? '';
$primary_color = $result->pwa_theme_color ?? ($result->school_primary ?? '#16a34a');
$logo_url = $school_logo ? $base . ltrim($school_logo, '/') : '';

$dev_year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($school_name) ?> – Loading</title>

    <!-- PWA Meta -->
    <link rel="manifest" href="<?= $base ?>manifest.php">
    <meta name="theme-color" content="<?= htmlspecialchars($primary_color) ?>">

    <?php if ($logo_url): ?>
    <link rel="icon" type="image" href="<?= htmlspecialchars($logo_url) ?>">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        /* ── Reset ─────────────────────────────── */
        *, *::before, *::after {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        /* ── CSS Variables from PHP ──────────────*/
        :root {
            --primary: <?= htmlspecialchars($primary_color) ?>;
            --primary-dark: color-mix(in srgb, var(--primary) 70%, #000);
            --primary-light: color-mix(in srgb, var(--primary) 85%, #fff);
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            -webkit-font-smoothing: antialiased;
            user-select: none;
            /* Prevent elastic overscroll revealing white gaps */
            overscroll-behavior: none;
            background: var(--primary);
        }

        /* ── Splash Container ────────────────────*/
        #splash {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            /* Multi-layer gradient for depth */
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -10%,
                    color-mix(in srgb, var(--primary) 60%, #fff) 0%,
                    transparent 70%),
                radial-gradient(ellipse 60% 50% at 110% 110%,
                    color-mix(in srgb, var(--primary) 50%, #000) 0%,
                    transparent 65%),
                radial-gradient(ellipse 50% 40% at -10% 110%,
                    color-mix(in srgb, var(--primary) 55%, #fff) 0%,
                    transparent 60%);
            padding: env(safe-area-inset-top, 20px) 32px env(safe-area-inset-bottom, 20px);
            z-index: 9999;
        }

        /* ── Decorative Blobs ────────────────────*/
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.18;
            pointer-events: none;
        }
        .blob-1 {
            width: 280px; height: 280px;
            background: #fff;
            top: -60px; right: -60px;
            animation: blob-drift 8s ease-in-out infinite alternate;
        }
        .blob-2 {
            width: 200px; height: 200px;
            background: color-mix(in srgb, var(--primary) 30%, #fff);
            bottom: -40px; left: -40px;
            animation: blob-drift 10s ease-in-out infinite alternate-reverse;
        }
        .blob-3 {
            width: 150px; height: 150px;
            background: #fff;
            bottom: 20%; right: 10%;
            animation: blob-drift 6s ease-in-out infinite alternate;
            opacity: 0.1;
        }

        @keyframes blob-drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(20px, 20px) scale(1.08); }
        }

        /* ── Main Content ────────────────────────*/
        .splash-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            position: relative;
            z-index: 1;
        }

        /* ── Logo Ring ───────────────────────────*/
        .logo-ring {
            position: relative;
            width: 120px; height: 120px;
            margin-bottom: 28px;
            animation: logo-pop 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            opacity: 0;
            animation-delay: 0.15s;
        }

        .logo-ring-outer {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.25);
            animation: ring-spin 12s linear infinite;
        }

        .logo-ring-outer::before {
            content: '';
            position: absolute;
            top: -3px; left: 50%;
            transform: translateX(-50%);
            width: 8px; height: 8px;
            background: rgba(255,255,255,0.7);
            border-radius: 50%;
        }

        @keyframes ring-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .logo-bg {
            width: 120px; height: 120px;
            border-radius: 32px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.4);
            overflow: hidden;
        }

        .logo-bg img {
            width: 78px; height: 78px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.15));
        }

        /* Fallback icon when no logo */
        .logo-fallback {
            font-size: 48px;
            color: rgba(255,255,255,0.9);
        }

        @keyframes logo-pop {
            from { opacity: 0; transform: scale(0.6) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* ── School Name ─────────────────────────*/
        .school-name {
            font-size: clamp(22px, 6vw, 30px);
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-align: center;
            line-height: 1.15;
            text-shadow: 0 2px 12px rgba(0,0,0,0.15);
            opacity: 0;
            animation: fade-up 0.55s ease-out forwards;
            animation-delay: 0.5s;
            padding: 0 8px;
        }

        /* ── Tagline Divider ─────────────────────*/
        .tagline-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0 10px;
            opacity: 0;
            animation: fade-up 0.55s ease-out forwards;
            animation-delay: 0.7s;
        }
        .tagline-divider span {
            height: 1px;
            width: 40px;
            background: rgba(255,255,255,0.35);
            border-radius: 1px;
        }
        .tagline-dot {
            width: 5px; height: 5px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
        }

        /* ── Tagline ─────────────────────────────*/
        .school-tagline {
            font-size: clamp(11px, 3.2vw, 13px);
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            text-align: center;
            opacity: 0;
            animation: fade-up 0.55s ease-out forwards;
            animation-delay: 0.8s;
            padding: 0 20px;
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Loading Dots ────────────────────────*/
        .loading-dots {
            display: flex;
            gap: 7px;
            margin-top: 44px;
            opacity: 0;
            animation: fade-up 0.4s ease-out forwards;
            animation-delay: 1.1s;
        }
        .loading-dots span {
            width: 7px; height: 7px;
            background: rgba(255,255,255,0.55);
            border-radius: 50%;
            animation: dot-pulse 1.4s ease-in-out infinite;
        }
        .loading-dots span:nth-child(1) { animation-delay: 0s; }
        .loading-dots span:nth-child(2) { animation-delay: 0.22s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.44s; }

        @keyframes dot-pulse {
            0%, 80%, 100% { transform: scale(0.7); opacity: 0.35; }
            40%            { transform: scale(1.15); opacity: 1; background: #fff; }
        }

        /* ── Developer Credit ────────────────────*/
        .dev-credit {
            position: absolute;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);
            left: 0; right: 0;
            text-align: center;
            font-size: 11.5px;
            font-weight: 400;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.02em;
            opacity: 0;
            animation: fade-up 0.5s ease-out forwards;
            animation-delay: 1.3s;
            padding: 0 16px;
        }
        .dev-credit a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .dev-credit a:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* ── Splash Exit Animation ───────────────*/
        #splash.splash-exit {
            animation: splash-out 0.5s cubic-bezier(0.4, 0, 1, 1) forwards;
        }
        @keyframes splash-out {
            from { opacity: 1; transform: scale(1); }
            to   { opacity: 0; transform: scale(1.04); }
        }


    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════ SPLASH ══ -->
<div id="splash">

    <!-- Decorative blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Main centred content -->
    <div class="splash-content">

        <!-- Logo -->
        <div class="logo-ring">
            <div class="logo-ring-outer"></div>
            <div class="logo-bg">
                <?php if ($logo_url): ?>
                    <img src="<?= htmlspecialchars($logo_url) ?>"
                         alt="<?= htmlspecialchars($school_name) ?> Logo">
                <?php else: ?>
                    <span class="logo-fallback">🏫</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- School Name -->
        <h1 class="school-name"><?= htmlspecialchars(strtoupper($school_name)) ?></h1>

        <!-- Divider -->
        <div class="tagline-divider">
            <span></span>
            <div class="tagline-dot"></div>
            <span></span>
        </div>

        <!-- Tagline -->
        <p class="school-tagline"><?= htmlspecialchars($school_tagline) ?></p>

        <!-- Animated loading dots -->
        <div class="loading-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>

    </div><!-- /.splash-content -->



    <!-- Developer Credit -->
    <p class="dev-credit">
        <?= $dev_year ?> &copy; App Developed by
        <a href="mailto:psalmkheed123@gmail.com">@BlaqDev</a>
    </p>

</div>
<!-- ═══════════════════════════════════════════════════════ -->

<script>
    // ── Service Worker Registration (required for PWA installability) ──────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= $base ?>sw.js')
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.warn('SW registration failed:', err));
    }





    const SPLASH_MS = 2800; 
    let redirectTimer = setTimeout(performRedirect, SPLASH_MS);

    function performRedirect() {
        const splash = document.getElementById('splash');
        if (splash) splash.classList.add('splash-exit');

        // Check if the user has an active session before deciding where to go
        fetch('<?= $base ?>auth/check_login.php')
            .then(r => r.json())
            .then(data => {
                let dest = '<?= $base ?>auth/login.php'; // default fallback
                if (data.loggedIn) {
                    const role = (data.role || '').toLowerCase();
                    if      (role === 'admin' || role === 'super')   dest = '<?= $base ?>admin/index.php';
                    else if (role === 'staff') dest = '<?= $base ?>staff/index.php';
                    else if (role === 'student') dest = '<?= $base ?>student/index.php';
                }
                setTimeout(() => window.location.replace(dest), 480);
            })
            .catch(() => {
                // Network error fallback — just go to login
                setTimeout(() => window.location.replace('<?= $base ?>auth/login.php'), 480);
            });
    }


</script>

</body>
</html>


<?php
require __DIR__ . '/../connections/db.php';

// Build APP_URL from the actual request host so it works on any device
// (localhost on the PC, the PC's IP on a phone, etc.)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
DEFINE("APP_URL", $scheme . '://' . $_SERVER['HTTP_HOST'] . '/school_app/');
$base = APP_URL;

$role = $_SESSION['role'];

?>

<?php
$stmt = $conn->prepare('SELECT * FROM school_config');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);


$primary = $result->school_primary;
$secondary = $result->school_secondary;

// --- GLOBAL CURRENT SESSION FETCH ---
if (!isset($_SESSION['active_session']) || empty($_SESSION['active_session'])) {
      // 1. Try to find the term active TODAY, or the next upcoming one
      $sess_stmt = $conn->prepare("
            SELECT id, session, term, session_end_date 
            FROM sch_session 
            WHERE session_end_date >= CURRENT_DATE 
            ORDER BY session_start_date ASC 
            LIMIT 1
      ");
      $sess_stmt->execute();
      $active_session = $sess_stmt->fetch(PDO::FETCH_OBJ);
      
      // 2. If no current/future session, get the very last one that ended
      if (!$active_session) {
            $sess_stmt = $conn->prepare("SELECT id, session, term, session_end_date FROM sch_session ORDER BY session_end_date DESC LIMIT 1");
            $sess_stmt->execute();
            $active_session = $sess_stmt->fetch(PDO::FETCH_OBJ);
      }
      
      if ($active_session) {
            $_SESSION['active_session'] = $active_session->session;
            $_SESSION['active_term'] = $active_session->term;
            $_SESSION['active_session_id'] = $active_session->id;
            $_SESSION['session_end_date'] = $active_session->session_end_date;
      }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <title><?= strtoupper(explode(' ', $result->school_name)[0]) ?? '' ?> CBT Portal <?= ucfirst($role) ?? '' ?> - Dashboard
      </title>
      <link rel="manifest" href="/school_app/manifest.php">
      <meta name="theme-color" content="<?= $primary ?>">
      <meta name="mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <link rel="icon" type="image" href="<?= $base ?>uploads/school_logo/<?= $result->school_logo ?>" />
      <link href="<?= $base ?>src/fontawesome.css" rel="stylesheet">
      <link href="<?= $base ?>src/swiper.css" rel="stylesheet">
      <link href="<?= $base ?>src/boxicons.css" rel="stylesheet">
      <link href="<?= $base ?>src/output.css?v=<?= time() ?>" rel="stylesheet">
      <link href="<?= $base ?>src/input.css?v=<?= time() ?>" rel="stylesheet">
      <script src="<?= $base ?>src/jquery.js"></script>
      <script src="<?= $base ?>src/swiper-bundle.js"></script>
      <script src="<?= $base ?>src/sweetAlert.js"></script>

      <!-- Premium Tooltips (Tippy.js) -->
      <script src="https://unpkg.com/@popperjs/core@2"></script>
      <script src="https://unpkg.com/tippy.js@6"></script>
      <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/shift-away.css" />
      <style>
            .ck-content {
                  font-family: sans-serif;
            }

            .ck-content h1 {
                  font-size: 2em;
                  font-weight: 700;
                  margin: 0.67em 0;
                  line-height: 1.2;
            }

            .ck-content h2 {
                  font-size: 1.5em;
                  font-weight: 700;
                  margin: 0.75em 0;
                  line-height: 1.3;
            }

            .ck-content h3 {
                  font-size: 1.25em;
                  font-weight: 600;
                  margin: 0.83em 0;
                  line-height: 1.4;
            }

            .ck-content h4 {
                  font-size: 1.1em;
                  font-weight: 600;
                  margin: 1em 0;
                  line-height: 1.4;
            }

            .ck-content h5 {
                  font-size: 1em;
                  font-weight: 600;
                  margin: 1.1em 0;
                  line-height: 1.4;
            }

            .ck-content h6 {
                  font-size: 0.9em;
                  font-weight: 600;
                  margin: 1.2em 0;
                  line-height: 1.4;
            }

            .ck-content p {
                  margin: 0.5em 0;
            }

            .ck-content ul {
                  list-style-type: disc;
                  padding-left: 1.5em;
                  margin: 0.5em 0;
            }

            .ck-content ol {
                  list-style-type: decimal;
                  padding-left: 1.5em;
                  margin: 0.5em 0;
            }

            .ck-content li {
                  margin: 0.25em 0;
            }

            .ck-content blockquote {
                  border-left: 4px solid #d1d5db;
                  padding-left: 1em;
                  color: #6b7280;
                  margin: 1em 0;
                  font-style: italic;
            }

            .ck-content strong {
                  font-weight: 700;
            }

            .ck-content em {
                  font-style: italic;
            }

            .ck-content code {
                  background: #f3f4f6;
                  border-radius: 4px;
                  padding: 0.1em 0.4em;
                  font-family: monospace;
                  font-size: 0.9em;
            }

            .ck-content pre {
                  background: #1e293b;
                  color: #e2e8f0;
                  padding: 1em;
                  border-radius: 6px;
                  overflow-x: auto;
                  font-family: monospace;
                  font-size: 0.9em;
            }

            .ck-content ul a:hover {
                  text-decoration: underline;
            }

            hr {
                  border: 1.5px solid #f3f3f3;
            }

            .inline-ad {
                  /* text-align: center; */
                  margin: 30px 0;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  animation: fadeIn 0.8s ease;
            }

            .inline-ad ul a li {
                  background: white;
                  padding: 10px;
                  width: 400px;
                  margin: 0;
            }

            @keyframes fadeIn {
                  from {
                        opacity: 0;
                  }

                  to {
                        opacity: 1;
                  }
            }

            .inline-ad img {
                  border-radius: 10px;
            }
      </style>

      <script type="importmap">
      {
            "imports": {
                  "ckeditor5": "/school_app/assets/ckeditor/ckeditor5/ckeditor5.js?v=<?= time() ?>",
                        "ckeditor5/": "/school_app/assets/ckeditor/ckeditor5/"
                  }
            }
            </script>
      <script type="module" src="/school_app/assets/ckeditor/main.js?v=<?= time() ?>"
            onerror="console.error('Fatal: Failed to load main.js module file'); alert('Editor script loading failed.');">
            </script>
      <script>
            console.log('Loader: Root Header diagnostics running...');
            (function checkEditorExposed() {
                  const checkInterval = setInterval(() => {
                        if (window.ClassicEditor) {
                              console.log('Loader: SUCCESS - ClassicEditor available globally.');
                              clearInterval(checkInterval);
                        }
                  }, 500);
                  setTimeout(() => { clearInterval(checkInterval); }, 5000);
            })();
      </script>
</head>

<body class="select-none user-<?= $role ?>">
      <div class="w-full">
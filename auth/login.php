<?php
require '../connections/db.php';

// redirect to configuration page if school settings is not yet configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();

$result = $check_config->fetch(PDO::FETCH_OBJ);

if ($check_config->rowCount() < 1) {
      session_unset();
      session_destroy();
      header("Location: /school_app/index.php");
}
;
// -------------------------------------------------------------//

// redirect users base on user role
// -----------------------------------------------------------------//

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
      header("Location: /school_app/admin/index.php");
      exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'staff') {
      header("Location: /school_app/staff/index.php");
      exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
      header("Location: /school_app/student/index.php");
      exit();
}

// -------------------------------------------------------------------------//

// App developer credit
// ------------------------------------------------------------------------ //

$dev_name = '<p class="absolute bottom-0 left-0 right-0 text-center text-sm text-gray-700 md:mt-8">' . date('Y') . "&copy; App Developed by <a href='mailto:[psalmkheed123@gmail.com]'>@BlaqDev</a> </p>";

// -------------------------------------------------------------------------//
?>

<!DOCTYPE html>
<html lang="en">

<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <title><?= strtoupper(explode(' ', $result->school_name)[0]) ?> CBT Portal - Login</title>
      <link rel="manifest" href="/school_app/manifest.php">
      <meta name="theme-color" content="<?= $result->school_primary ?>">
       <meta name="mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <link rel="icon" type="image" href="../uploads/school_logo/<?= $result->school_logo ?>" />
      <link href="../src/output.css" rel="stylesheet">
      <link href="../src/input.css" rel="stylesheet">
      <link href="../src/boxicons.css" rel="stylesheet">
      <script src="../src/jquery.js"></script>
      <script>
            // Early SW registration for PWA installability
            if ('serviceWorker' in navigator) {
                  navigator.serviceWorker.register('/school_app/sw.js')
                        .catch(function(err){ console.warn('SW:', err); });
            }
            // Fullscreen API: hide address bar on first tap (browser-only fallback)
            (function(){
                  var installed = window.matchMedia('(display-mode: fullscreen)').matches ||
                                  window.matchMedia('(display-mode: standalone)').matches ||
                                  !!navigator.standalone;
                  if (!installed) {
                        function goFull() {
                              var d = document.documentElement;
                              if (d.requestFullscreen)            d.requestFullscreen();
                              else if (d.webkitRequestFullscreen) d.webkitRequestFullscreen();
                        }
                        document.addEventListener('touchstart', goFull, {once:true});
                        document.addEventListener('click',      goFull, {once:true});
                  }
            })();
      </script>
      <style>
            /* ── PWA Install Banner ─────────────────────────────────── */
            #install-banner {
                  position: fixed;
                  bottom: 16px; left: 16px; right: 16px;
                  background: #1a1a1a;
                  background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
                  color: white;
                  padding: 16px;
                  border-radius: 18px;
                  display: none; /* Shown by JS */
                  align-items: center;
                  gap: 14px;
                  z-index: 10000;
                  box-shadow: 0 12px 30px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.08);
                  transform: translateY(120%);
                  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                  font-family: 'Outfit', sans-serif;
            }
            #install-banner.visible { transform: translateY(0); }
            .install-icon {
                  width: 44px; height: 44px;
                  background: rgba(255,255,255,0.1);
                  border-radius: 12px;
                  display: flex; align-items: center; justify-content: center;
                  font-size: 22px;
            }
            .install-text { flex: 1; }
            .install-text strong { display: block; filter: brightness(1.2); font-size: 14px; }
            .install-text small { color: rgba(255,255,255,0.6); font-size: 11px; }
            .install-btn {
                  background: white; color: black;
                  border: none; border-radius: 10px;
                  padding: 8px 16px; font-weight: 700; font-size: 12px;
                  cursor: pointer; transition: transform 0.2s;
            }
            .install-btn:active { transform: scale(0.95); }
            .install-close {
                  position: absolute; top: 6px; right: 8px;
                  background: none; border: none; color: rgba(255,255,255,0.4);
                  font-size: 18px; cursor: pointer;
            }
      </style>
</head>

<body
      class="bg-green-100 text-gray-800 dark:bg-gray-900 dark:text-gray-500 min-h-screen w-full flex items-center justify-center select-none">

      <div class="fadeIn md:w-[70%] w-[90%] bg-white flex justify-center shadow-[0_20px_50px_rgba(0,0,0,0.1)] rounded-3xl overflow-hidden md:mb-4">

            <!-- left Div -->
            <div
                  class="hidden md:flex flex-col justify-between w-[50%] bg-green-600 text-white p-8">
                  <div class="flex flex-col items-start">
                        <div class="flex gap-3 items-center mb-10">
                              <img src="../uploads/school_logo/<?= $result->school_logo ?>" alt=""
                                    class="size-12 border-2 border-white/20 rounded-xl object-contain select-none shadow-sm" />
                              <div>
                                    <h3 class="text-2xl font-black leading-tight"><?= strtoupper(explode(' ', $result->school_name)[0]) ?></h3>
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-green-100"><?= $result->school_tagline ?></p>
                              </div>
                        </div>

                        <img src="/school_app/src/office-workplace.svg" alt="" class="w-full h-72 object-contain m-auto" />
                  </div>
                  <div class="mt-auto">
                        <h5 class="text-2xl font-bold mb-1">Welcome Back!</h5>
                        <p class="text-green-100 text-sm opacity-90">Please login to access your academic dashboard and records.</p>
                  </div>
            </div>

            <!-- right Div -->
            <div class="bg-white md:w-[50%] w-full h-full">
                  <form id="loginForm"
                        class="p-8 md:p-10 md:pt-8 h-full flex flex-col justify-center border-l border-gray-50">
                        
                        <!-- Mobile Only Logo -->
                        <div class="md:hidden flex flex-col items-center mb-8">
                              <div class="w-20 h-20 rounded-2xl bg-gray-50 flex items-center justify-center p-3 mb-3 shadow-sm border border-gray-100">
                                    <img src="../uploads/school_logo/<?= $result->school_logo ?>" alt="School Logo" class="w-full h-full object-contain">
                              </div>
                              <h3 class="text-xl font-black text-gray-800"><?= strtoupper($result->school_name) ?></h3>
                              <div class="h-1 w-10 bg-green-500 rounded-full mt-2"></div>
                        </div>

                        <h3 class="text-start text-3xl font-black text-gray-800 mb-2 md:block hidden">Login</h3>
                        <p class="text-gray-400 text-sm mb-8 md:block hidden">Enter your credentials to continue</p>

                        <div class="mb-5 flex flex-col gap-1.5">
                              <label for="user_id"
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest">User ID</label>
                              <div class="relative">
                                    <i
                                          class="bx bx-user text-lg absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" placeholder="Enter your user id" name="user_id" id="user_id"
                                          class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-3.5 pl-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 focus:bg-white transition-all duration-300">
                              </div>
                        </div>
                        <div class="mb-6 flex flex-col gap-1.5">
                              <label for="password"
                                    class="text-xs font-bold text-gray-400 uppercase tracking-widest">Password</label>
                              <div class="relative">
                                    <i
                                          class="bx bx-lock text-lg absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="password" placeholder="Enter your password" name="password"
                                          id="password"
                                          class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-3.5 pl-12 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 focus:bg-white transition-all duration-300">
                                    <button type="button"
                                          class="toggle-pw absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-green-600 transition-colors cursor-pointer"
                                          data-target="password">
                                          <i class="bxs-eye-closed text-xl"></i>
                                    </button>
                              </div>
                        </div>
                        <button type="submit" name="login" id="loginBtn"
                              class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 shadow-lg shadow-green-200 active:scale-[0.98] transition-all duration-200 font-bold text-base cursor-pointer">
                              <span>Sign In</span>
                              <i class="bxs-arrow-right-stroke text-xl"></i>
                        </button>

                        <div class="mt-8 pt-6 border-t border-gray-50 text-center">
                              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Help & Support</p>
                              <p class="text-sm text-gray-500 mt-2">Forgot Password? Contact the administrator</p>
                        </div>

                  </form>
            </div>

      </div>

</body>
<script src="../src/scripts.js"></script>


<script>

      // Login AJAX
      $("#loginForm").on("submit", function (e) {
            e.preventDefault();

            // Hide any previous errors
            $("#auth_error").addClass("hidden").html("");

            $.ajax({
                  url: "/school_app/auth/login_auth.php",
                  method: "POST",
                  dataType: "json",
                  data: {
                        login: true,
                        user_id: $("#user_id").val(),
                        password: $("#password").val()
                  },
                  beforeSend: () => {
                        $("#loginBtn").prop("disabled", true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>
                        `);
                  },
                  success: (data) => {
                        console.log("Login response:", data);

                        if (data.status === "success") {
                              const role = (data.role || "").toLowerCase().trim();
                              console.log("User role:", role);

                              switch (role) {
                                    case "admin":
                                          console.log("Redirecting to admin dashboard");
                                          window.location.href = "/school_app/admin/index.php";
                                          break;
                                    case "student":
                                          console.log("Redirecting to student dashboard");
                                          window.location.href = "/school_app/student/index.php";
                                          break;
                                    case "staff":
                                          console.log("Redirecting to staff dashboard");
                                          window.location.href = "/school_app/staff/index.php";
                                          break;
                                    default:
                                          console.log("Unknown role, redirecting to default index");
                                          window.location.href = "/school_app/index.php";
                              }
                        } else {
                              showAlert('error', data.message);
                        }
                  },

                  error: () => {
                        showAlert('error', 'An error occured. Please try again.');
                  },
                  complete: () => {
                        $("#loginBtn").prop("disabled", false).text("Login");
                  }
            });
      });
      // Toggle password visibility
      document.querySelectorAll('.toggle-pw').forEach(btn => {
            btn.addEventListener('click', () => {
                  const input = document.getElementById(btn.dataset.target);
                  const icon = btn.querySelector('i');
                  if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'bx-eye text-lg';
                  } else {
                        input.type = 'password';
                        icon.className = 'bx-eye-closed text-lg';
                  }
            });
      });
</script>

      <!-- PWA Install Banner -->
      <div id="install-banner">
            <div class="install-icon">📲</div>
            <div class="install-text">
                  <strong>Use the Web App</strong>
                  <small>Add to Home Screen for fullscreen view</small>
            </div>
            <button class="install-btn" id="installBtn">Install</button>
            <button class="install-close" id="installClose">&times;</button>
      </div>

      <script>
            // PWA Install Logic for Login Page
            (function() {
                  let deferredPrompt = null;
                  const banner = document.getElementById('install-banner');
                  const installBtn = document.getElementById('installBtn');
                  const closeBtn = document.getElementById('installClose');

                  const isInstalled = window.matchMedia('(display-mode: fullscreen)').matches ||
                                      window.matchMedia('(display-mode: standalone)').matches ||
                                      !!navigator.standalone;
                  
                  const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 0);
                  const dismissed = sessionStorage.getItem('install-banner-dismissed');

                  if (!isInstalled && !dismissed) {
                        // Wait 3 seconds then show
                        setTimeout(showBanner, 3000);

                        window.addEventListener('beforeinstallprompt', (e) => {
                              e.preventDefault();
                              deferredPrompt = e;
                              if (installBtn) installBtn.textContent = 'Install Now';
                        });

                        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
                        const isInSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

                        if (installBtn) {
                              installBtn.addEventListener('click', async () => {
                                    if (deferredPrompt) {
                                          deferredPrompt.prompt();
                                          const { outcome } = await deferredPrompt.userChoice;
                                          if (outcome === 'accepted') hideBanner();
                                          deferredPrompt = null;
                                    } else if (isIOS && isInSafari) {
                                          alert('Tap the Share button (□↑) at the bottom, then "Add to Home Screen".');
                                    } else {
                                          alert('To hide the browser UI:\n1. Open browser menu (⋮).\n2. Tap "Install app" or "Add to Home Screen".');
                                    }
                              });
                        }
                  }

                  function showBanner() {
                        if (!banner) return;
                        banner.style.display = 'flex';
                        setTimeout(() => banner.classList.add('visible'), 50);
                  }

                  function hideBanner() {
                        if (!banner) return;
                        banner.classList.remove('visible');
                        setTimeout(() => banner.style.display = 'none', 400);
                  }

                  if (closeBtn) {
                        closeBtn.addEventListener('click', () => {
                              sessionStorage.setItem('install-banner-dismissed', '1');
                              hideBanner();
                        });
                  }
            })();
      </script>
</body>

</html>
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
      header("Location: {$base}index.php");
}
;
// -------------------------------------------------------------//

// redirect users base on user role
// -----------------------------------------------------------------//

if (isset($_SESSION['user_id']) && in_array(strtolower($_SESSION['role']), ['admin', 'super'])) {
      header("Location: {$base}admin/index.php");
      exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'staff') {
      header("Location: {$base}staff/index.php");
      exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
      header("Location: {$base}student/index.php");
      exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'guardian') {
      header("Location: {$base}parent/index.php");
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
      <link rel="manifest" href="<?= $base ?>manifest.php">
      <meta name="theme-color" content="<?= $result->school_primary ?>">
      <link rel="icon" type="image" href="<?= $base . ltrim($result->school_logo ?? '', '/') ?>" />
      <link href="../src/output.css" rel="stylesheet">
      <link href="../src/input.css" rel="stylesheet">
      <link href="../src/boxicons.css" rel="stylesheet">
      <script src="../src/jquery.js"></script>
      <script>
            // Early SW registration
            if ('serviceWorker' in navigator) {
                  navigator.serviceWorker.register('<?= $base ?>sw.js')
                        .catch(function(err){ console.warn('SW:', err); });
            }
      </script>

</head>

<body
      class="bg-green-100 text-gray-800 dark:bg-gray-900 dark:text-gray-500 min-h-screen w-full flex items-center justify-center select-none">

      <div class="fadeIn md:w-[70%] w-[90%] bg-white flex justify-center shadow-[0_20px_50px_rgba(0,0,0,0.1)] rounded-3xl overflow-hidden md:mb-4">

            <!-- left Div -->
            <div
                  class="hidden md:flex flex-col justify-between w-[50%] bg-green-600 text-white p-6 md:p-8">
                  <div class="flex flex-col items-start">
                        <div class="flex gap-3 items-center mb-6">
                              <img src="<?= $base . ltrim($result->school_logo ?? '', '/') ?>" alt="School Logo"
                                    class="size-12 border-2 border-white/20 rounded-xl object-contain select-none shadow-sm bg-white" />
                              <div>
                                    <h3 class="text-xl font-black leading-tight"><?= strtoupper(explode(' ', $result->school_name)[0]) ?></h3>
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-green-100"><?= $result->school_tagline ?></p>
                              </div>
                        </div>

                        <img src="<?= $base ?>src/office-workplace.svg" alt="" class="w-full h-[300px] object-contain m-auto" />
                  </div>
                  <div class="mt-auto pt-4">
                        <h5 class="text-xl font-bold mb-1">Welcome Back!</h5>
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
                                    <img src="<?= $base . ltrim($result->school_logo ?? '', '/') ?>" alt="School Logo" class="w-full h-full object-contain">
                              </div>
                              <h3 class="text-xl font-semibold text-gray-800 text-center"><?= strtoupper($result->school_name) ?></h3>
                              <div class="h-1 w-10 bg-green-500 rounded-full mt-2"></div>
                        </div>

                        <h3 class="text-start text-3xl font-bold text-gray-800 mb-2 md:block hidden">Login</h3>
                        <p class="text-gray-400 text-sm mb-6 md:block hidden">Enter your credentials to continue</p>

                        <div class="mb-4 flex flex-col gap-1.5">
                              <label for="user_id"
                                    class="text-sm font-bold text-gray-600 tracking-widest">User ID</label>
                              <div class="relative">
                                    <i
                                          class="bx bx-user text-lg absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" placeholder="Enter your user id" name="user_id" id="user_id"
                                          class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-3 pl-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 focus:bg-white transition-all duration-300">
                              </div>
                        </div>
                        <div class="mb-6 flex flex-col gap-1.5">
                              <label for="password"
                                    class="text-sm font-bold text-gray-600  tracking-widest">Password</label>
                              <div class="relative">
                                    <i
                                          class="bx bx-lock text-lg absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="password" placeholder="Enter your password" name="password"
                                          id="password"
                                          class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-3 pl-12 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 focus:bg-white transition-all duration-300">
                                    <button type="button"
                                          class="toggle-pw absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-green-600 transition-colors cursor-pointer z-10"
                                          data-target="password">
                                          <i class="bxs-eye-closed text-xl"></i>
                                    </button>
                              </div>
                        </div>
                        <button type="submit" name="login" id="loginBtn"
                              class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 shadow-lg shadow-green-200 active:scale-[0.98] transition-all duration-200 font-bold text-base cursor-pointer">
                              <span>Sign In</span>
                              <i class="bxs-arrow-right-stroke text-xl"></i>
                        </button>

                        <div class="mt-6 pt-5 border-t border-gray-50 text-center flex flex-col gap-2">
                              <p class="text-[12px] font-semibold text-gray-600 uppercase tracking-wider">Help & Support</p>
                              <p class="text-sm text-gray-600 mt-1">Forgot Password? Contact the administrator</p>
                              <a href="../parent/register.php" class="text-sm font-bold text-green-600 hover:text-green-700 hover:underline inline-block">Register as Parent / Guardian</a>
                        </div>

                  </form>
            </div>

      </div>

</body>
<script src="../src/scripts.js"></script>


<script>
      $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('reason') === 'timeout') {
                  // Small delay to ensure script structure is set before animation fires
                  setTimeout(() => {
                        window.showToast('Session expired due to inactivity. Please login again.', 'error');
                  }, 500);
            }
      });

      // Login AJAX
      $("#loginForm").on("submit", function (e) {
            e.preventDefault();

            // Hide any previous errors
            $("#auth_error").addClass("hidden").html("");

            $.ajax({
                  url: "<?= $base ?>auth/login_auth.php",
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
                                          window.location.href = "<?= $base ?>admin/index.php";
                                          break;
                                    case "super":
                                          console.log("Redirecting to admin dashboard");
                                          window.location.href = "<?= $base ?>admin/index.php";
                                          break;
                                    case "student":
                                          console.log("Redirecting to student dashboard");
                                          window.location.href = "<?= $base ?>student/index.php";
                                          break;
                                    case "staff":
                                          console.log("Redirecting to staff dashboard");
                                          window.location.href = "<?= $base ?>staff/index.php";
                                          break;
                                    case "guardian":
                                          console.log("Redirecting to parent dashboard");
                                          window.location.href = "<?= $base ?>parent/index.php";
                                          break;
                                    default:
                                          console.log("Unknown role, redirecting to default index");
                                          window.location.href = "<?= $base ?>auth/login.php";
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

</script>
</body>

</html>

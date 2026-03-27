<?php
require '../connections/db.php';

// If already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'guardian') {
        header("Location: {$base}parent/index.php");
    } else {
        header("Location: {$base}auth/login.php");
    }
    exit();
}

$check_config = $conn->query('SELECT * FROM school_config LIMIT 1');

$check_config->execute();
$result = $check_config->fetch(PDO::FETCH_OBJ);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Parent Portal - Registration</title>
    <meta name="theme-color" content="<?= $result->school_primary ?? '#1d4ed8' ?>">
    <link href="../src/output.css" rel="stylesheet">
    <link href="../src/input.css" rel="stylesheet">
    <link href="../src/boxicons.css" rel="stylesheet">
    <script src="../src/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 font-sans select-none">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden relative fade-in">
        <div class="absolute top-0 w-full h-2 bg-gradient-to-r from-green-600 to-green-500"></div>
        <div class="p-8">
            <div class="text-center mb-8">
                <?php if (!empty($result->school_logo)): ?>
                                            <img src="<?= $base . ltrim($result->school_logo ?? '', '/') ?>" class="h-16 mx-auto mb-4 object-contain">
                <?php else: ?>
                    <div class="w-16 h-16 bg-green-100 rounded-2xl mx-auto flex items-center justify-center mb-4">
                        <i class="bx bxs-building-house text-3xl text-green-600"></i>
                    </div>
                <?php endif; ?>
                <h1 class="text-2xl font-semibold text-gray-800">Parent Portal</h1>
                <p class="text-sm font-semibold text-gray-500 mt-1">Create an account to monitor your ward(s).</p>
            </div>

            <form id="registerForm" class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-3">
                    <div class="w-full sm:w-1/2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">First Name</label>
                        <input type="text" name="first_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-all">
                    </div>
                    <div class="w-full sm:w-1/2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Surname</label>
                        <input type="text" name="surname" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Email (Username)</label>
                    <input type="email" name="user_id" required placeholder="e.g. parent@example.com" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-all">
                        <i class="bx bx-show absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl cursor-pointer hover:text-green-500 transition-colors" id="togglePassword"></i>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-200 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                        Create Account <i class="bx bx-right-arrow-alt text-xl"></i>
                    </button>
                </div>
            </form>

            <p class="text-center mt-8 text-sm font-medium text-gray-500">
                Already have an account? <a href="../auth/login.php" class="text-green-600 hover:underline font-bold">Sign In here</a>
            </p>
        </div>
    </div>

    <script>
        $('#togglePassword').on('click', function() {
            let type = $('#password').attr('type') === 'password' ? 'text' : 'password';
            $('#password').attr('type', type);
            $(this).toggleClass('bx-show bx-hide');
        });

        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#submitBtn');
            let ogText = btn.html();
            btn.html('<i class="bx bxs-loader-dots bx-spin text-xl"></i> Creating...').prop('disabled', true);

            $.ajax({
                url: 'auth/parent_api.php?action=register',
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            title: 'Welcome!',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            window.location.href = '../auth/login.php';
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        btn.html(ogText).prop('disabled', false);
                    }
                },
                error: function() {
                    Swal.fire('Network Error', 'Please try again later.', 'error');
                    btn.html(ogText).prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html>

<?php
require 'connections/db.php';

$stmt = $conn->query("SELECT school_name, school_logo, maintenance_mode FROM school_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config || $config['maintenance_mode'] != 1) {
    // If maintenance mode is off, redirect to index/login
    header("Location: {$base}index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Under Maintenance - <?= htmlspecialchars($config['school_name'] ?? 'School Portal') ?></title>
    <link href="<?= rtrim($base, '/') ?>/src/output.css?v=<?= time()  ?>" rel="stylesheet">
    <link href="<?= rtrim($base, '/') ?>/src/boxicons.css?v=<?= time() ?>" rel="stylesheet">
    <link rel="icon" type="image" href="<?= rtrim($base, '/') . '/' . ltrim($config['school_logo'] ?? '', '/') ?>" />
</head>
<body class="bg-gray-50 flex flex-col items-center justify-center min-h-screen p-4 text-center select-none">
    
    <div class="fadeIn max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl shadow-gray-200 border border-gray-100 flex flex-col items-center relative overflow-hidden">
        
        <div class="absolute -top-[100px] -right-[100px] size-[250px] bg-blue-50/50 rounded-full blur-3xl z-0"></div>
        <div class="absolute -bottom-[100px] -left-[100px] size-[250px] bg-orange-50/50 rounded-full blur-3xl z-0"></div>

        <div class="relative z-10 w-full flex flex-col items-center">
            <?php if(!empty($config['school_logo'])): ?>
                <img src="<?= rtrim($base, '/') . '/' . ltrim($config['school_logo'], '/') ?>" alt="Logo" class="h-16 mb-8 object-contain">
            <?php else: ?>
                <div class="h-16 w-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-8 shadow-sm">
                    <i class="bx bx-buildings text-3xl"></i>
                </div>
            <?php endif; ?>

            <div class="size-20 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center text-4xl mb-6 shadow-sm border border-orange-100 relative">
                <i class="bx bx-spanner animate-pulse"></i>
                <div class="absolute top-1 right-1 size-4 bg-red-500 rounded-full border-2 border-white flex items-center justify-center shadow">
                    <div class="size-1 bg-white rounded-full animate-ping"></div>
                </div>
            </div>

            <h1 class="text-2xl font-semibold text-gray-800 mb-2 tracking-tight">System Maintenance</h1>
            <p class="text-sm text-gray-500 mb-8 leading-relaxed font-medium">
                <span class="font-bold text-gray-700"><?= htmlspecialchars($config['school_name'] ?? 'The platform') ?></span> is currently undergoing an essential scheduled maintenance upgrade. We apologize for the downtime and will be back online shortly!
            </p>

            <a href="<?= rtrim($base, '/') ?>/index.php" class="relative overflow-hidden inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-black hover:shadow-lg transition-all w-full sm:w-auto shadow-md">
                <i class="bx bx-refresh text-xl"></i> Refresh Status
            </a>

            <div class="mt-10 w-full pt-6 border-t border-gray-50 flex flex-col gap-2">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">School Administration</span>
                <span class="text-xs text-gray-400 font-medium">Please contact admin or check the parent whatsapp group for updates.</span>
            </div>
            
            <?php if(isset($_SESSION['user_id']) && in_array(($_SESSION['role'] ?? ''), ['admin', 'super'])): ?>
            <div class="mt-4">
                <a href="<?= rtrim($base, '/') ?>/admin/index.php" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">Bypass Maintenance (Admin)</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

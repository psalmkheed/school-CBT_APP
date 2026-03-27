<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    die("Access Denied.");
}

$tpl_id = intval($_GET['tpl_id'] ?? 0);
$target = trim($_GET['target'] ?? '');

if (!$tpl_id || !$target) {
    die("Invalid request parameters.");
}

// Get the template
$stmt = $conn->prepare("SELECT * FROM document_templates WHERE id = ?");
$stmt->execute([$tpl_id]);
$tpl = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tpl) {
    die("Template not found.");
}

$html = $tpl['html_content'];
$bg = !empty($tpl['bg_image']) ? APP_URL . "uploads/templates/" . $tpl['bg_image'] : "";

// Fetch mapping targets
$users = [];
if ($target === 'all_students') {
    $q = $conn->query("SELECT user_id as id_no, first_name, surname, class, role, profile_photo, created_at FROM users WHERE role = 'student' ORDER BY class, first_name");
    $users = $q->fetchAll(PDO::FETCH_ASSOC);
} elseif ($target === 'all_staff') {
    $q = $conn->query("SELECT staff_id as id_no, first_name, surname, role, role as class, passport as profile_photo, created_at FROM staff ORDER BY first_name");
    $users = $q->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Specific class
    $q = $conn->prepare("SELECT user_id as id_no, first_name, surname, class, role, profile_photo, created_at FROM users WHERE role = 'student' AND class = ? ORDER BY first_name");
    $q->execute([$target]);
    $users = $q->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($users)) {
    die("<div style='font-family:sans-serif; text-align:center; padding: 50px; color:#ff4444; font-weight:bold;'>No users found for the selected target.</div>");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Documents - <?= htmlspecialchars($tpl['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { margin: 0; padding: 0; background: #f3f4f6; }
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .print-page { margin: 0 !important; box-shadow: none !important; page-break-after: always; break-after: page; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            /* Avoid page breaks inside a template item */
            .print-item { page-break-inside: avoid; break-inside: avoid; }
        }
        .print-view-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 20px; }
        
        .rendered-doc {
            position: relative;
            background-color: white;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            box-sizing: border-box;
        }

        .bg-layer {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            z-index: 1;
        }
        
        .content-layer {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        <?php if($tpl['type'] === 'id_card'): ?>
        .rendered-doc { width: 5.4cm; height: 8.6cm; border: 1px solid #ccc; font-size: 10px; } /* CR80 Standard */
        <?php elseif($tpl['type'] === 'certificate'): ?>
        .rendered-doc { width: 29.7cm; height: 21cm; border: none; margin-bottom: 2cm; } /* A4 Landscape */
        <?php else: ?>
        .rendered-doc { width: 21cm; height: 29.7cm; border: none; margin-bottom: 2cm; } /* A4 Portrait */
        <?php endif; ?>

    </style>
</head>
<body>
    
    <div class="no-print bg-gray-900 text-white flex justify-between items-center px-6 py-4 shadow-xl sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <div>
                <h1 class="text-lg font-bold leading-tight"><?= htmlspecialchars($tpl['title']) ?> Generator</h1>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold"><?= count($users) ?> Documents Ready</p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="window.close()" class="px-5 py-2 text-sm font-bold bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors border border-gray-700">Cancel</button>
            <button onclick="window.print()" class="px-5 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-colors shadow-[0_0_15px_rgb(52,211,153,0.3)]">Print Now</button>
        </div>
    </div>

    <div class="print-view-container min-h-screen">
        <?php 
        $current_date = date('F j, Y');
        
        foreach($users as $u):
            // Default mappings
            $name = ucfirst($u['first_name']) . ' ' . ucfirst($u['surname']);
            $id_no = $u['id_no'] ?? 'N/A';
            $class = $u['class'] ?? 'N/A';
            $role = strtoupper($u['role'] ?? '');
            
            // Generate QR Code data URIs based on ID
            $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("ID:" . $id_no) . "&margin=0";
            
            // Photo resolving (handles staff 'passport' vs student 'profile_photo')
            $photo_file = $u['profile_photo'];
            $photo_url = APP_URL . "src/img_icon.png";
            if (!empty($photo_file)) {
                $checkDir = ($u['role'] === 'student') ? "uploads/profile_photos/" : "staff/uploads/passports/";
                $photo_url = APP_URL . $checkDir . $photo_file;
            }

            // Engine Replacement
            $docHtml = $html;
            $docHtml = str_replace('{{name}}', htmlspecialchars($name), $docHtml);
            $docHtml = str_replace('{{id_no}}', htmlspecialchars($id_no), $docHtml);
            $docHtml = str_replace('{{class}}', htmlspecialchars($class), $docHtml);
            $docHtml = str_replace('{{role}}', htmlspecialchars($role), $docHtml);
            $docHtml = str_replace('{{qr_code}}', $qr_code_url, $docHtml);
            $docHtml = str_replace('{{photo}}', $photo_url, $docHtml);
            $docHtml = str_replace('{{date}}', $current_date, $docHtml);
        ?>
        
        <!-- Individual Document Container -->
        <div class="rendered-doc print-item print-page relative shadow-2xl overflow-hidden group">
            <?php if($bg): ?>
                <div class="bg-layer" style="background-image: url('<?= $bg ?>');"></div>
            <?php endif; ?>
            <div class="content-layer relative z-10 w-full h-full p-4 group-hover:bg-transparent transition-all">
                <?= $docHtml ?>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
</body>
</html>

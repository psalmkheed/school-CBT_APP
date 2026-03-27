<?php
require '../../connections/db.php';

// Fetch classes
$class_stmt = $conn->prepare("SELECT * FROM class ORDER BY class ASC");
$class_stmt->execute();
$all_classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);

$school_stmt = $conn->prepare("SELECT school_name, school_logo FROM school_config LIMIT 1");
$school_stmt->execute();
$sch = $school_stmt->fetch(PDO::FETCH_OBJ);

$selected_class = $_GET['class'] ?? '';
$students = [];

if ($selected_class) {
    if ($selected_class === 'all') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'student' ORDER BY class, first_name");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'student' AND class = :class ORDER BY first_name");
        $stmt->execute([':class' => $selected_class]);
    }
    $students = $stmt->fetchAll(PDO::FETCH_OBJ);
}
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 no-print">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">QR ID Cards</h3>
            <p class="text-sm text-gray-500">Generate printable Smart ID Cards for students.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <select id="idClassSelector" onchange="if(this.value) window.loadPage('pages/id_cards.php?class=' + encodeURIComponent(this.value))" class="w-full md:w-64 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">-- Select Class --</option>
                <option value="all" <?= $selected_class === 'all' ? 'selected' : '' ?>>All Students</option>
                <?php foreach ($all_classes as $cls): ?>
                    <option value="<?= htmlspecialchars($cls->class) ?>" <?= $selected_class === $cls->class ? 'selected' : '' ?>><?= htmlspecialchars($cls->class) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($students)): ?>
            <button onclick="window.print()" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-md flex items-center justify-center gap-2">
                <i class="bx bx-printer text-lg"></i> Print Cards
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($selected_class)): ?>
    <div class="flex flex-col items-center justify-center p-12 py-20 bg-white rounded-2xl border border-dashed border-gray-200 text-center no-print">
        <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center mb-4">
            <i class="bx bx-user-id-card text-4xl text-blue-500"></i>
        </div>
        <h4 class="text-xl font-bold text-gray-700 mb-2">Select a Class</h4>
        <p class="text-sm text-gray-400 max-w-sm">Choose a class to generate and print ID cards for all students in that specific class.</p>
    </div>
    <?php elseif (empty($students)): ?>
    <div class="p-8 bg-orange-50 text-orange-600 rounded-2xl text-center border border-orange-100 font-bold no-print">
        No students found in the selected class.
    </div>
    <?php else: ?>
    
    <!-- ID CARDS GRID FOR PRINTING -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 print:grid-cols-2 print:gap-4 print:p-0" id="idCardsContainer">
        <?php foreach ($students as $student): 
            $fullname = htmlspecialchars(ucfirst($student->first_name) . ' ' . ucfirst($student->surname));
            $mat_no = htmlspecialchars($student->user_id); // we use user_id
            $qr_data = urlencode("USER_ID:" . $mat_no); // encoded text
        ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden w-[280px] h-[440px] flex flex-col relative mx-auto print:border-2 print:shadow-none print:break-inside-avoid">
            
            <!-- Header Pattern (dynamic background) -->
            <div class="h-28 bg-gradient-to-br from-blue-700 to-indigo-800 relative pt-4 text-wrap">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 10px 10px;"></div>
                <h3 class="text-white font-bold text-center px-2 leading-tight relative z-10 text-[10px] md:text-sm uppercase tracking-wider"><?= strtoupper(htmlspecialchars($sch->school_name ?? 'SCHOOL APP')) ?></h3>
            </div>
            
            <!-- Avatar / Photo -->
            <div class="relative -mt-14 shrink-0 self-center">
                <?php if (!empty($student->profile_photo)): ?>
                    <img src="<?= APP_URL ?>uploads/profile_photos/<?= htmlspecialchars($student->profile_photo) ?>" class="w-28 h-28 object-cover rounded-full border-4 border-white shadow-md bg-white">
                <?php else: ?>
                    <img src="<?= APP_URL ?>src/img_icon.png" class="w-28 h-28 object-cover rounded-full border-4 border-white shadow-md bg-white">
                <?php endif; ?>
            </div>
            
            <!-- Details -->
            <div class="flex flex-col items-center p-4 pt-3 flex-1 text-center">
                <h2 class="text-lg font-semibold text-gray-800 leading-tight mb-1 truncate w-full"><?= $fullname ?></h2>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full uppercase tracking-wider mb-4">STUDENT</span>
                
                <div class="w-full text-left space-y-2 mb-4">
                    <div class="flex border-b border-gray-50 pb-1">
                        <span class="text-[10px] font-bold text-gray-400 w-16 uppercase tracking-wider">ID NO:</span>
                        <span class="text-xs font-bold text-gray-800"><?= $mat_no ?></span>
                    </div>
                    <div class="flex border-b border-gray-50 pb-1">
                        <span class="text-[10px] font-bold text-gray-400 w-16 uppercase tracking-wider">CLASS:</span>
                        <span class="text-xs font-bold text-gray-800"><?= htmlspecialchars($student->class) ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer / QR -->
            <div class="bg-gray-50 border-t border-gray-100 p-3 flex justify-between items-center">
                <div class="flex-1 flex items-center gap-2">
                    <?php if(!empty($sch->school_logo)): ?>
                        <img src="<?= APP_URL . ltrim($sch->school_logo ?? '', '/') ?>" class="h-6 object-contain opacity-50 grayscale mix-blend-multiply">
                    <?php endif; ?>
                    <p class="text-[8px] font-semibold text-gray-400 leading-tight pr-2">Property of <?= htmlspecialchars($sch->school_name ?? 'School') ?>. If found, please return.</p>
                </div>
                <!-- QR API -->
                <div class="shrink-0 bg-white p-1 rounded-md border border-gray-200 shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=<?= $qr_data ?>&margin=0" class="w-[60px] h-[60px]" loading="lazy">
                </div>
            </div>
            
        </div>
        <?php endforeach; ?>
    </div>
    
    <style>
        @media print {
            body { background: white !important; }
            nav, aside, .no-print, #global-toast { display: none !important; }
            #mainContent { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .print\:break-inside-avoid { break-inside: avoid; page-break-inside: avoid; }
            .print\:border-2 { border-width: 2px !important; border-color: #000 !important; }
            /* Force background colors to print */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
    
    <?php endif; ?>
</div>

<script>
    $('#idClassSelector').on('change', function() {
        const val = $(this).val();
        if(val) {
            // Load the same page with the query parameter
            loadPage(BASE_URL + 'admin/pages/id_cards.php?class=' + encodeURIComponent(val));
        }
    });
</script>

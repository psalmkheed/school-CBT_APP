<?php
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'student') {
    exit('Unauthorized access.');
}

// Ensure the user has the QR info fields mapped correctly
$fullname = htmlspecialchars($user->first_name . ' ' . $user->surname);
$mat_no = htmlspecialchars($user->user_id);
$qr_data = urlencode("USER_ID:" . $mat_no); 

$stmt = $conn->prepare('SELECT * FROM school_config');
$stmt->execute();
$sch = $stmt->fetch(PDO::FETCH_OBJ);

// Get attendance stats 
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = :sid AND status = 'present'");
$stmt->execute([':sid' => $user->id]);
$pres_count = $stmt->fetchColumn();

?>

<div class="fadeIn w-full md:p-8 p-4 h-[calc(100vh-80px)] overflow-y-auto">
    
    <div class="max-w-md mx-auto relative md:mt-10">
        
        <div class="absolute inset-0 bg-blue-500 rounded-[2.5rem] blur-xl opacity-30 mt-6 md:mt-0 animate-pulse"></div>

        <div class="bg-white rounded-[2.5rem] shadow-2xl relative border border-gray-100 overflow-hidden mt-6 md:mt-0">
            <!-- Header Pattern -->
            <div class="h-32 bg-gradient-to-br from-blue-700 to-indigo-800 relative flex justify-center items-start pt-6">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 10px 10px;"></div>
                <h3 class="text-white font-semibold text-center px-4 leading-tight relative z-10 text-sm md:text-base uppercase tracking-wider"><?= strtoupper(htmlspecialchars($sch->school_name ?? 'SCHOOL APP')) ?></h3>
            </div>
            
            <!-- Avatar -->
            <div class="relative -mt-16 shrink-0 self-center flex justify-center">
                <?php if (!empty($user->profile_photo)): ?>
                    <img src="<?= APP_URL ?>uploads/profile_photos/<?= htmlspecialchars($user->profile_photo) ?>" class="w-32 h-32 object-cover rounded-full border-[6px] border-white shadow-xl bg-white">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full border-[6px] border-white shadow-xl bg-gray-200 flex items-center justify-center text-4xl font-bold text-gray-400">
                        <i class="bx bx-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Details -->
            <div class="flex flex-col items-center p-6 flex-1 text-center">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-2 truncate w-full"><?= $fullname ?></h2>
                <div class="flex items-center gap-2 mb-6">
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-4 py-1.5 rounded-full uppercase tracking-wider">Student Profile</span>
                    <span class="text-xs font-bold text-green-600 bg-green-50 px-4 py-1.5 rounded-full uppercase tracking-wider"><i class="bx bx-check-shield"></i> Active</span>
                </div>
                
                <!-- QR Code Display -->
                <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100 shadow-inner mb-6 flex flex-col items-center justify-center w-full max-w-[240px] mx-auto group">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= $qr_data ?>&color=1e1b4b&bgcolor=f8fafc" class="w-full max-w-[200px] h-auto object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500 rounded-xl" alt="Digital Pass QR">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mt-3 bg-white px-3 py-1 rounded-full border border-gray-100">Digital Pass</p>
                </div>

                <div class="w-full grid grid-cols-2 gap-3 mb-2">
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 text-center hover:bg-white hover:shadow-md transition-all">
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">ID NO</span>
                        <span class="text-sm font-semibold text-gray-800 break-all"><?= $mat_no ?></span>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 text-center hover:bg-white hover:shadow-md transition-all">
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">CLASS</span>
                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($user->class) ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="bg-gray-900 border-t border-gray-100 p-4 flex justify-between items-center text-white">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-white/10 flex items-center justify-center">
                        <i class="bx bx-calendar-check text-xl text-green-400"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-semibold tracking-widest text-gray-400">Total Check-ins</p>
                        <p class="text-lg font-semibold leading-none"><?= $pres_count ?></p>
                    </div>
                </div>
                <button onclick="window.print()" class="size-10 rounded-xl bg-white text-gray-900 flex items-center justify-center hover:scale-105 transition shadow-lg shrink-0 cursor-pointer" data-tippy-content="Print Pass">
                    <i class="bx bx-printer text-xl"></i>
                </button>
            </div>
            
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .max-w-md, .max-w-md * { visibility: visible; }
    .max-w-md { position: absolute; left: 0; top: 0; margin: 0; width: 100%; box-shadow: none !important; }
    .animate-pulse, button { display: none !important; }
}
</style>

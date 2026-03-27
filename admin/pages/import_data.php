<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch trials for context (optional, but keep UI consistent)
$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch exams for questions import mapping
$exam_stmt = $conn->prepare("SELECT id, subject AS exam_title, class AS exam_class FROM exams ORDER BY created_at DESC");
$exam_stmt->execute();
$exams = $exam_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full p-4 md:p-8">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">Bulk Data Import</h1>
        <p class="text-sm text-gray-500 mt-1">Scale your school operations by importing records in bulk via CSV files.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- User Import Card -->
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col gap-6 relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 size-40 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500 opacity-50"></div>
            
            <div class="relative z-10">
                <div class="size-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl mb-4 shadow-inner">
                    <i class="fa fa-users"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Import Users</h3>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-widest mt-1 mb-4">Students & Staff Records</p>
                
                <div class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100 mb-6">
                    <h4 class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-2">Instructions:</h4>
                    <ul class="text-[11px] text-blue-600 space-y-1.5 font-medium">
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Download the template below.</li>
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Fill in first name, surname, ID, and role.</li>
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Password will be hashed automatically.</li>
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Save as CSV (Comma Separated) format.</li>
                    </ul>
                </div>

                <form id="importUsersForm" class="space-y-4">
                    <input type="hidden" name="import_type" value="users">
                    <div class="relative group/file">
                        <input type="file" name="csv_file" id="userCsvFile" accept=".csv" required class="hidden">
                        <label for="userCsvFile" class="flex flex-col items-center justify-center w-full py-10 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/30 hover:bg-white hover:border-blue-400 transition-all cursor-pointer group-hover/file:shadow-md">
                            <i class="bx bx-upload text-3xl text-gray-300 group-hover:text-blue-500 transition-colors mb-2"></i>
                            <span class="text-sm font-bold text-gray-500" id="userFileName">Click to upload CSV</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between gap-4 mt-2">
                        <a href="<?= $base ?>assets/templates/users_template.csv" download class="text-xs font-bold text-gray-400 hover:text-blue-600 transition flex items-center gap-1">
                            <i class="bx bx-download"></i> Download Template
                        </a>
                        <button type="submit" id="btnImportUsers" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                            <i class="bx bx-cloud-upload text-xl"></i> Process Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Questions Import Card -->
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col gap-6 relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 size-40 bg-purple-50 rounded-full group-hover:scale-110 transition-transform duration-500 opacity-50"></div>
            
            <div class="relative z-10">
                <div class="size-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl mb-4 shadow-inner">
                    <i class="bx bxs-message-question-mark"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Import Questions</h3>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-widest mt-1 mb-4">CBT Exam Questions</p>
                
                <div class="bg-purple-50/50 rounded-2xl p-4 border border-purple-100 mb-6">
                    <h4 class="text-xs font-semibold text-purple-700 uppercase tracking-wider mb-2">Instructions:</h4>
                    <ul class="text-[11px] text-purple-600 space-y-1.5 font-medium">
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Select the target examination carefully.</li>
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> CSV must contain: Text, Option A-D, Answer.</li>
                        <li class="flex items-center gap-2"><i class="bx bx-check-circle"></i> Ensure formatting is kept simple (no complex HTML).</li>
                    </ul>
                </div>

                <form id="importQuestionsForm" class="space-y-4">
                    <input type="hidden" name="import_type" value="questions">
                    <div class="space-y-1">
                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Select Target Exam</label>
                        <select name="exam_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-bold text-gray-700 appearance-none focus:ring-2 focus:ring-purple-100 transition-all">
                            <option value="">-- Select Exam --</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= $exam->id ?>"><?= htmlspecialchars($exam->exam_title) ?> (<?= htmlspecialchars($exam->exam_class) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="relative group/file">
                        <input type="file" name="csv_file" id="quesCsvFile" accept=".csv" required class="hidden">
                        <label for="quesCsvFile" class="flex flex-col items-center justify-center w-full py-10 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/30 hover:bg-white hover:border-purple-400 transition-all cursor-pointer group-hover/file:shadow-md">
                            <i class="bx bx-upload text-3xl text-gray-300 group-hover:text-purple-500 transition-colors mb-2"></i>
                            <span class="text-sm font-bold text-gray-500" id="quesFileName">Click to upload CSV</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between gap-4 mt-2">
                        <a href="<?= $base ?>assets/templates/questions_template.csv" download class="text-xs font-bold text-gray-400 hover:text-purple-600 transition flex items-center gap-1">
                            <i class="bx bx-download"></i> Download Template
                        </a>
                        <button type="submit" id="btnImportQues" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-purple-200 transition-all flex items-center gap-2">
                            <i class="bx bx-cloud-upload text-xl"></i> Process Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // File Name Display Logic
    document.getElementById('userCsvFile').addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            document.getElementById('userFileName').textContent = this.files[0].name;
            document.getElementById('userFileName').classList.add('text-blue-600');
        }
    });

    document.getElementById('quesCsvFile').addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            document.getElementById('quesFileName').textContent = this.files[0].name;
            document.getElementById('quesFileName').classList.add('text-purple-600');
        }
    });

    // Submissions
    $("#importUsersForm").on("submit", function(e) {
        e.preventDefault();
        handleImport($(this), "#btnImportUsers");
    });

    $("#importQuestionsForm").on("submit", function(e) {
        e.preventDefault();
        handleImport($(this), "#btnImportQues");
    });

    function handleImport(form, btnSelector) {
        let fd = new FormData(form[0]);
        let btn = $(btnSelector);
        let ogText = btn.html();
        
        btn.html('<i class="bx bx-loader-alt bx-spin text-xl"></i> Processing...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/import_api.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: res.message,
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    });
                    form[0].reset();
                    // Reset labels
                    $("#userFileName").text("Click to upload CSV").removeClass('text-blue-600');
                    $("#quesFileName").text("Click to upload CSV").removeClass('text-purple-600');
                } else {
                    Swal.fire('Error', res.message || 'Import failed', 'error');
                }
                btn.html(ogText).prop('disabled', false);
            },
            error: function() {
                Swal.fire('Network Error', 'Check your connection', 'error');
                btn.html(ogText).prop('disabled', false);
            }
        });
    }
</script>

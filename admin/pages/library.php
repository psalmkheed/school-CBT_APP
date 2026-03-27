<?php
require __DIR__ . '/../../auth/check.php';

if (!in_array($user->role, ['super', 'admin'])) {
    exit('Unauthorized');
}

$session_id = $_SESSION['active_session_id'] ?? 0;

// Admin fetches ALL materials
$stmt = $conn->prepare("
    SELECT m.*, u.first_name, u.surname 
    FROM materials m
    JOIN users u ON m.uploaded_by = u.id
    ORDER BY m.created_at DESC
");
$stmt->execute();
$materials = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch subjects for the dropdown
$subj_stmt = $conn->query("SELECT * FROM subjects ORDER BY subject ASC");
$subjects = $subj_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch classes for the dropdown
$class_stmt = $conn->query("SELECT * FROM class ORDER BY class ASC");
$classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-xl shadow-amber-200 flex items-center justify-center">
                <i class="bx bx-library text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Digital Library Manager</h1>
                <p class="text-sm text-gray-500 font-medium">Manage all study resources across the school</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative w-full sm:w-80 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="bx bx-search text-gray-400 group-focus-within:text-amber-500 transition-colors text-xl"></i>
                </div>
                <input type="text" id="adminLibrarySearch" placeholder="Search school library..." 
                    class="w-full bg-white border border-gray-100 rounded-[1.25rem] pl-12 pr-4 py-3.5 text-sm font-bold text-gray-700 shadow-xl shadow-gray-100/50 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all">
            </div>
        </div>
    </div>

    <!-- Materials Grid -->
    <div id="adminMaterialsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (count($materials) > 0): ?>
            <?php foreach ($materials as $m): ?>
                <div class="material-card bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 group"
                    data-title="<?= strtolower(htmlspecialchars($m->title)) ?>" data-subject="<?= strtolower(htmlspecialchars($m->subject)) ?>">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-amber-50 group-hover:text-amber-600 transition-colors">
                            <?php 
                                $icon = 'bxs-note';
                                if(strpos($m->file_type, 'pdf') !== false) $icon = 'bxs-note text-red-500';
                                else if(strpos($m->file_type, 'image') !== false) $icon = 'bxs-image text-blue-500';
                                else if(strpos($m->file_type, 'word') !== false) $icon = 'bxs-file-detail text-blue-700';
                            ?>
                            <i class="bx <?= $icon ?> text-2xl"></i>
                        </div>
                        <div class="flex gap-2">
                             <button onclick="deleteMaterial(<?= $m->id ?>)" class="size-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition cursor-pointer">
                                <i class="bx bx-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <h3 class="font-bold text-gray-800 mb-1 truncate" title="<?= htmlspecialchars($m->title) ?>"><?= htmlspecialchars($m->title) ?></h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-4">By: <?= htmlspecialchars($m->surname) ?></p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($m->subject) ?></span>
                        <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($m->class) ?></span>
                    </div>

                    <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400"><?= date('M j, Y', strtotime($m->created_at)) ?></span>
                        <a href="<?= APP_URL . $m->file_path ?>" target="_blank" class="text-xs font-bold text-amber-600 hover:underline flex items-center gap-1">
                            <i class="bx bx-download"></i> View File
                        </a>
                    </div>
                </div>
            <?php endforeach ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="bx bx-library text-4xl"></i>
                </div>
                <h3 class="text-gray-800 font-bold">No materials found in school library</h3>
                <p class="text-gray-400 text-sm">Teachers haven't uploaded any study resources yet.</p>
            </div>
        <?php endif ?>
    </div>
</div>

<script>
    $('#adminLibrarySearch').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.material-card').each(function() {
            const title = $(this).data('title');
            const subject = $(this).data('subject');
            $(this).toggle(title.includes(query) || subject.includes(query));
        });
    });

    function deleteMaterial(id) {
        Swal.fire({
            title: 'Delete Resource?',
            text: "This material will be permanently removed from the school library.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../admin/auth/library_api.php?action=delete', { id }, function(res) {
                    if(res.status === 'success') {
                        window.showToast("Resource deleted!", "success");
                        setTimeout(() => window.loadPage('pages/library.php'), 1000);
                    } else {
                        window.showToast(res.message, "error");
                    }
                }, 'json').fail(() => {
                    window.showToast("Network Error", "error");
                });
            }
        });
    }
</script>

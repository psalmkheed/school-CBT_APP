<?php
require __DIR__ . '/../../auth/check.php';
/** @var stdClass|false $user */
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$teacher_id = $user->id;
$session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch materials uploaded by this teacher
$stmt = $conn->prepare("SELECT * FROM materials WHERE uploaded_by = ? ORDER BY created_at DESC");
$stmt->execute([$teacher_id]);
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
            <div class="size-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-xl shadow-indigo-200 flex items-center justify-center">
                <i class="bx bx-book-content text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Material Library</h1>
                <p class="text-sm text-gray-500 font-medium">Manage and share resources with your classes</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4">
            <div class="relative w-full sm:w-80 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="bx bx-search text-gray-400 group-focus-within:text-indigo-500 transition-colors text-xl"></i>
                </div>
                <input type="text" id="staffLibrarySearch" placeholder="Search my materials..." 
                    class="w-full bg-white border border-gray-100 rounded-[1.25rem] pl-12 pr-4 py-3.5 text-sm font-bold text-gray-700 shadow-xl shadow-gray-100/50 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all">
            </div>

            <button onclick="$('#uploadMaterialModal').removeClass('hidden')" 
                class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-[1.25rem] font-semibold text-xs uppercase tracking-widest hover:shadow-2xl hover:shadow-indigo-200 hover:-translate-y-1 active:translate-y-0 transition-all duration-300 cursor-pointer flex items-center justify-center gap-3">
                <i class="bx bx-plus-circle text-xl"></i> New Material
            </button>
        </div>
    </div>

    <!-- Materials Grid -->
    <div id="staffMaterialsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (count($materials) > 0): ?>
            <?php foreach ($materials as $m): ?>
                <div class="material-card bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 group"
                    data-title="<?= strtolower(htmlspecialchars($m->title)) ?>" data-subject="<?= strtolower(htmlspecialchars($m->subject)) ?>">
                    <div class="flex items-start justify-between mb-6">
                        <div class="size-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
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
                    <p class="text-xs text-gray-400 mb-4 line-clamp-2 h-8"><?= htmlspecialchars($m->description ?: 'No description provided.') ?></p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($m->subject) ?></span>
                        <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($m->class) ?></span>
                    </div>

                    <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400"><?= date('M j, Y', strtotime($m->created_at)) ?></span>
                        <a href="<?= APP_URL . $m->file_path ?>" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
                            <i class="bx bx-download"></i> View File
                        </a>
                    </div>
                </div>
            <?php endforeach ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="bx bx-folder-open text-4xl"></i>
                </div>
                <h3 class="text-gray-800 font-bold">No materials found</h3>
                <p class="text-gray-400 text-sm">You haven't uploaded any study resources yet.</p>
            </div>
        <?php endif ?>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadMaterialModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[1000] flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-indigo-50/50">
            <h3 class="text-lg font-bold text-gray-800">Upload Study Material</h3>
            <button onclick="$('#uploadMaterialModal').addClass('hidden')" class="size-8 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-red-500 transition cursor-pointer">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        
        <form id="uploadMaterialForm" class="p-6 space-y-4">
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Title</label>
                <input type="text" name="title" required placeholder="Material Title (e.g. Physics Week 1 Note)"
                    class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
            </div>

            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Brief Description</label>
                <textarea name="description" placeholder="What is this material about? (optional)" rows="2"
                    class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Subject</label>
                    <select name="subject" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                        <option value="">Select Subject</option>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= htmlspecialchars($s->subject) ?>"><?= htmlspecialchars($s->subject) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Target Class</label>
                    <select name="class" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                        <option value="">Select Class</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c->class) ?>"><?= htmlspecialchars($c->class) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="p-6 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50 hover:bg-indigo-50/30 hover:border-indigo-200 transition-all cursor-pointer relative group text-center" id="dropZone">
                <input type="file" name="material_file" id="materialFile" required class="absolute inset-0 opacity-0 cursor-pointer">
                <div class="pointer-events-none">
                    <i class="bx bx-cloud-upload text-4xl text-gray-300 group-hover:text-indigo-400 transition-colors"></i>
                    <p class="text-xs font-bold text-gray-500 mt-2" id="fileNameDisp">Click or drag file to upload</p>
                    <p class="text-[9px] text-gray-400 mt-1">PDF, Docs, Images (Max 10MB)</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all cursor-pointer flex items-center justify-center gap-2">
                <i class="bx bx-upload"></i> Start Upload
            </button>
        </form>
    </div>
</div>

<script>
    $('#staffLibrarySearch').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.material-card').each(function() {
            const title = $(this).data('title');
            const subject = $(this).data('subject');
            $(this).toggle(title.includes(query) || subject.includes(query));
        });
    });

    $('#materialFile').on('change', function() {
        const file = this.files[0];
        if(file) {
            $('#fileNameDisp').text(file.name).addClass('text-indigo-600');
        }
    });

    $('#uploadMaterialForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="bx bx-loader-dots animate-spin text-lg"></i> Uploading...');

        $.ajax({
            url: '../auth/materialAuth.php?action=upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire('Success', res.message, 'success').then(() => {
                        window.loadPage('pages/library.php');
                    });
                } else {
                    Swal.fire('Error', res.message || 'Upload failed', 'error');
                }
            },
            error: () => Swal.fire('Error', 'Connection failed', 'error'),
            complete: () => btn.prop('disabled', false).html(originalHtml)
        });
    });

    function deleteMaterial(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This material will be permanently removed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../auth/materialAuth.php?action=delete', { id }, function(res) {
                    if(res.status === 'success') {
                        window.loadPage('pages/library.php');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            }
        });
    }
</script>

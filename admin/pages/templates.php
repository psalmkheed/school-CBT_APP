<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

$stmt = $conn->query("SELECT * FROM document_templates ORDER BY created_at DESC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-700 hover:border-emerald-200 hover:bg-emerald-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0 shadow-sm border border-emerald-200">
                    <i class="bx bx-certification text-emerald-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Template Engine</h3>
                    <p class="text-sm text-gray-400 font-medium">Design dynamic certificates & ID cards</p>
                </div>
            </div>
        </div>
        <button onclick="openTemplateModal()" class="bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-gray-200 font-bold text-sm flex gap-2 items-center">
            <i class="bx bx-plus"></i> New Template
        </button>
    </div>

    <!-- Active Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if(empty($templates)): ?>
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                <i class="bx bx-file-blank text-4xl text-gray-300 mb-2"></i>
                <h5 class="text-gray-500 font-bold">No Templates Found</h5>
                <p class="text-xs text-gray-400">Click New Template to design your first document.</p>
            </div>
        <?php else: ?>
            <?php foreach($templates as $t): ?>
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-[0_4px_20px_rgb(0,0,0,0.06)] transition-all overflow-hidden flex flex-col group">
                <div class="h-32 bg-gray-100 relative w-full flex items-center justify-center overflow-hidden">
                    <?php if(!empty($t['bg_image'])): ?>
                        <img src="<?= APP_URL ?>uploads/templates/<?= htmlspecialchars($t['bg_image']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-multiply transition-transform group-hover:scale-110 duration-700">
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                    <span class="absolute top-3 left-3 bg-white text-gray-800 text-[9px] font-semibold uppercase tracking-widest px-2 py-1 rounded shadow-sm">
                        <?= htmlspecialchars($t['type']) ?>
                    </span>
                    <h3 class="absolute bottom-3 left-4 right-4 text-white font-semibold text-lg line-clamp-1 break-words"><?= htmlspecialchars($t['title']) ?></h3>
                </div>
                
                <div class="p-4 bg-white flex justify-between items-center bg-gray-50/50">
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">HTML Format</p>
                    <div class="flex items-center gap-2">
                        <button onclick='editTemplate(<?= json_encode($t) ?>)' class="size-8 rounded-full bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white flex items-center justify-center transition-colors">
                            <i class="bx bx-edit text-sm"></i>
                        </button>
                        <button onclick="deleteResourceTpl(<?= $t['id'] ?>)" class="size-8 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                            <i class="bx bx-trash text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Print Documents Component -->
    <div class="mt-12 bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 pt-6">
        <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
            <i class="bx bx-printer text-blue-500 text-lg"></i> Issue Center
        </h4>
        <form class="flex flex-col md:flex-row gap-4 items-end" onsubmit="handleGenerator(event)">
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select Format</label>
                <select id="gen_template_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all text-sm font-semibold">
                    <option value="">Choose a Template..</option>
                    <?php foreach($templates as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> (<?= $t['type'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Target Grade / Department</label>
                <select id="gen_target_class" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all text-sm font-semibold">
                    <option value="">Choose Target Audience..</option>
                    <option value="all_students">All Students</option>
                    <option value="all_staff">All Staff</option>
                    <optgroup label="Classes">
                        <?php 
                        $cls_stmt = $conn->query("SELECT * FROM class ORDER BY class ASC");
                        while($c = $cls_stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . htmlspecialchars($c['class']) . '">Class: ' . htmlspecialchars($c['class']) . '</option>';
                        }
                        ?>
                    </optgroup>
                </select>
            </div>
            <button type="submit" class="w-full md:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-[0_4px_15px_rgb(37,99,235,0.3)] transition-all">
                Generate & Print
            </button>
        </form>
    </div>
</div>

<!-- Template Editor Modal -->
<div id="templateModal" class="fixed inset-0 z-[600] bg-black/60 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="templateModalContent">
        <div class="p-6 md:p-8 flex justify-between items-center border-b border-gray-50 no-print">
            <div>
                <h3 class="text-xl md:text-2xl font-semibold text-gray-800" id="tplModalTitle">Design Template</h3>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">HTML Rendering Engine</p>
            </div>
            <button onclick="closeTemplateModal()" class="w-10 h-10 rounded-full bg-gray-50 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition flex items-center justify-center shrink-0">
                <i class="bx-x text-2xl"></i>
            </button>
        </div>
        
        <div class="p-6 md:p-8 flex-1 overflow-y-auto w-full custom-scrollbar">
            <form id="templateForm" class="space-y-6">
                <input type="hidden" name="id" id="tpl_id" value="0">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Title</label>
                        <input type="text" name="title" id="tpl_title" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 font-semibold text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Template Type</label>
                        <select name="type" id="tpl_type" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 font-semibold text-sm">
                            <option value="id_card">Smart ID Card</option>
                            <option value="certificate">Award / Certificate</option>
                            <option value="letter">Official Letter</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Background Artwork (Optional, fits to page)</label>
                    <input type="file" name="bg_image" id="tpl_bg" accept=".jpg,.png,.jpeg,.webp" class="w-full px-4 py-2 border border-dashed border-gray-300 rounded-xl text-sm bg-gray-50 cursor-pointer">
                    <p class="text-[10px] text-gray-400 mt-1 font-semibold">* Uploading a new image will replace the old one for this template. Max 2MB.</p>
                </div>

                <div>
                    <div class="flex justify-between items-end mb-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Document HTML Structure</label>
                        <span class="text-[9px] font-bold bg-amber-50 text-amber-600 px-2 py-0.5 rounded border border-amber-100 uppercase tracking-widest cursor-pointer" onclick="alert('Available placeholders:\n{{name}}\n{{id_no}}\n{{class}}\n{{role}}\n{{qr_code}}')">Available Placeholders</span>
                    </div>
                    <textarea name="html_content" id="tpl_html" rows="12" class="w-full px-4 py-4 bg-gray-900 text-green-400 font-mono text-xs border border-gray-800 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all custom-scrollbar" placeholder="<!-- Example: -->&#10;<div style='padding: 20px; text-align: center'>&#10;   <h1>Participant: {{name}}</h1>&#10;   <img src='{{qr_code}}' width='100' />&#10;</div>"></textarea>
                </div>
            </form>
        </div>
        
        <div class="p-4 md:p-6 bg-gray-50/80 border-t border-gray-100 flex justify-end gap-3 no-print">
            <button onclick="closeTemplateModal()" class="px-6 py-2.5 rounded-xl font-bold text-sm text-gray-600 bg-white border border-gray-200 shadow-sm hover:bg-gray-100 transition-all">Cancel</button>
            <button onclick="$('#templateForm').submit()" id="btnSaveTpl" class="px-8 py-2.5 rounded-xl font-bold text-sm text-white bg-emerald-600 shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all flex items-center gap-2">
                <i class="bx bx-save"></i> Save Engine Structure
            </button>
        </div>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    function openTemplateModal(isEdit = false) {
        if(!isEdit) {
            $('#templateForm')[0].reset();
            $('#tpl_id').val('0');
            $('#tplModalTitle').text('Design Template');
            $('#tpl_bg').val('');
        }
        $('#templateModal').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#templateModalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }
    
    function closeTemplateModal() {
        $('#templateModalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#templateModal').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function editTemplate(tpl) {
        $('#tpl_id').val(tpl.id);
        $('#tpl_title').val(tpl.title);
        $('#tpl_type').val(tpl.type);
        $('#tpl_html').val(tpl.html_content);
        $('#tplModalTitle').text('Edit: ' + tpl.title);
        
        openTemplateModal(true);
    }

    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btn = $('#btnSaveTpl');
        const ogText = btn.html();
        btn.html(`<i class="bx bx-loader-alt bx-spin"></i> Engine Compiling...`).prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/templates_api.php?action=save_template',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    closeTemplateModal();
                    $('#templates_menu_btn').trigger('click'); // Wait I don't have templates_menu_btn. Let me fallback
                    loadPage(BASE_URL + "admin/pages/templates.php");
                } else {
                    showAlert('error', res.message || 'Operation failed');
                }
            },
            error: function() {
                showAlert('error', 'Network failure.');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    window.deleteResourceTpl = function(id) {
        Swal.fire({
            title: 'Delete Template?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/templates_api.php?action=delete_template',
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            loadPage(BASE_URL + "admin/pages/templates.php");
                        } else {
                            showAlert('error', res.message || 'Deletion failed.');
                        }
                    }
                });
            }
        });
    };

    function handleGenerator(e) {
        e.preventDefault();
        const tpl_id = $('#gen_template_id').val();
        const target = $('#gen_target_class').val();
        
        if(!tpl_id || !target) return showAlert('error', 'Select both inputs.');
        
        // This opens a new window specifically for printing the generated documents
        const printUrl = BASE_URL + 'admin/pages/render_documents.php?tpl_id=' + tpl_id + '&target=' + encodeURIComponent(target);
        window.open(printUrl, '_blank');
    }
</script>

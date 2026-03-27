<?php
require '../../connections/db.php';
require '../../auth/check.php';
/** @var stdClass|false $user */
if ($user->role !== 'staff') {
    exit('Unauthorized Access.');
}

$tab = $_GET['tab'] ?? 'All';
if($tab === 'Other_Download') $tab = 'Other Download';

// Fetch classes for dropdown
$classes = $conn->query("SELECT id, class FROM class ORDER BY class ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 shadow-sm border border-indigo-200">
                    <i class="bxs-book text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight" id="pgTitle">Study Material</h3>
                    <p class="text-sm text-gray-400 font-medium">Manage and distribute learning resources.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="$('#uploadModal').removeClass('hidden')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 transition-all flex items-center gap-2 text-sm">
                <i class="fas fa-file-upload text-lg"></i> Upload New
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-6" id="categoryFilters">
        <button onclick="changeTab('All')" class="tab-btn px-4 py-2 rounded-full text-xs font-bold transition-all border <?= $tab === 'All' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200' ?>" data-tab="All">All Resources</button>
        <button onclick="changeTab('Assignment')" class="tab-btn px-4 py-2 rounded-full text-xs font-bold transition-all border <?= $tab === 'Assignment' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200' ?>" data-tab="Assignment">Assignments</button>
        <button onclick="changeTab('Syllabus')" class="tab-btn px-4 py-2 rounded-full text-xs font-bold transition-all border <?= $tab === 'Syllabus' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200' ?>" data-tab="Syllabus">Syllabus</button>
        <button onclick="changeTab('Other Download')" class="tab-btn px-4 py-2 rounded-full text-xs font-bold transition-all border <?= $tab === 'Other Download' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200' ?>" data-tab="Other Download">Other Downloads</button>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10" id="materialGrid">
        <!-- Content Loaded via Ajax -->
    </div>
    
    <div id="materialLoader" class="flex flex-col items-center justify-center py-20 text-gray-400">
        <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-indigo-600"></i>
        <p class="text-sm font-semibold">Fetching content...</p>
    </div>
    
    <div id="materialEmpty" class="hidden flex flex-col items-center justify-center py-20 text-gray-400">
        <div class="size-16 bg-white rounded-2xl flex items-center justify-center mb-4 border border-gray-100 shadow-sm">
            <i class="bx bx-folder-open text-3xl text-gray-300"></i>
        </div>
        <h4 class="font-bold text-gray-700">No Content Found</h4>
        <p class="text-xs mt-1">There are no study materials matching this category.</p>
    </div>
</div>

<!-- Modal: Upload Form -->
<div id="uploadModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl scale-100 transform transition-all flex flex-col max-h-[90vh]">
        <div class="px-6 py-5 flex items-center justify-between border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2"><i class="fas fa-file-upload text-indigo-600"></i> Upload Material</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500 transition"><i class="bx bx-x text-xl"></i></button>
        </div>
        <form id="uploadForm" class="p-6 space-y-4 overflow-y-auto">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Title</label>
                <input type="text" name="title" required placeholder="E.g. Math Revision Notes" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Category</label>
                    <select name="category" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="Assignment">Assignment</option>
                        <option value="Syllabus">Syllabus</option>
                        <option value="Other Download" selected>Other Download</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Target Class</label>
                    <select name="target_class" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="All">All Classes</option>
                        <?php foreach($classes as $cl): ?>
                            <option value="<?= $cl['class'] ?>"><?= $cl['class'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Description (Optional)</label>
                <textarea name="description" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 transition-all"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Select File</label>
                <div class="relative border-2 border-dashed border-gray-200 bg-gray-50 rounded-xl p-6 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition-all">
                    <input type="file" name="file" id="fileInput" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.mp4,.mp3" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <i class="bx bx-cloud-upload text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm font-bold text-gray-600" id="fileNameDisp">Click or drag file to upload</p>
                    <p class="text-[10px] font-semibold text-gray-400 mt-1">Supports: JPG, PNG, PDF, DOCX, MP4, MP3</p>
                </div>
            </div>
            
            <div class="pt-4 mt-6 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">Cancel</button>
                <button type="submit" id="upBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition shadow-lg shadow-indigo-200 flex items-center gap-2">
                    <i class="bx bx-check text-lg"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Preview -->
<div id="previewModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[1000] hidden flex flex-col p-4 md:p-8">
    <div class="flex items-center justify-between text-white mb-4">
        <div>
            <h3 class="font-bold text-lg md:text-xl" id="pvTitle">File Preview</h3>
            <p class="text-xs text-gray-400 font-mono tracking-widest uppercase mt-0.5" id="pvType">PDF / 12MB</p>
        </div>
        <div class="flex gap-4">
            <a href="#" id="pvDownload" download class="w-10 h-10 rounded-full bg-white/10 hover:bg-indigo-600 flex items-center justify-center text-white transition cursor-pointer" title="Download directly">
                <i class="bx bx-download text-xl"></i>
            </a>
            <button onclick="closePreview()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-rose-600 flex items-center justify-center text-white transition">
                <i class="bx bx-x text-2xl"></i>
            </button>
        </div>
    </div>
    
    <div class="flex-1 w-full max-w-6xl mx-auto rounded-2xl overflow-hidden bg-gray-900 border border-white/10 flex items-center justify-center relative shadow-2xl" id="pvContainer">
        <!-- Content inject dynamically -->
    </div>
</div>

<script>
    window.currentTab = "<?= $tab ?>";
    
    function changeTab(tab) {
        window.currentTab = tab;
        
        $('.tab-btn').removeClass('bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200').addClass('bg-white text-gray-500 hover:bg-gray-50 border-gray-200');
        $(`.tab-btn[data-tab="${tab}"]`).removeClass('bg-white text-gray-500 hover:bg-gray-50 border-gray-200').addClass('bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200');
        
        if(tab === 'All') $('#pgTitle').text('Study Material');
        else $('#pgTitle').text(tab + 's');

        loadMaterials();
    }

    function loadMaterials() {
        $('#materialGrid').empty();
        $('#materialEmpty').addClass('hidden');
        $('#materialLoader').removeClass('hidden');

        $.get(BASE_URL + 'admin/auth/study_api.php?action=get_list&category=' + window.currentTab, function(res) {
            $('#materialLoader').addClass('hidden');
            if(res.status === 'success') {
                if(res.data.length === 0) {
                    $('#materialEmpty').removeClass('hidden');
                } else {
                    renderGrid(res.data);
                }
            }
        });
    }

    function renderGrid(data) {
        let html = '';
        data.forEach(m => {
            const ext = m.file_type.toLowerCase();
            let icon = 'bx-file-blank text-gray-400';
            let bg = 'bg-gray-50 border-gray-100';
            
            if(ext === 'pdf') { icon = 'bxs-file-pdf text-rose-500'; bg = 'bg-rose-50 border-rose-100'; }
            else if(ext === 'mp4') { icon = 'bx-video text-blue-500'; bg = 'bg-blue-50 border-blue-100'; }
            else if(ext === 'mp3') { icon = 'bx-music text-purple-500'; bg = 'bg-purple-50 border-purple-100'; }
            else if(['jpg','jpeg','png'].includes(ext)) { icon = 'bx-image text-emerald-500'; bg = 'bg-emerald-50 border-emerald-100'; }
            else if(['doc','docx'].includes(ext)) { icon = 'bxs-file-doc text-blue-600'; bg = 'bg-blue-100 border-blue-200'; }

            const mJson = JSON.stringify(m).replace(/'/g, "&#39;");

            html += `
            <div class="bg-white border text-left border-gray-100 rounded-3xl p-5 hover:shadow-xl hover:border-indigo-100 transition-all group flex flex-col relative overflow-hidden">
                ${m.is_owner ? `
                <div class="absolute top-4 right-4 z-10">
                    <button onclick='deleteMaterial(${m.id})' class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all border border-red-100 shadow-sm cursor-pointer" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>` : ''}
                
                <div class="h-16 w-16 ${bg} rounded-2xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110 shadow-sm">
                    <i class="bx ${icon} text-3xl"></i>
                </div>
                
                <h4 class="font-bold text-gray-800 text-base leading-tight mb-2 line-clamp-2">${m.title}</h4>
                <p class="text-xs text-gray-500 line-clamp-2 flex-1">${m.description || 'No description provided.'}</p>
                
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400">${m.category}</span>
                        <span class="text-[10px] font-mono font-medium text-gray-500 mt-0.5">Class: ${m.target_class}</span>
                    </div>
                    
                    <button onclick='openPreview(${mJson})' class="text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 h-9 px-4 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer flex items-center gap-1">
                        View
                    </button>
                </div>
            </div>
            `;
        });
        $('#materialGrid').html(html);
    }

    // Upload interactions
    $('#fileInput').on('change', function() {
        if(this.files && this.files.length > 0) {
            $('#fileNameDisp').text(this.files[0].name).addClass('text-indigo-600');
        } else {
            $('#fileNameDisp').text('Click or drag file to upload').removeClass('text-indigo-600');
        }
    });

    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#upBtn');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);

        const formData = new FormData(this);

        $.ajax({
            url: BASE_URL + 'admin/auth/study_api.php?action=upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btn.html(og).prop('disabled', false);
                if(res.status === 'success') {
                    $('#uploadModal').addClass('hidden');
                    $('#uploadForm')[0].reset();
                    $('#fileNameDisp').text('Click or drag file to upload').removeClass('text-indigo-600');
                    showAlert('success', res.message);
                    loadMaterials();
                } else {
                    showAlert('error', res.message);
                }
            },
            error: function() {
                btn.html(og).prop('disabled', false);
                showAlert('error', 'Network error.');
            }
        });
    });

    window.deleteMaterial = function(id) {
        Swal.fire({
            title: 'Delete Content?',
            text: "This file will be permanently removed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/study_api.php?action=delete', {id: id}, function(res) {
                    if(res.status === 'success') {
                        showAlert('success', res.message);
                        loadMaterials();
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    }

    // Preview Logic
    window.openPreview = function(m) {
        const ext = m.file_type.toLowerCase();
        const fileUrl = BASE_URL + 'uploads/study_materials/' + m.file_path;
        
        $('#pvTitle').text(m.title);
        $('#pvType').text(ext.toUpperCase() + ' - ' + m.category);
        $('#pvDownload').attr('href', fileUrl);
        
        let viewerHTML = '';
        
        if(['jpg','jpeg','png'].includes(ext)) {
            viewerHTML = `<img src="${fileUrl}" class="max-w-full max-h-full object-contain p-4" alt="Preview">`;
        } else if(ext === 'mp4') {
            viewerHTML = `
                <video controls class="max-w-full max-h-[85vh] w-full bg-black outline-none rounded-2xl">
                    <source src="${fileUrl}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>`;
        } else if(ext === 'mp3') {
            viewerHTML = `
                <div class="flex flex-col items-center justify-center p-10 bg-gray-800 rounded-3xl w-full max-w-md shadow-inner text-white">
                    <i class="bx bxs-music text-7xl text-purple-400 mb-6 drop-shadow-lg"></i>
                    <audio controls class="w-full outline-none">
                        <source src="${fileUrl}" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                </div>`;
        } else if(ext === 'pdf') {
            viewerHTML = `<iframe src="${fileUrl}" class="w-full h-full border-0 rounded-2xl bg-white"></iframe>`;
        } else if(['doc','docx'].includes(ext)) {
            // Because local env, google docs viewer might fail. Fallback gracefully.
            const encodeFile = encodeURIComponent(fileUrl);
            viewerHTML = `
                <div class="flex flex-col items-center p-10 text-center">
                    <i class="bx bxs-file-doc text-7xl text-blue-400 mb-4"></i>
                    <h4 class="text-white text-lg font-bold">Word Document</h4>
                    <p class="text-gray-400 text-sm mb-6 max-w-sm">Local documents cannot be perfectly previewed. View it using Google Docs remote API or download directly below.</p>
                    <div class="flex gap-4">
                        <a href="${fileUrl}" download class="px-6 py-3 bg-blue-600 hover:bg-blue-500 rounded-xl text-white font-bold transition shadow-lg">Download File</a>
                        <a href="https://view.officeapps.live.com/op/embed.aspx?src=${encodeFile}" target="_blank" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded-xl text-white font-bold transition shadow-lg">Attempt Preview</a>
                    </div>
                </div>`;
        } else {
            viewerHTML = `<p class="text-white font-semibold">No preview available for this format.</p>`;
        }

        $('#pvContainer').html(viewerHTML);
        $('#previewModal').removeClass('hidden');
    }

    window.closePreview = function() {
        $('#previewModal').addClass('hidden');
        $('#pvContainer').empty(); // Stops audio/video playback
    }

    // Init Page load
    changeTab(window.currentTab);
</script>

<?php
require __DIR__ . '/../../auth/check.php';

if ($user->role !== 'student') {
    exit('Unauthorized');
}

$class = $_SESSION['class'] ?? $user->class ?? '';
$session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch materials for student's class
$stmt = $conn->prepare("
    SELECT m.*, u.first_name, u.surname 
    FROM materials m 
    JOIN users u ON m.uploaded_by = u.id 
    WHERE TRIM(LOWER(m.class)) = TRIM(LOWER(?)) 
    ORDER BY m.created_at DESC
");
$stmt->execute([
    $class
]);
$materials = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch unique subjects available for filtering
$subj_stmt = $conn->prepare("SELECT DISTINCT subject FROM materials WHERE TRIM(LOWER(class)) = TRIM(LOWER(?))");
$subj_stmt->execute([
    $class
]);

$subjects = $subj_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-xl shadow-emerald-200 flex items-center justify-center">
                <i class="bx bx-book-bookmark text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight mb-2">Resource Library</h1>
                <p class="text-sm text-gray-500 font-medium">Download notes for <span class="bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg border border-emerald-100"><?= htmlspecialchars($class) ?></span></p>
            </div>
        </div>
        
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="bx bx-search text-gray-400 group-focus-within:text-emerald-500 transition-colors text-xl"></i>
            </div>
            <input type="text" id="librarySearch" placeholder="Search resources..." 
                class="w-full bg-white border border-gray-100 rounded-[1.25rem] pl-12 pr-4 py-3.5 text-sm font-bold text-gray-700 shadow-xl shadow-gray-100/50 focus:outline-none focus:ring-2 focus:ring-emerald-400 transition-all">
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex flex-wrap gap-3 mb-10 overflow-x-auto pb-2 scrollbar-hide">
        <button class="subj-pill px-6 py-3 rounded-[1.125rem] bg-emerald-600 text-white text-[10px] font-semibold uppercase tracking-widest shadow-xl shadow-emerald-100 transition-all cursor-pointer whitespace-nowrap active:scale-95 translate-y-0 hover:-translate-y-0.5" data-subject="all">
            All Subjects
        </button>
        <?php foreach($subjects as $s): ?>
            <button class="subj-pill px-6 py-3 rounded-[1.125rem] bg-white border border-gray-100 text-gray-400 text-[10px] font-semibold uppercase tracking-widest hover:border-emerald-200 hover:text-emerald-600 hover:shadow-xl hover:shadow-gray-100 transition-all cursor-pointer whitespace-nowrap active:scale-95 translate-y-0 hover:-translate-y-0.5" data-subject="<?= htmlspecialchars($s) ?>">
                <?= htmlspecialchars($s) ?>
            </button>
        <?php endforeach ?>
    </div>

    <!-- Materials Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="materialsGrid">
        <?php if (count($materials) > 0): ?>
            <?php foreach ($materials as $m): ?>
                <div class="material-item bg-white rounded-3xl border border-gray-100 shadow-sm p-5 hover:shadow-xl transition-all duration-300 group" 
                    data-subject="<?= htmlspecialchars($m->subject) ?>" data-title="<?= strtolower(htmlspecialchars($m->title)) ?>">
                    
                    <div class="flex items-start justify-between mb-4">
                        <div class="size-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-emerald-50 group-hover:text-emerald-600 transition-colors">
                            <?php 
                                $icon = 'bx-file';
                                if(strpos($m->file_type, 'pdf') !== false) $icon = 'bx-note text-red-500';
                                else if(strpos($m->file_type, 'image') !== false) $icon = 'bxs-image text-blue-500';
                                else if(strpos($m->file_type, 'word') !== false) $icon = 'bx-file-detail text-blue-700';
                            ?>
                            <i class="bx <?= $icon ?> text-2xl"></i>
                        </div>
                        <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[9px] font-semibold uppercase tracking-widest"><?= htmlspecialchars($m->subject) ?></span>
                    </div>
                    
                    <h3 class="font-bold text-gray-800 mb-1 truncate" title="<?= htmlspecialchars($m->title) ?>"><?= htmlspecialchars($m->title) ?></h3>
                    <p class="text-[10px] text-gray-400 mb-4 line-clamp-2 h-7 italic">Shared by: Mr/Mrs. <?= htmlspecialchars($m->surname) ?></p>

                    <p class="text-xs text-gray-500 mb-6 line-clamp-2 h-8 leading-relaxed"><?= htmlspecialchars($m->description ?: 'No description provided.') ?></p>

                    <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                        <span class="text-[9px] font-bold text-gray-400 flex items-center gap-1 uppercase tracking-widest">
                            <i class="bx bx-calendar"></i> <?= date('M j, Y', strtotime($m->created_at)) ?>
                        </span>
                        <a href="<?= APP_URL . $m->file_path ?>" target="_blank" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-bold hover:bg-emerald-700 transition shadow-md shadow-emerald-50 flex items-center gap-1">
                            <i class="bx bx-download"></i> Download
                        </a>
                    </div>
                </div>
            <?php endforeach ?>
        <?php else: ?>
            <div class="col-span-full py-24 text-center">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
                    <i class="bx bx-book-content text-5xl"></i>
                </div>
                <h3 class="text-gray-800 font-bold text-xl">Library is Empty</h3>
                <p class="text-gray-400 text-sm">Your teachers haven't uploaded any materials for your class yet.</p>
            </div>
        <?php endif ?>
    </div>
</div>

<script>
    $('.subj-pill').on('click', function() {
        const subj = $(this).data('subject');
        
        // Reset all pills
        $('.subj-pill').removeClass('bg-emerald-600 text-white shadow-xl shadow-emerald-100')
                      .addClass('bg-white border-gray-100 text-gray-400');
        
        // Set active pill
        $(this).addClass('bg-emerald-600 text-white shadow-xl shadow-emerald-100')
               .removeClass('bg-white border-gray-100 text-gray-400');

        filterLibrary();
    });

    $('#librarySearch').on('input', filterLibrary);

    function filterLibrary() {
        const query = $('#librarySearch').val().toLowerCase().trim();
        const activeSubj = $('.subj-pill.bg-emerald-600').data('subject');

        $('.material-item').each(function() {
            const $m = $(this);
            const mSubj = $m.data('subject');
            const mTitle = $m.data('title');

            const matchSearch = !query || mTitle.includes(query);
            const matchSubj = activeSubj === 'all' || mSubj === activeSubj;

            $m.toggle(matchSearch && matchSubj);
        });
    }
</script>

<?php
// Determine Role-based action button
$actionBtn = match($_SESSION['role']) {
    'admin'   => ['icon' => 'bx-user-plus', 'id' => 'createAccount', 'color' => 'bg-green-600'],
    'staff'   => ['icon' => 'bx-pencil', 'id' => 'sideStaffExams', 'color' => 'bg-sky-600'],
    'student' => ['icon' => 'bx-book-open', 'id' => 'sideTest', 'color' => 'bg-blue-600'],
    default   => ['icon' => 'bx-plus', 'id' => 'homepage', 'color' => 'bg-gray-600']
};
// Determine Role-based news button
$newsBtn = match ($_SESSION['role']) {
    'admin' => ['icon' => 'bx-news', 'id' => 'createBlog', 'color' => 'bg-green-600'],
    'staff' => ['icon' => 'bx-bar-chart', 'id' => 'sideResults', 'color' => 'bg-sky-600'],
    'student' => ['icon' => 'bx-history', 'id' => 'sideExamHistory', 'color' => 'bg-blue-600'],
    default => ['icon' => 'bx-plus', 'id' => 'homepage', 'color' => 'bg-gray-600']
};
// Determine Role-based news button
$chatBtn = match ($_SESSION['role']) {
    'admin' => (object) ['icon' => 'bx-envelope', 'id' => 'broadcast', 'color' => 'bg-green-600'],
    'staff' => (object) ['icon' => 'bx-group', 'id' => 'sideStudents', 'color' => 'bg-sky-600'],
    'student' => (object) ['icon' => 'bx-message-circle-detail', 'id' => 'sideChat', 'color' => 'bg-blue-600'],
    default => (object) ['icon' => 'bx-envelope', 'id' => 'homepage', 'color' => 'bg-gray-600']
};

$themeClass = match($_SESSION['role']) {
    'admin'   => 'text-green-600',
    'staff'   => 'text-sky-600',
    'student' => 'text-blue-600',
    default   => 'text-gray-600'
};
?>

<!-- Mobile Bottom Navigation -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-[200] px-4 pb-2 backdrop-blur-xl shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)]">
    <div class="bg-white/80 backdrop-blur-xl border border-gray-100/50 rounded-2xl shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] flex items-center justify-between px-6 py-3 relative">
        
        <!-- Home -->
        <button onclick="window.goHome()" class="bn-btn flex flex-col items-center gap-1 group active:scale-95 transition-all active-link" id="bn-home">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:bg-gray-50 transition-colors">
                <i class="bx bx-home-alt-2 text-2xl text-gray-400 bn-icon" data-solid="bxs-home-alt-2" data-regular="bx-home-alt-2"></i>
            </div>
        </button>

        <!-- Message/Chat -->
        <button onclick="$('#<?= $chatBtn->id ?>').click()"
            class="bn-btn flex flex-col items-center gap-1 group active:scale-95 transition-all" id="bn-chat">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:bg-gray-50 transition-colors">
                <i class="bx <?= $chatBtn->icon ?> text-2xl text-gray-400 bn-icon" data-solid="bxs-<?= $chatBtn->icon ?>"
                    data-regular="bx-<?= $chatBtn->icon ?>"></i>
            </div>
        </button>

        <!-- Floating Action Button -->
        <div class="relative -top-7 z-[210]">
            <button onclick="$('#<?= $actionBtn['id'] ?>').click();" 
                    class="w-14 h-14 rounded-full <?= $actionBtn['color'] ?> text-white flex items-center justify-center shadow-2xl active:scale-90 transition-all border-4 border-white">
                <i class="bx <?= $actionBtn['icon'] ?> text-2xl"></i>
            </button>
        </div>

        <!-- News/Blog -->
        <button onclick="$('#<?= $newsBtn['id'] ?>').first().click()"
            class="bn-btn flex flex-col items-center gap-1 group active:scale-95 transition-all" id="bn-news">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:bg-gray-50 transition-colors">
                <i class="bx <?= $newsBtn['icon'] ?> text-2xl text-gray-400 bn-icon" data-solid="bxs-<?= $newsBtn['icon'] ?>"
                    data-regular="bx-<?= $newsBtn['icon'] ?>"></i>
            </div>
        </button>

        <!-- Profile -->
        <button onclick="$('#profile, #sideProfile').first().click()" class="bn-btn flex flex-col items-center gap-1 group active:scale-95 transition-all" id="bn-profile">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:bg-gray-50 transition-colors overflow-hidden">
                 <i class="bx bx-user-circle text-2xl text-gray-400 bn-icon" data-solid="bxs-user-circle" data-regular="bx-user-circle"></i>
            </div>
        </button>

    </div>
</div>

<script>
    $(document).ready(function() {
        function setActive(btn) {
            // Reset all
            $('.bn-btn').removeClass('active-link');
            $('.bn-btn .bn-icon').each(function() {
                const reg = $(this).data('regular');
                $(this).removeClass('<?= $themeClass ?>').addClass('text-gray-400');
                $(this).removeClass(function(index, className) {
                    return (className.match(/(^|\s)bxs-\S+/g) || []).join(' ');
                }).addClass(reg);
            });
            
            // Set active
            const activeBtn = $(btn);
            activeBtn.addClass('active-link');
            const icon = activeBtn.find('.bn-icon');
            const solid = icon.data('solid');
            const reg = icon.data('regular');
            
            icon.removeClass('text-gray-400 ' + reg).addClass('<?= $themeClass ?> ' + solid);
        }

        $('.bn-btn').on('click', function() {
            setActive(this);
        });

        // Initial active state
        setActive('#bn-home');
    });
</script>

<style>
    /* Adjust main content padding for bottom nav on mobile */
    @media (max-width: 767px) {
        body {
            padding-bottom: 80px;
        }
    }
</style>

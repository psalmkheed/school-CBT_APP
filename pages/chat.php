<?php
require_once __DIR__ . '/../connections/db.php';
require_once __DIR__ . '/../auth/check.php';

// Load last 60 messages for initial render
$stmt = $conn->prepare("
    SELECT m.id, m.message, m.sent_at, m.attachment, m.attachment_type, m.is_deleted, m.is_edited,
           u.first_name, u.surname, u.user_id, m.user_id AS sender_id
    FROM class_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.class = :class
    ORDER BY m.sent_at DESC
    LIMIT 60
");
$stmt->execute([':class' => $user->class]);
$initial = array_reverse($stmt->fetchAll(PDO::FETCH_OBJ));
$lastId = count($initial) > 0 ? end($initial)->id : 0;
$lastSeenTime = count($initial) > 0 ? end($initial)->sent_at : null;

// Get class members count
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE class = :class AND role = 'student'");
$count_stmt->execute([':class' => $user->class]);
$classCount = (int) $count_stmt->fetchColumn();

function getChatDateHeader($dateStr)
{
    if (!$dateStr)
        return '';
    $d = new DateTime($dateStr);
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $yesterday = $now->modify('-1 day')->format('Y-m-d');
    $msgDate = $d->format('Y-m-d');

    if ($msgDate === $today)
        return 'Today';
    if ($msgDate === $yesterday)
        return 'Yesterday';

    // Within last 7 days, show Day name
    if ($now->modify('+1 day')->diff($d)->days < 7) {
        return $d->format('l');
    }

    return $d->format('F j, Y');
}
?>

<div class="fadeIn flex h-[calc(100vh-64px)] bg-white overflow-hidden w-full">

    <!-- ── Left Sidebar (WhatsApp Desktop Style) ──────────────── -->
    <div class="hidden md:flex w-80 lg:w-96 flex-col border-r border-gray-100 flex-shrink-0 bg-[#f0f2f5]/30">

        <!-- Sidebar Header -->
        <div class="h-16 flex-shrink-0 bg-[#f0f2f5] px-4 flex items-center justify-between border-b border-gray-200/60">
            <div class="flex items-center gap-3">
                <div
                    class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-semibold shadow-sm">
                    <?= strtoupper(substr($user->first_name, 0, 1) . substr($user->surname, 0, 1)) ?>
                </div>
                <h3 class="text-sm font-bold text-gray-800">Chats</h3>
            </div>
            <div class="flex items-center gap-2 text-gray-500">
                <button onclick="openSupportModal()"
                    class="p-2 hover:bg-gray-200/50 rounded-full transition cursor-pointer" title="Support Chat">
                    <i class="bx bx-plus-circle text-xl"></i>
                </button>
                <button class="p-2 hover:bg-gray-200/50 rounded-full transition cursor-pointer" title="Menu">
                    <i class="bx bx-dots-vertical-rounded text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Search / Filter -->
        <div class="px-3 py-2 bg-white sticky top-16 z-10 border-b border-gray-50">
            <div class="relative group">
                <i
                    class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg transition-colors group-focus-within:text-blue-500"></i>
                <input type="text" id="sidebarChatSearch" placeholder="Search chats..."
                    class="w-full bg-[#f0f2f5] border-none rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-0 placeholder-gray-500">
            </div>
        </div>

        <!-- Chat List -->
        <div class="flex-1 overflow-y-auto scrollbar-hide">
            <!-- Active Class Chat -->
            <div
                class="flex items-center gap-3 px-4 py-3 bg-[#f0f2f5] cursor-pointer hover:bg-gray-100 transition relative chat-list-item">
                <div class="absolute inset-y-0 left-0 w-1 bg-blue-600"></div>
                <div
                    class="size-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-md flex-shrink-0">
                    <i class="bx bx-group text-xl"></i>
                </div>
                <div class="flex-1 min-w-0 border-b border-gray-100 py-1">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <h4 class="text-[15px] font-bold text-gray-900 truncate"><?= htmlspecialchars($user->class) ?>
                            Class</h4>
                        <span class="text-[10px] font-bold text-gray-400">Just now</span>
                    </div>
                    <p class="text-[13px] text-gray-500 truncate font-medium">Click to chat with classmates...</p>
                </div>
            </div>

            <!-- Support Chat (As a menu item) -->
            <div onclick="openSupportModal()"
                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition chat-list-item">
                <div
                    class="size-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                    <i class="bx bx-headphone-mic text-xl"></i>
                </div>
                <div class="flex-1 min-w-0 border-b border-gray-100 py-1">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <h4 class="text-[15px] font-bold text-gray-800 truncate">Admin Support</h4>
                        <i class="bx bx-chevron-right text-gray-300"></i>
                    </div>
                    <p class="text-[13px] text-gray-400 truncate">Need help? Talk to an Admin</p>
                </div>
            </div>

            <!-- Archive Placeholder -->
            <div class="px-4 py-6 text-center">
                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="bx bx-archive text-gray-300 text-xl"></i>
                </div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-relaxed">No other chats
                    yet</p>
                <p class="text-[9px] text-gray-300 mt-1">Your recent conversations will appear here.</p>
            </div>
        </div>
    </div>

    <!-- ── Main Chat Pane (Right Side) ────────────────────────── -->
    <div class="flex-1 flex flex-col bg-[#efeae2] relative min-w-0">

        <!-- Wallpaper Pattern (WhatsApp style) -->
        <div class="absolute inset-0 opacity-[0.06] pointer-events-none"
            style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: 400px;">
        </div>

        <!-- ── Chat Header ──────────────────────────────────────── -->
        <div
            class="h-16 flex-shrink-0 bg-[#f0f2f5] px-4 flex items-center justify-between border-b border-gray-200/60 shadow-sm relative z-[60]">

            <!-- Default Header Content -->
            <div id="headerDefaultContent" class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <!-- Mobile back button -->
                    <button onclick="goHome()" class="md:hidden p-1.5 hover:bg-gray-200 rounded-full mr-1">
                        <i class="bx bx-arrow-back text-xl text-gray-600"></i>
                    </button>
                    <div
                        class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-sm flex-shrink-0">
                        <i class="bx bx-group text-xl"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm md:text-base font-bold text-gray-800 truncate">
                            <?= htmlspecialchars($user->class) ?> Class
                        </h2>
                        <div class="flex items-center gap-1.5 overflow-hidden">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                            <p class="text-[10px] font-semibold text-gray-500 truncate" id="onlineCount">
                                <?= $classCount ?> members
                                <?php if ($lastSeenTime): ?>
                                    <span class="hidden md:inline mx-1 font-semibold opacity-20">•</span>
                                    <span class="hidden md:inline">Last message:
                                        <?= date('h:i A', strtotime($lastSeenTime)) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        </div>
                        </div>
                        <div class="flex items-center gap-4 text-gray-500 relative">
                            <button id="msgSearchToggleBtn" class="cursor-pointer hover:bg-gray-200 p-2 rounded-full transition"
                                title="Search Message">
                                <i class="bx bx-search text-xl"></i>
                            </button>
                            <button id="chatMenuBtn"
                                class="cursor-pointer hover:text-blue-600 transition flex items-center justify-center p-2 rounded-full hover:bg-gray-200"
                                title="More Options">
                                <i class="bx bx-dots-vertical-rounded text-2xl"></i>
                            </button>

                    <!-- Hidden Menu Dropdown -->
                    <div id="chatMenuDropdown"
                        class="hidden absolute top-12 right-0 w-48 bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-[9999] overflow-hidden">
                        <button onclick="scrollBottom(true)"
                            class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl flex items-center gap-2">
                            <i class="bx bx-downvote"></i> Scroll to Bottom
                        </button>
                        <button onclick="location.reload()"
                            class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl flex items-center gap-2">
                            <i class="bx bx-refresh"></i> Refresh Chat
                        </button>
                        <div class="border-t border-gray-50 my-1"></div>
                        <button
                            onclick="Swal.fire('Class Info', 'You are in <?= htmlspecialchars($user->class) ?>. Total students: <?= $classCount ?>', 'info')"
                            class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl flex items-center gap-2">
                            <i class="bx bx-info-circle"></i> View Info
                        </button>
                    </div>
                    </div>
                    </div>

            <!-- Search Header Content (Hidden by default) -->
            <div id="headerSearchContent"
                class="hidden items-center w-full gap-4 animate-in slide-in-from-top-1 duration-300">
                <button id="closeSearchBtn" class="p-2 hover:bg-gray-200 rounded-full transition text-gray-500">
                    <i class="bx bx-arrow-back text-xl"></i>
                </button>
                <div class="flex-1 relative">
                    <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="msgSearchInput" placeholder="Search messages in this chat"
                        class="w-full bg-white border-none rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-0 placeholder-gray-400">
                </div>
                <div class="px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-widest" id="searchCount">0
                    matches
                </div>
            </div>
        </div>

        <!-- ── Messages Area ─────────────────────────────────────── -->
        <div id="chatMessages"
            class="flex-1 overflow-y-auto px-4 md:px-12 py-6 space-y-3 scroll-smooth relative z-10 custom-scrollbar">

            <?php
            $currentDate = '';
            if (count($initial) === 0): ?>
            <div id="emptyState" class="flex flex-col items-center justify-center h-full text-center py-20">
                    <div
                        class="size-20 bg-blue-50 rounded-[2rem] border border-blue-100 flex items-center justify-center mb-4">
                        <i class="bx bx-message-bubble-detail text-4xl text-blue-400"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-700 mb-1">Start the Conversation!</h4>
                    <p class="text-sm text-gray-400 font-medium max-w-xs leading-relaxed">Be the first to send a message to
                        your
                        <?= htmlspecialchars($user->class) ?> classmates.
                    </p>
                    </div>
                    <?php else: ?>
                    <?php
                foreach ($initial as $msg):
                    $isMine = $msg->sender_id == $user->id;
                    $name = htmlspecialchars($msg->first_name . ' ' . $msg->surname);
                    $initials = strtoupper(substr($msg->first_name, 0, 1) . substr($msg->surname, 0, 1));
                    $time = date('g:i A', strtotime($msg->sent_at));
                    $msgDate = getChatDateHeader($msg->sent_at);

                    if ($msgDate !== $currentDate):
                        $currentDate = $msgDate;
                        ?>
                            <div
                                class="flex justify-center my-6 sticky top-2 z-[30] date-divider transition-all duration-500 opacity-0 pointer-events-none">
                                <span
                                    class="px-4 py-1.5 rounded-xl bg-white/90 backdrop-blur-md border border-gray-100/50 text-[11px] font-medium text-gray-500 shadow-sm">
                                    <?= $msgDate ?>
                                </span>
                            </div>
                    <?php endif; ?>

                    <div class="flex items-end gap-3 <?= $isMine ? 'flex-row-reverse' : '' ?> chat-msg"
                        data-id="<?= $msg->id ?>" data-date="<?= $msgDate ?>">
                        <!-- Avatar -->
                        <?php if (!$isMine): ?>
                            <div
                                class="size-8 shrink-0 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center text-white text-[10px] font-semibold shadow-sm">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>
                                <div class="max-w-[85%] md:max-w-[72%] <?= $isMine ? 'items-end' : 'items-start' ?> flex flex-col gap-1 relative group">
                            <?php if (!$isMine): ?>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide px-1"><?= $name ?></p>
                            <?php endif; ?>
                            <?php if ($msg->is_deleted): ?>
                                <div
                                    class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm bg-gray-100 text-gray-500 border border-gray-200 italic <?= $isMine ? 'rounded-br-none' : 'rounded-bl-none' ?>">
                                    <i class="bx bx-block mr-1"></i> This message was deleted
                                </div>
                            <?php else: ?>
                            <div class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm flex flex-col gap-2 relative
                                                                                                                                                        <?= $isMine
                                                                                                                                                            ? 'bg-blue-600 text-white rounded-br-none shadow-blue-100'
                                                                                                                                                            : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none' ?>">
                                    <?php if ($isMine): ?>
                                        <button onclick="toggleMsgMenu(<?= $msg->id ?>, event)"
                                            class="absolute top-2 -left-8 size-6 bg-white border border-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-500 transition shadow-sm opacity-0 md:group-hover:opacity-100">
                                            <i class="bx bx-chevron-down"></i>
                                        </button>
                                        <div id="msgMenu_<?= $msg->id ?>"
                                            class="hidden absolute top-8 -left-28 w-28 bg-white border border-gray-100 rounded-xl shadow-lg z-50 py-1 flex-col items-start overflow-hidden origin-top-right">
                                            <button
                                                onclick="editMsgUI(<?= $msg->id ?>, <?= htmlspecialchars(json_encode($msg->message), ENT_QUOTES | ENT_SUBSTITUTE) ?>)"
                                                class="w-full px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 text-left"><i
                                                    class="bx bx-edit text-base"></i> Edit</button>
                                            <button onclick="deleteMsg(<?= $msg->id ?>)"
                                                class="w-full px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-red-500 flex items-center gap-2 text-left"><i
                                                    class="bx bx-trash text-base"></i> Delete</button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Attachment Display -->
                                    <?php if ($msg->attachment): ?>
                                        <?php if ($msg->attachment_type === 'image'): ?>
                                                                <div
                                                                    class="group/att relative overflow-hidden rounded-xl border <?= $isMine ? 'border-blue-500' : 'border-gray-100' ?>">
                                                <img src="<?= APP_URL ?>uploads/chat/<?= $msg->attachment ?>" alt="Attachment"
                                                    class="max-w-full max-h-[300px] object-cover cursor-pointer hover:scale-105 transition-transform duration-500"
                                                    onclick="window.open(this.src)">
                                                </div>
                                                <?php else: ?>
                                                <div
                                                    class="flex items-center gap-3 p-3 rounded-xl border <?= $isMine ? 'bg-blue-700/50 border-blue-400' : 'bg-gray-50 border-gray-100' ?>">
                                                    <div class="size-10 rounded-lg bg-white/20 flex items-center justify-center">
                                                        <i class="bx bx-file text-2xl"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[11px] font-bold truncate opacity-80">Document Shared</p>
                                                    <a href="<?= APP_URL ?>uploads/chat/<?= $msg->attachment ?>" download
                                                        class="text-[10px] font-semibold underline uppercase tracking-widest hover:opacity-100 opacity-70">
                                                        Download File
                                                    </a>
                                                    </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($msg->message)): ?>
                                                    <p><?= nl2br(htmlspecialchars($msg->message)) ?></p>
                                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-[10px] text-gray-300 font-bold px-1 flex items-center gap-1">
                                <?= $msg->is_edited && !$msg->is_deleted ? '<span class="italic">(edited)</span>' : '' ?>
                                <?= $time ?>
                                <?php if ($isMine): ?><i class="bx bx-check-double text-blue-400"></i><?php endif; ?>
                            </p>
                            </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>

            <!-- Anchor for scroll-to-bottom -->
            <div id="chatBottom"></div>
        </div>

        <!-- ── Message Composer ──────────────────────────────────── -->
        <div class="shrink-0 bg-[#f0f2f5] px-3 md:px-6 py-3 relative z-20">
            <div class="flex items-center gap-2 md:gap-4 max-w-full lg:max-w-7xl mx-auto">

                <!-- Attachment/Emoji -->
                <div class="flex items-center gap-1 text-gray-500">
                    <button id="emojiToggleBtn" type="button"
                        class="p-2 hover:bg-gray-200/60 rounded-full transition cursor-pointer text-2xl leading-none">
                        😊
                    </button>
                    <div id="emojiPicker"
                        class="hidden absolute bottom-16 left-4 w-72 bg-white border border-gray-100 rounded-3xl shadow-2xl p-4 z-50">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Pick an Emoji
                        </p>
                        <div id="emojiGrid" class="grid grid-cols-8 gap-1 max-h-48 overflow-y-auto"></div>
                    </div>

                    <!-- Hidden Attachment Input -->
                    <input type="file" id="attachmentInput" class="hidden"
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                    <button onclick="document.getElementById('attachmentInput').click()"
                        class="p-2 hover:bg-gray-200/60 rounded-full transition cursor-pointer">
                        <i class="bx bx-paperclip text-2xl"></i>
                    </button>
                </div>

                <!-- Input Box -->
                <div class="flex-1 relative">
                    <div id="attachmentPreview"
                        class="hidden absolute bottom-full left-0 right-0 bg-white p-3 border border-gray-100 rounded-t-2xl shadow-xl animate-in slide-in-from-bottom-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600"
                                    id="previewIcon">
                                    <i class="bx bx-file text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-800 truncate" id="previewName">filename.pdf
                                    </p>
                                    <p class="text-[10px] font-medium text-gray-400" id="previewSize">2.4 MB</p>
                                </div>
                            </div>
                            <button onclick="clearAttachment()"
                                class="p-1 hover:bg-red-50 hover:text-red-500 rounded-full transition">
                                <i class="bx bx-x text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <textarea id="chatInput" placeholder="Type a message" rows="1" maxlength="1000"
                        class="w-full bg-white border-none rounded-lg px-4 py-2.5 text-sm md:text-[15px] focus:ring-0 placeholder-gray-500 resize-none max-h-32 transition-all min-h-[44px]"></textarea>
                </div>

                <!-- Send Button -->
                <button id="sendBtn" type="button"
                    class="size-11 flex-shrink-0 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-all shadow-md cursor-pointer">
                    <i class="bx bx-send text-xl"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling to match WhatsApp's custom scrollbars and layout tweaks */
    #chatMessages {
        background-color: #efeae2;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .chat-msg p {
        word-break: break-word;
    }
</style>

<script>
    (function () {

        const MY_USER_ID = <?= $user->id ?>;
        const MY_NAME = <?= json_encode($user->first_name . ' ' . $user->surname) ?>;
        const MY_INITIALS = <?= json_encode(strtoupper(substr($user->first_name, 0, 1) . substr($user->surname, 0, 1))) ?>;
        const CLASS_NAME = <?= json_encode($user->class) ?>;

        let lastId = <?= $lastId ?>;
        let changedSince = '<?= $lastSeenTime ?>';
        let pollTimer;
        let isSending = false;
        let lastDateShown = '<?= $currentDate ?>';

        const $messages = document.getElementById('chatMessages');
        const $input = document.getElementById('chatInput');
        const $sendBtn = document.getElementById('sendBtn');

        // Auto-resize textarea
        $input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 128) + 'px';
        });

        // Send on Enter (Shift+Enter = newline)
        $input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        $sendBtn.addEventListener('click', sendMessage);

        // Scroll to bottom
        function scrollBottom(smooth = true) {
            const bottom = document.getElementById('chatBottom');
            if (bottom) bottom.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant' });
        }

        // Menu Toggle
        const $menuBtn = document.getElementById('chatMenuBtn');
        const $menuDropdown = document.getElementById('chatMenuDropdown');
        if ($menuBtn && $menuDropdown) {
            $menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                $menuDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => $menuDropdown.classList.add('hidden'));
        }

        const $fileInput = document.getElementById('attachmentInput');
        const $preview = document.getElementById('attachmentPreview');
        const $previewName = document.getElementById('previewName');
        const $previewSize = document.getElementById('previewSize');
        const $previewIcon = document.getElementById('previewIcon');

        // File selection handler
        if ($fileInput) {
            $fileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    $previewName.textContent = file.name;
                    $previewSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

                    if (file.type.startsWith('image/')) {
                        $previewIcon.innerHTML = '<i class="bx bx-image text-xl"></i>';
                    } else {
                        $previewIcon.innerHTML = '<i class="bx bx-file text-xl"></i>';
                    }

                    $preview.classList.remove('hidden');
                    $input.focus();
                }
            });
        }

        window.clearAttachment = function () {
            if ($fileInput) $fileInput.value = '';
            if ($preview) $preview.classList.add('hidden');
        };

        // Build a chat bubble HTML string
        function buildBubble(msg, isMine) {
            const name = escHtml(msg.first_name + ' ' + msg.surname);
            const initials = (msg.first_name.charAt(0) + msg.surname.charAt(0)).toUpperCase();
            const timeStr = formatTime(msg.sent_at);
            const text = msg.message ? escHtml(msg.message).replace(/\n/g, '<br>') : '';

            let attachmentHtml = '';
            if (msg.attachment) {
                if (msg.attachment_type === 'image') {
                    attachmentHtml = `
                    <div class="group relative overflow-hidden rounded-xl border ${isMine ? 'border-blue-500' : 'border-gray-100'}">
                        <img src="${BASE_URL}uploads/chat/${msg.attachment}" alt="Attachment" 
                            class="max-w-full max-h-[300px] object-cover cursor-pointer hover:scale-105 transition-transform duration-500"
                            onclick="window.open(this.src)">
                    </div>`;
                } else {
                    attachmentHtml = `
                    <div class="flex items-center gap-3 p-3 rounded-xl border ${isMine ? 'bg-blue-700/50 border-blue-400' : 'bg-gray-50 border-gray-100'}">
                        <div class="size-10 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="bx bx-file text-2xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold truncate opacity-80">Document Shared</p>
                            <a href="${BASE_URL}uploads/chat/${msg.attachment}" download 
                                class="text-[10px] font-semibold underline uppercase tracking-widest hover:opacity-100 opacity-70">
                                Download File
                            </a>
                        </div>
                    </div>`;
                }
            }

            const safeMsgText = JSON.stringify(msg.message || "");
            let msgBody = '';
            if (msg.is_deleted) {
                msgBody = `<div class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm bg-gray-100 text-gray-500 border border-gray-200 italic ${isMine ? 'rounded-br-none' : 'rounded-bl-none'}"><i class="bx bx-block mr-1"></i> This message was deleted</div>`;
            } else {
                let actionsHtml = '';
                if (isMine) {
                    actionsHtml = `
                    <button onclick="toggleMsgMenu(${msg.id}, event)" class="absolute top-2 -left-8 size-6 bg-white border border-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-500 transition shadow-sm opacity-0 md:group-hover:opacity-100">
                        <i class="bx bx-chevron-down"></i>
                    </button>
                    <div id="msgMenu_${msg.id}" class="hidden absolute top-8 -left-28 w-28 bg-white border border-gray-100 rounded-xl shadow-lg z-50 py-1 flex-col items-start overflow-hidden origin-top-right">
                        <button onclick='editMsgUI(${msg.id}, ${safeMsgText})' class="w-full px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 text-left"><i class="bx bx-edit text-base"></i> Edit</button>
                        <button onclick="deleteMsg(${msg.id})" class="w-full px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-red-500 flex items-center gap-2 text-left"><i class="bx bx-trash text-base"></i> Delete</button>
                    </div>`;
                }

                msgBody = `<div class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm flex flex-col gap-2 relative ${isMine ? 'bg-blue-600 text-white rounded-br-none shadow-blue-100' : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none'}">
                ${actionsHtml}
                ${attachmentHtml}
                ${text ? `<p>${text}</p>` : ''}
            </div>`;
            }

            return `
            <div class="flex items-end gap-3 ${isMine ? 'flex-row-reverse' : ''} chat-msg animate-in slide-in-from-bottom-2" data-id="${msg.id}">
                ${!isMine ? `<div class="size-8 shrink-0 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center text-white text-[10px] font-semibold shadow-sm">${initials}</div>` : ''}
                <div class="max-w-[85%] md:max-w-[72%] ${isMine ? 'items-end' : 'items-start'} flex flex-col gap-1 relative group">
                    ${!isMine ? `<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide px-1">${name}</p>` : ''}
                    ${msgBody}
                    <p class="text-[10px] text-gray-300 font-bold px-1 flex items-center gap-1">${msg.is_edited && !msg.is_deleted ? '<span class="italic">(edited)</span>' : ''}${timeStr} ${isMine ? '<i class="bx bx-check-double text-blue-400"></i>' : ''}</p>
                </div>
            </div>`;
        }

        window.toggleMsgMenu = function (id, e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('msgMenu_' + id);
            if (menu) {
                document.querySelectorAll('[id^=msgMenu_]').forEach(m => {
                    if (m.id !== 'msgMenu_' + id) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
                menu.classList.toggle('flex');
            }
        };

        document.addEventListener('click', (e) => {
            document.querySelectorAll('[id^=msgMenu_]').forEach(m => { m.classList.add('hidden'); m.classList.remove('flex'); });
        });

        window.deleteMsg = function (id) {
            Swal.fire({
                title: 'Delete Message?',
                text: 'This message will be deleted for everyone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(BASE_URL + 'student/auth/delete_message.php', { msg_id: id }, function (res) {
                        if (res.success) {
                            const el = document.querySelector(`.chat-msg[data-id="${id}"]`);
                            if (el) {
                                const isMine = el.classList.contains('flex-row-reverse');
                                // Instead of removing, replace with deleted placeholder
                                el.innerHTML = `
                                <div class="max-w-[85%] md:max-w-[72%] ${isMine ? 'items-end' : 'items-start'} flex flex-col gap-1 relative group">
                                    <div class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm bg-gray-100 text-gray-500 border border-gray-200 italic ${isMine ? 'rounded-br-none' : 'rounded-bl-none'}"><i class="bx bx-block mr-1"></i> This message was deleted</div>
                                </div>
                            `;
                            }
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        };

        window.editMsgUI = function (id, text) {
            Swal.fire({
                title: 'Edit Message',
                input: 'textarea',
                inputValue: text,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                confirmButtonColor: '#10b981',
                inputValidator: (value) => {
                    if (!value.trim()) {
                        return 'Message cannot be empty!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(BASE_URL + 'student/auth/edit_message.php', { msg_id: id, message: result.value }, function (res) {
                        if (res.success) {
                            // Will be updated on next poll, but we can optimistically update
                            const el = document.querySelector(`.chat-msg[data-id="${id}"]`);
                            if (el) {
                                const p = el.querySelector('p:not(.text-gray-300)');
                                if (p) { p.innerHTML = escHtml(result.value).replace(/\n/g, '<br>'); }
                                // Add edited tag if not present
                                const timeP = el.querySelector('p.text-gray-300');
                                if (timeP && !timeP.innerHTML.includes('(edited)')) {
                                    timeP.innerHTML = '<span class="italic">(edited)</span> ' + timeP.innerHTML;
                                }
                            }
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        }

        function buildDateDivider(label) {
            return `
            <div class="flex justify-center my-6 sticky top-2 z-[30] date-divider transition-opacity duration-500 opacity-0 pointer-events-none">
                <span class="px-4 py-1.5 rounded-xl bg-white/95 backdrop-blur-md border border-gray-100 text-[11px] font-semibold text-gray-400 shadow-sm">
                    ${label}
                </span>
            </div>`;
        }

        function sendMessage() {
            const text = $input.value.trim();
            const file = $fileInput ? $fileInput.files[0] : null;

            if (!text && !file || isSending) return;

            isSending = true;
            const originalBtnHtml = $sendBtn.innerHTML;
            $sendBtn.innerHTML = '<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>';

            const formData = new FormData();
            formData.append('message', text);
            if (file) formData.append('attachment', file);

            $.ajax({
                url: BASE_URL + 'student/auth/send_message.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        const empty = document.getElementById('emptyState');
                        if (empty) empty.remove();

                        // Clear inputs
                        $input.value = '';
                        $input.style.height = 'auto';
                        clearAttachment();

                        // Refresh poll immediately to show the sent message correctly
                        clearTimeout(pollTimer);
                        pollMessages();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to send. Check your connection.', 'error');
                },
                complete: function () {
                    isSending = false;
                    $sendBtn.innerHTML = originalBtnHtml;
                    $input.focus();
                }
            });
        }

        function pollMessages() {
            $.ajax({
                url: BASE_URL + 'student/auth/fetch_messages.php',
                type: 'GET',
                data: {
                    last_id: lastId,
                    changed_since: changedSince
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        if (res.server_time) changedSince = res.server_time;

                        if (res.messages.length > 0) {
                            const empty = document.getElementById('emptyState');
                            if (empty) empty.remove();

                            const bottom = document.getElementById('chatBottom');
                            const wasAtBottom = isNearBottom();

                            res.messages.forEach(function (msg) {
                                const existing = document.querySelector(`.chat-msg[data-id="${msg.id}"]`);

                                if (existing) {
                                    // Update existing message if it's edited or deleted
                                    const isMine = msg.sender_id == MY_USER_ID;

                                    if (msg.is_deleted) {
                                        // WhatsApp style deletion UI
                                        existing.outerHTML = `
                                        <div class="flex items-end gap-3 ${isMine ? 'flex-row-reverse' : ''} chat-msg" data-id="${msg.id}">
                                            <div class="max-w-[85%] md:max-w-[72%] ${isMine ? 'items-end' : 'items-start'} flex flex-col gap-1 relative group">
                                                <div class="px-4 py-3 rounded-2xl text-sm font-medium leading-relaxed shadow-sm bg-gray-100 text-gray-500 border border-gray-200 italic ${isMine ? 'rounded-br-none' : 'rounded-bl-none'}">
                                                    <i class="bx bx-block mr-1"></i> This message was deleted
                                                </div>
                                            </div>
                                        </div>`;
                                    } else if (msg.is_edited) {
                                        const p = existing.querySelector('p:not(.text-gray-300)');
                                        if (p) p.innerHTML = escHtml(msg.message).replace(/\n/g, '<br>');

                                        const timeP = existing.querySelector('p.text-gray-300');
                                        if (timeP && !timeP.innerHTML.includes('(edited)')) {
                                            timeP.innerHTML = '<span class="italic text-[9px] opacity-70">(edited)</span> ' + timeP.innerHTML;
                                        }
                                    }
                                    return;
                                }

                                const msgDateLabel = getJSDateHeader(msg.sent_at);
                                if (msgDateLabel !== lastDateShown) {
                                    lastDateShown = msgDateLabel;
                                    bottom.insertAdjacentHTML('beforebegin', buildDateDivider(msgDateLabel));
                                }

                                bottom.insertAdjacentHTML('beforebegin', buildBubble(msg, msg.sender_id == MY_USER_ID));
                                lastId = Math.max(lastId, parseInt(msg.id));

                                // Update last message time in header
                                const timeStr = formatTime(msg.sent_at);
                                const statusEl = document.querySelector('#onlineCount');
                                if (statusEl) {
                                    statusEl.innerHTML = `<?= $classCount ?> members <span class="hidden md:inline mx-1 font-semibold opacity-20">•</span> <span class="hidden md:inline">Last message: ${timeStr}</span>`;
                                }
                            });

                            if (wasAtBottom) scrollBottom();
                        }
                    }
                },
                complete: function () {
                    pollTimer = setTimeout(pollMessages, 3000);
                }
            });
        }

        function getJSDateHeader(dateStr) {
            const d = new Date(dateStr.replace(' ', 'T'));
            const now = new Date();

            const isToday = d.toDateString() === now.toDateString();
            const yesterday = new Date(now);
            yesterday.setDate(now.getDate() - 1);
            const isYesterday = d.toDateString() === yesterday.toDateString();

            if (isToday) return 'Today';
            if (isYesterday) return 'Yesterday';

            const diffDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));
            if (diffDays < 7) {
                return d.toLocaleDateString('en-US', { weekday: 'long' });
            }

            return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }

        function isNearBottom() {
            return $messages.scrollHeight - $messages.scrollTop - $messages.clientHeight < 150;
        }

        function formatTime(dateStr) {
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }

        function escHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Emoji Picker ──────────────────────────────────────────
        const EMOJIS = ['😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊', '😋', '😎', '😍', '🥰', '😘', '🤩', '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😒', '😞', '😟', '😤', '😢', '😭', '😦', '😧', '😨', '🥺', '😮', '😯', '😲', '😳', '🤯', '😱', '🥵', '😴', '🤤', '😪', '😵', '🤐', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👻', '💀', '☠️', '🤡', '👋', '🤚', '🖐️', '✋', '🤙', '👍', '👎', '✌️', '🤞', '🤟', '🤘', '🤙', '👌', '🤌', '🤏', '👈', '👉', '👆', '☝️', '👇', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '🔥', '⭐', '🌟', '✨', '💫', '💥', '💢', '💦', '💨', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🎮', '📚', '📖', '💡', '✅', '❌', '⚠️', '🔔', '🔕', '💬', '🗨️', '💭', '🙏', '👏', '🤝', '💪', '🦾', '🧠', '👀', '👁️'];
        const $emojiBtn = document.getElementById('emojiToggleBtn');
        const $emojiPicker = document.getElementById('emojiPicker');
        const $emojiGrid = document.getElementById('emojiGrid');
        if ($emojiGrid) {
            $emojiGrid.innerHTML = EMOJIS.map(e => `<button type="button" class="flex items-center justify-center emoji-btn text-xl hover:bg-gray-100 rounded-xl p-1 cursor-pointer transition-colors leading-none" data-emoji="${e}">${e}</button>`).join('');
            $emojiGrid.addEventListener('click', (e) => {
                const btn = e.target.closest('.emoji-btn');
                if (!btn) return;
                const emoji = btn.dataset.emoji;
                const start = $input.selectionStart;
                $input.value = $input.value.substring(0, start) + emoji + $input.value.substring($input.selectionEnd);
                const newPos = start + emoji.length;
                $input.setSelectionRange(newPos, newPos);
                $input.focus();
                $input.dispatchEvent(new Event('input'));
                $emojiPicker.classList.add('hidden');
            });
        }
        if ($emojiBtn) {
            $emojiBtn.addEventListener('click', (e) => { e.stopPropagation(); $emojiPicker.classList.toggle('hidden'); });
        }
        document.addEventListener('click', (e) => { if ($emojiPicker && !$emojiPicker.contains(e.target) && e.target !== $emojiBtn) $emojiPicker.classList.add('hidden'); });

        // Date header auto-hide logic
        let dateHideTimeout;
        function refreshDividers() {
            const dividers = document.querySelectorAll('.date-divider');
            dividers.forEach(d => {
                // Check if there are visible messages following this divider
                let hasVisible = false;
                let next = d.nextElementSibling;
                while (next && !next.classList.contains('date-divider')) {
                    if (next.classList.contains('chat-msg') && !next.classList.contains('hidden')) {
                        hasVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                if (!hasVisible) d.classList.add('hidden');
                else d.classList.remove('hidden');
            });
        }

        $messages.addEventListener('scroll', () => {
            const dividers = document.querySelectorAll('.date-divider:not(.hidden)');
            dividers.forEach(d => {
                d.classList.remove('opacity-0');
                d.classList.add('translate-y-0');
            });

            clearTimeout(dateHideTimeout);
            dateHideTimeout = setTimeout(() => {
                dividers.forEach(d => {
                    d.classList.add('opacity-0');
                });
            }, 1200);
        });

        // ── Search Logic ──────────────────────────────────────────
        const $searchToggle = document.getElementById('msgSearchToggleBtn');
        const $closeSearch = document.getElementById('closeSearchBtn');
        const $headerDefault = document.getElementById('headerDefaultContent');
        const $headerSearch = document.getElementById('headerSearchContent');
        const $searchInput = document.getElementById('msgSearchInput');
        const $searchCount = document.getElementById('searchCount');

        $searchToggle.addEventListener('click', () => {
            $headerDefault.classList.add('hidden');
            $headerSearch.classList.remove('hidden');
            $headerSearch.classList.add('flex');
            $searchInput.focus();
        });

        $closeSearch.addEventListener('click', () => {
            $headerSearch.classList.add('hidden');
            $headerSearch.classList.remove('flex');
            $headerDefault.classList.remove('hidden');
            $searchInput.value = '';
            performSearch('');
        });

        $searchInput.addEventListener('input', function () {
            performSearch(this.value.trim().toLowerCase());
        });

        function performSearch(query) {
            const msgs = document.querySelectorAll('.chat-msg');
            let matches = 0;

            msgs.forEach(m => {
                // Search all text within the message container (name, message content, attachments)
                const text = m.innerText.toLowerCase() || '';
                const hasMatch = text.includes(query);

                if (hasMatch) {
                    m.classList.remove('hidden');
                    matches++;
                } else {
                    m.classList.add('hidden');
                }
            });

            $searchCount.textContent = query ? `${matches} match${matches === 1 ? '' : 'es'}` : '0 matches';
            refreshDividers();
            if (!query) scrollBottom(false);
        }

        // Sidebar search (Filter chat list)
        const $sidebarSearch = document.getElementById('sidebarChatSearch');
        $sidebarSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            const items = document.querySelectorAll('.chat-list-item'); // Assuming a class for items
            items.forEach(item => {
                const name = item.querySelector('h4')?.textContent.toLowerCase() || '';
                item.style.display = name.includes(q) ? 'flex' : 'none';
            });
        });
        // ────────────────────────────────────────────────────────

        scrollBottom(false);
        $input.focus();
        pollTimer = setTimeout(pollMessages, 3000);

        // Clear notification dot
        const _dot = document.getElementById('chatNotifDot');
        if (_dot) _dot.classList.add('hidden');
        window._chatLastSeenId = lastId;

        window._stopChatPoll = function () { clearTimeout(pollTimer); window._chatLastSeenId = lastId; };
    })();
</script>
<!-- ── EduBot AI Support Widget ──────────────────────────────────────────── -->

<!-- Floating Trigger Button -->
<button id="eduBotToggle"
    onclick="toggleEduBot()"
    class="fixed bottom-24 right-4 md:bottom-8 md:right-8 z-[500] size-14 rounded-full bg-gradient-to-br from-violet-600 to-purple-700 text-white shadow-2xl shadow-violet-300/50 flex items-center justify-center transition-all hover:scale-110 active:scale-95 border-2 border-white/30"
    data-tippy-content="Ask EduBot — AI Support">
    <i class="bx bx-robot text-2xl" id="eduBotBtnIcon"></i>
    <!-- Pulse ring when closed -->
    <span id="eduBotPing" class="absolute -top-1 -right-1 flex size-4">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
        <span class="relative inline-flex size-4 rounded-full bg-violet-500"></span>
    </span>
</button>

<!-- Chat Panel -->
<div id="eduBotPanel"
    class="hidden fixed z-[500] bg-white rounded-3xl shadow-2xl shadow-gray-300/60 border border-gray-100 flex flex-col overflow-hidden fadeIn"
    style="
        bottom: 6.5rem; right: 1rem;
        width: min(calc(100vw - 2rem), 380px);
        height: min(420px, calc(100dvh - 10rem));
    ">

    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-600 to-purple-700 px-5 py-4 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="size-10 rounded-2xl bg-white/20 flex items-center justify-center border border-white/30">
                <i class="bx bx-robot text-white text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white text-sm"><?= strtoupper($result->school_name) ?> EduBot</h3>
                <div class="flex items-center gap-1.5">
                    <span class="size-2 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="text-white/70 text-[10px] font-semibold">AI Support · Always Online</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="clearEduBot()" class="text-white/60 hover:text-white transition text-xs font-bold cursor-pointer" title="Clear chat">
                <i class="bx bx-trash text-lg"></i>
            </button>
            <button onclick="toggleEduBot()" class="text-white/60 hover:text-white transition cursor-pointer">
                <i class="bx bx-x text-2xl"></i>
            </button>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="eduBotMessages" class="flex-1 overflow-y-auto p-4 space-y-3 scroll-smooth">
        <!-- Welcome message -->
        <div class="flex items-end gap-2 edubot-msg">
            <div class="size-7 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                <i class="bx bx-robot text-violet-600 text-sm"></i>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[80%]">
                <p class="text-xs text-gray-700 leading-relaxed">👋 Hi! I'm <?= strtoupper($result->school_name) ?> <strong>EduBot</strong>, your AI support assistant. I can help you with anything about this school portal. What do you need? 😊</p>
            </div>
        </div>
    </div>

    <!-- Quick Prompts -->
    <div id="eduBotQuickPrompts" class="px-3 py-2 border-t border-gray-50 flex gap-2 overflow-x-auto shrink-0">
        <button onclick="sendEduBotQuick('How do I take an exam?')" class="shrink-0 px-3 py-1.5 bg-violet-50 text-violet-600 rounded-full text-[10px] font-bold whitespace-nowrap hover:bg-violet-100 transition cursor-pointer">📝 Take Exam</button>
        <button onclick="sendEduBotQuick('How do I view my results?')" class="shrink-0 px-3 py-1.5 bg-violet-50 text-violet-600 rounded-full text-[10px] font-bold whitespace-nowrap hover:bg-violet-100 transition cursor-pointer">📊 View Results</button>
        <button onclick="sendEduBotQuick('How do I reset my password?')" class="shrink-0 px-3 py-1.5 bg-violet-50 text-violet-600 rounded-full text-[10px] font-bold whitespace-nowrap hover:bg-violet-100 transition cursor-pointer">🔑 Password Help</button>
        <button onclick="sendEduBotQuick('How do I contact admin?')" class="shrink-0 px-3 py-1.5 bg-violet-50 text-violet-600 rounded-full text-[10px] font-bold whitespace-nowrap hover:bg-violet-100 transition cursor-pointer">📞 Contact Admin</button>
    </div>

    <!-- Input Area -->
    <div class="px-3 pb-3 pt-2 border-t border-gray-100 shrink-0">
        <div class="flex items-center gap-2 bg-gray-50 rounded-2xl border border-gray-200 focus-within:border-violet-400 focus-within:ring-2 focus-within:ring-violet-100 transition-all px-4 py-2">
            <input type="text" id="eduBotInput"
                class="flex-1 bg-transparent text-sm text-gray-700 placeholder:text-gray-400 outline-none"
                placeholder="Ask me anything..."
                onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendEduBot(); }">
            <button onclick="sendEduBot()" id="eduBotSendBtn"
                class="size-8 rounded-xl bg-violet-600 hover:bg-violet-700 text-white flex items-center justify-center transition-all active:scale-90 cursor-pointer shrink-0">
                <i class="bx bx-send text-sm"></i>
            </button>
        </div>
    </div>
</div>

<script>
// ── EduBot Chat Widget ────────────────────────────────────────────────────
let eduBotOpen = false;

function toggleEduBot() {
    const panel = document.getElementById('eduBotPanel');
    const ping  = document.getElementById('eduBotPing');
    const icon  = document.getElementById('eduBotBtnIcon');

    eduBotOpen = !eduBotOpen;
    panel.classList.toggle('hidden', !eduBotOpen);

    if (eduBotOpen) {
        ping.classList.add('hidden');
        icon.className = 'bx bx-x text-2xl';
        setTimeout(() => document.getElementById('eduBotInput').focus(), 150);
        scrollEduBot();
    } else {
        icon.className = 'bx bx-robot text-2xl';
    }
}

function scrollEduBot() {
    const msgs = document.getElementById('eduBotMessages');
    msgs.scrollTop = msgs.scrollHeight;
}

function appendMsg(text, role) {
    const msgs = document.getElementById('eduBotMessages');

    if (role === 'user') {
        msgs.insertAdjacentHTML('beforeend', `
            <div class="flex justify-end edubot-msg">
                <div class="bg-violet-600 text-white rounded-2xl rounded-br-sm px-4 py-3 max-w-[80%]">
                    <p class="text-xs leading-relaxed">${escHtml(text)}</p>
                </div>
            </div>`);
    } else {
        // Convert newlines and markdown-bold to HTML
        const formatted = escHtml(text)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        msgs.insertAdjacentHTML('beforeend', `
            <div class="flex items-end gap-2 edubot-msg">
                <div class="size-7 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                    <i class="bx bx-robot text-violet-600 text-sm"></i>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[80%]">
                    <p class="text-xs text-gray-700 leading-relaxed">${formatted}</p>
                </div>
            </div>`);
    }
    scrollEduBot();
}

function appendTyping() {
    const msgs = document.getElementById('eduBotMessages');
    msgs.insertAdjacentHTML('beforeend', `
        <div class="flex items-end gap-2 edubot-msg" id="eduBotTyping">
            <div class="size-7 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                <i class="bx bx-robot text-violet-600 text-sm"></i>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-3">
                <div class="flex items-center gap-1">
                    <span class="size-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="size-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="size-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
            </div>
        </div>`);
    scrollEduBot();
}

function sendEduBot() {
    const input = document.getElementById('eduBotInput');
    const msg   = input.value.trim();
    if (!msg) return;

    // Hide quick prompts after first message
    document.getElementById('eduBotQuickPrompts').classList.add('hidden');

    input.value = '';
    appendMsg(msg, 'user');
    appendTyping();

    const sendBtn = document.getElementById('eduBotSendBtn');
    sendBtn.disabled = true;
    input.disabled = true;

    $.ajax({
        url: BASE_URL + 'auth/ai_support.php',
        type: 'POST',
        data: { message: msg },
        dataType: 'json',
        success: function(res) {
            document.getElementById('eduBotTyping')?.remove();
            if (res.success) {
                appendMsg(res.reply, 'robot');
            } else {
                appendMsg('Sorry, I ran into an issue. Please try again.', 'robot');
            }
        },
        error: function() {
            document.getElementById('eduBotTyping')?.remove();
            appendMsg('⚠️ Connection issue. Please check your internet and try again.', 'robot');
        },
        complete: function() {
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });
}

function sendEduBotQuick(text) {
    document.getElementById('eduBotInput').value = text;
    sendEduBot();
}

function clearEduBot() {
    const msgs = document.getElementById('eduBotMessages');
    // Keep only the welcome message (first child)
    while (msgs.children.length > 1) {
        msgs.removeChild(msgs.lastChild);
    }
    document.getElementById('eduBotQuickPrompts').classList.remove('hidden');
    // Clear server-side history via a flag
    $.post(BASE_URL + 'auth/ai_support.php', { message: '__clear__' });
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

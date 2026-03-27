<?php
require '../../connections/db.php';

// We need an endpoint to save attendance: `admin/auth/qr_attendance_api.php`
// But we will build the UI first.
?>

<div class="fadeIn w-full md:p-8 p-4 h-[calc(100vh-80px)] flex flex-col items-center">
    
    <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 flex flex-col h-full md:h-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-indigo-800 p-6 flex items-center justify-between text-white shrink-0">
            <div>
                <h2 class="text-2xl font-semibold mb-1">Fast Attendance Scanner</h2>
                <p class="text-white/70 text-sm font-semibold tracking-wide">Hold Student QR ID to camera</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 shadow-inner">
                <i class="bx bx-qr-scan text-2xl text-white animate-pulse"></i>
            </div>
        </div>

        <!-- Scanner Container -->
        <div class="flex-1 relative bg-black flex items-center justify-center min-h-[300px]">
            <div id="reader" class="w-full h-full max-h-[60vh] object-cover">
                <!-- Video rendered here -->
            </div>
            <!-- Overlay visual -->
            <div class="absolute inset-0 pointer-events-none border-[30px] border-white/10 z-10 flex items-center justify-center">
                <div class="w-64 h-64 border-2 border-white/50 rounded-3xl relative">
                    <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-white"></div>
                    <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-white"></div>
                    <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-white"></div>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-white"></div>
                    <!-- Scanning line animation -->
                    <div class="w-full h-1 bg-green-500/80 shadow-[0_0_15px_#22c55e] absolute top-1/2 -translate-y-1/2 animate-scan"></div>
                </div>
            </div>
        </div>

        <!-- Action / Status Bar -->
        <div class="p-6 bg-gray-50 flex items-center justify-between gap-4 shrink-0">
            <div class="flex-1">
                <div id="scanStatus" class="bg-blue-50 text-blue-700 px-4 py-3 rounded-xl border border-blue-100 font-bold text-sm tracking-wide flex items-center gap-2 transition-all">
                    <i class="bx bx-info-circle text-lg"></i>
                    <span>Awaiting Scan...</span>
                </div>
            </div>
            <button id="toggleCamBtn" class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 hover:bg-indigo-600 hover:text-white transition flex items-center justify-center shadow-inner cursor-pointer" data-tippy-content="Switch Camera">
                <i class="bx bx-camera text-2xl"></i>
            </button>
        </div>
    </div>

    <!-- Fallback Manual Entry -->
    <div class="mt-6 w-full max-w-2xl bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="flex-1 relative group">
            <i class="bx bx-id-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors text-lg"></i>
            <input type="text" id="manualUserId" class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition" placeholder="Manual Override: Enter User ID (e.g. STU123)">
        </div>
        <button id="manualSubmit" class="bg-gray-800 text-white px-6 py-3 rounded-xl hover:bg-black transition-all font-bold text-sm shadow-md cursor-pointer shrink-0">
            Submit
        </button>
    </div>

</div>

<!-- CSS Animation for Scanner -->
<style>
@keyframes scan {
    0%, 100% { transform: translateY(-3000%); opacity: 0; }
    50% { opacity: 1; }
}
.animate-scan {
    animation: scan 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}
#reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
}
#reader {
    border: none !important;
}
</style>

<script>
    let html5QrcodeScanner;
    let isProcessing = false;

    function initQRScanner() {
        if(typeof Html5Qrcode === 'undefined') {
            setTimeout(initQRScanner, 500);
            return;
        }

        if (window.isSecureContext === false && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            setStatus("Browser blocked camera. You must use HTTPS or localhost to scan QR codes.", "error");
            return;
        }

        try {
            html5QrcodeScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
            
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // Try to prefer back camera if multiple exist
                    let cameraId = devices[0].id;
                    const backCamera = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('environment'));
                    if (backCamera) cameraId = backCamera.id;
                    
                    html5QrcodeScanner.start(cameraId, config, onScanSuccess, onScanFailure)
                    .catch(err => {
                        console.error("Camera start error:", err);
                        setStatus("Camera use prevented or inaccessible", "error");
                    });
                } else {
                    setStatus("No camera devices found", "error");
                }
            }).catch(err => {
                console.error("Camera permission error:", err);
                setStatus("Please grant camera permissions to your browser", "error");
            });

        } catch(e) {
            console.error(e);
            setStatus("Scanner library failed to load.", "error");
        }
    }

    // Dynamic loading of external script for AJAX routing frameworks
    if(typeof Html5Qrcode === 'undefined') {
        let script = document.createElement('script');
        script.src = "https://unpkg.com/html5-qrcode";
        script.onload = initQRScanner;
        document.head.appendChild(script);
    } else {
        initQRScanner();
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        
        let userId = decodedText;
        if (userId.startsWith('USER_ID:')) {
            userId = userId.split('USER_ID:')[1];
        }

        if(userId) {
            handleCheckIn(userId);
        }
    }

    function onScanFailure(error) {
        // usually ignore constant failure frames
    }

    function handleCheckIn(userId) {
        if(isProcessing) return;
        isProcessing = true;
        
        // Pause visual scan line parsing
        $('.animate-scan').css('animation-play-state', 'paused');
        
        setStatus("Processing " + userId + "...", "info");

        if(window.triggerHaptic) window.triggerHaptic(50); // device vibrate if supported
        
        // Play beep sound
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            osc.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.1);
        } catch(e){}

        $.ajax({
            url: '../admin/auth/qr_attendance_api.php',
            method: 'POST',
            data: { user_id: userId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    setStatus(`<span class="font-semibold">${res.name}</span> marked Present.`, "success");
                    if (window.showToast) window.showToast('Attendance logged for ' + res.name, 'success');
                } else {
                    setStatus(res.message, "error");
                    if (window.showToast) window.showToast(res.message, 'error');
                }
            },
            error: function() {
                setStatus("Network Error.", "error");
            },
            complete: function() {
                setTimeout(() => {
                    isProcessing = false;
                    setStatus("Awaiting Scan...", "info");
                    $('.animate-scan').css('animation-play-state', 'running');
                    $('#manualUserId').val('');
                }, 2500); // 2.5sec cooldown
            }
        });
    }

    function setStatus(msg, type) {
        const box = $('#scanStatus');
        box.removeClass('bg-blue-50 text-blue-700 bg-green-50 text-green-700 bg-red-50 text-red-700 border-blue-100 border-green-100 border-red-100');
        
        let icon = 'bx-info-circle';
        if(type === 'success') {
            box.addClass('bg-green-50 text-green-700 border-green-100');
            icon = 'bx-check-circle';
        } else if(type === 'error') {
            box.addClass('bg-red-50 text-red-700 border-red-100');
            icon = 'bx-x-circle';
        } else {
            box.addClass('bg-blue-50 text-blue-700 border-blue-100');
        }

        box.html(`<i class="bx ${icon} text-lg"></i> <span>${msg}</span>`);
    }

    // Manual Entry
    $('#manualSubmit').on('click', function() {
        const uid = $('#manualUserId').val().trim();
        if(uid) handleCheckIn(uid);
    });

    $('#manualUserId').on('keypress', function(e) {
        if(e.which === 13) {
            e.preventDefault();
            $('#manualSubmit').click();
        }
    });

    // Cleanup on page change
    const oldLoadPage = window.loadPage;
    window.loadPage = function(url) {
        if(html5QrcodeScanner && html5QrcodeScanner.isScanning) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                oldLoadPage(url);
            }).catch(err => oldLoadPage(url));
        } else {
            oldLoadPage(url);
        }
    };
</script>

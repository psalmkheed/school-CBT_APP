<?php
require_once __DIR__ . '/../connections/db.php';
require_once __DIR__ . '/../auth/check.php';
?>

<div class="fadeIn w-full md:p-8 p-4 bg-gray-50/30">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div
                class="size-14 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-xl shadow-indigo-100 flex items-center justify-center">
                <i class="bx bx-sparkles text-3xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">AI Study Path</h1>
                <p class="text-sm text-gray-400 font-medium italic">Personalized learning recommendations based on your
                    performance</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span
                class="px-4 py-2 bg-white rounded-xl border border-gray-100 text-[10px] font-semibold uppercase text-gray-400 tracking-widest">
                Term Analysis: <?= $_SESSION['active_term'] ?? 'Current' ?>
            </span>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left: Performance Overview -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Strength vs Weakness Radar -->
            <div
                class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-50 flex flex-col md:flex-row items-center gap-10">
                <div class="w-full md:w-1/2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Cognitive Map</h3>
                    <p class="text-xs text-gray-400 mb-6 leading-relaxed">This chart visualizes your subject mastery.
                        Wider areas represent your strengths.</p>
                    <div class="h-64">
                        <canvas id="masteryRadar"></canvas>
                    </div>
                </div>
                <div class="w-full md:w-1/2 space-y-4" id="aiInsightContainer">
                    <div class="p-5 rounded-3xl bg-violet-50 border border-violet-100">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="bx bx-bot text-xl text-violet-600"></i>
                            <span class="text-[10px] font-semibold text-violet-400 uppercase tracking-widest">AI
                                Observation</span>
                        </div>
                        <p id="aiObservation" class="text-sm font-bold text-violet-900 leading-relaxed italic">Analyzing
                            your scores to generate insights...</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-4 rounded-[1.5rem] bg-emerald-50 border border-emerald-100">
                            <span
                                class="text-[9px] font-semibold text-emerald-500 uppercase tracking-widest block mb-1">Mastered</span>
                            <span id="masteredCount" class="text-2xl font-semibold text-emerald-700">0</span>
                        </div>
                        <div class="p-4 rounded-[1.5rem] bg-orange-50 border border-orange-100">
                            <span
                                class="text-[9px] font-semibold text-orange-500 uppercase tracking-widest block mb-1">Focus
                                Areas</span>
                            <span id="weakCount" class="text-2xl font-semibold text-orange-700">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Breakdown -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-50">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Mastery Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="subjectGrid">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Right: Recommendations -->
        <div class="space-y-8">
            <div
                class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-200 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                <h3 class="text-xl font-semibold mb-2 relative z-10">Recommended Study Path</h3>
                <p class="text-xs text-indigo-100/80 font-medium mb-6 relative z-10 leading-relaxed">Targeted materials
                    to help you boost your weakest areas first.</p>

                <div class="space-y-4 relative z-10" id="studyRoadmap">
                    <!-- Roadmap items -->
                    <div class="flex flex-col items-center justify-center py-10 opacity-50">
                        <i class="bx bxs-git-compare text-4xl mb-2"></i>
                        <p class="text-[10px] font-bold uppercase tracking-widest">Awaiting Analysis</p>
                    </div>
                </div>
            </div>

            <!-- Quick Action: Practice Mode -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm text-center">
                <div
                    class="size-16 rounded-3xl bg-orange-50 text-orange-600 flex items-center justify-center mx-auto mb-4">
                    <i class="bx bxs-running text-3xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Ready to Boost?</h4>
                <p class="text-xs text-gray-400 font-medium mb-6 leading-relaxed">Take a mock assessment in your focus
                    areas to track improvement.</p>
                <button onclick="$('#sideTest').click()"
                    class="w-full py-4 bg-gray-900 text-white rounded-2xl font-semibold text-sm hover:bg-black transition-all shadow-lg active:scale-95">Start
                    Practice Session</button>
            </div>
        </div>

    </div>
</div>

<script>
    $(function () {
        $.ajax({
            url: window.APP_URL + 'student/auth/study_api.php',
            type: 'GET',
            success: function (res) {
                if (res.status === 'success') {
                    updateUI(res);
                } else if (res.status === 'fee_restriction') {
                    showFeeRestriction(res.message);
                } else {
                    $('#aiObservation').text(res.message || 'Unable to load analysis.');
                }
            },
            error: function () {
                $('#aiObservation').text('Analytics engine is temporarily offline. Please try again later.');
            }
        });

        function showFeeRestriction(msg) {
            // Clear containers and show locked state
            $('#aiInsightContainer').html(`
            <div class="p-6 rounded-3xl bg-red-50 border border-red-100 flex flex-col items-center text-center">
                <i class="bx bxs-lock text-4xl text-red-500 mb-2"></i>
                <p class="text-sm font-bold text-red-900 mb-2">Access Restricted</p>
                <p class="text-[11px] text-red-700 leading-relaxed">${msg}</p>
                <button class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl text-[10px] font-semibold uppercase tracking-widest">Pay Fees</button>
            </div>
        `);

            $('#studyRoadmap').html(`
            <div class="flex flex-col items-center justify-center py-10 opacity-60">
                 <i class="bx bxs-lock-alt text-4xl mb-3 text-indigo-300"></i>
                 <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-100">Unlock with Fee Payment</p>
            </div>
        `);
        }

        function updateUI(data) {
            const perf = data.performance;

            // 1. Update Counts
            $('#masteredCount').text(data.mastered_subjects.length);
            $('#weakCount').text(data.weak_subjects.length);

            // 2. Radar Chart
            const ctx = document.getElementById('masteryRadar').getContext('2d');
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: perf.map(p => p.subject),
                    datasets: [{
                        label: 'Subject Mastery %',
                        data: perf.map(p => p.avg_p),
                        fill: true,
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366f1',
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#6366f1'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { display: false }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });

            // 3. AI Observation logic - Dynamic and more "AI-like"
            let obs = "";
            if (perf.length === 0) {
                obs = "I'm currently waiting for your first assessment results. Once you take an exam, I will analyze your performance to map your cognitive strengths and weaknesses.";
            } else if (data.weak_subjects.length === 0) {
                obs = "Incredible work! You are maintaining a strong edge across all subjects. I've analyzed your consistent performance and suggest exploring advanced materials in the Library to further challenge yourself.";
            } else {
                const worst = perf[0]; // Already sorted by avg_p ASC in PHP
                const best = perf[perf.length - 1];
                obs = `My analysis shows a significant mastery in <strong>${best.subject}</strong> (${Math.round(best.avg_p)}%), but your performance in <strong>${worst.subject}</strong> (${Math.round(worst.avg_p)}%) is dragging down your potential. Prioritizing ${worst.subject} will provide the fastest boost to your overall rank.`;
            }
            $('#aiObservation').html(obs);

            // 4. Subject Breakdown
            let gridHtml = '';
            if (perf.length > 0) {
                perf.forEach(p => {
                    const color = p.avg_p >= 80 ? 'emerald' : (p.avg_p >= 60 ? 'indigo' : 'orange');
                    const label = p.avg_p >= 80 ? 'Mastery' : (p.avg_p >= 60 ? 'Stable' : 'Focus');
                    gridHtml += `
                    <div class="flex items-center justify-between p-4 rounded-3xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-center gap-3">
                             <div class="size-10 rounded-2xl bg-${color}-100 text-${color}-600 flex items-center justify-center font-semibold text-xs shadow-inner">${p.subject.charAt(0)}</div>
                             <div>
                                 <p class="text-sm font-semibold text-gray-800">${p.subject}</p>
                                 <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">${label}</p>
                             </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-semibold text-${color}-600">${Math.round(p.avg_p)}%</span>
                        </div>
                    </div>
                `;
                });
            } else {
                gridHtml = `
                <div class="col-span-2 py-10 text-center bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-100">
                    <i class="bx bx-chart text-3xl text-gray-300 mb-2"></i>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">No Subject Data Available</p>
                </div>
            `;
            }
            $('#subjectGrid').html(gridHtml);

            // 5. Recommendations
            let roadHtml = '';
            if (data.recommendations && data.recommendations.length > 0) {
                data.recommendations.forEach((m, idx) => {
                    roadHtml += `
                    <div class="flex gap-4 group">
                        <div class="flex flex-col items-center">
                            <div class="size-6 rounded-full bg-white text-indigo-600 flex items-center justify-center text-[10px] font-semibold z-10 shrink-0 border-2 border-indigo-100">${idx + 1}</div>
                            ${idx < data.recommendations.length - 1 ? '<div class="w-0.5 h-12 bg-white/20 -mt-2"></div>' : ''}
                        </div>
                        <div class="flex-1 -mt-1 pb-4">
                            <p class="text-[9px] font-semibold text-indigo-200 uppercase tracking-widest mb-1">${m.subject}</p>
                            <a href="${window.APP_URL}${m.file_path}" target="_blank" class="block bg-white/10 hover:bg-white/20 border border-white/20 p-3 rounded-2xl transition-all group-active:scale-95">
                                 <p class="text-xs font-bold leading-tight line-clamp-1">${m.title}</p>
                                 <div class="flex items-center gap-1 mt-1 font-bold text-[9px] text-indigo-200">
                                      <i class="bx bx-download"></i> Review Material
                                 </div>
                            </a>
                        </div>
                    </div>
                `;
                });
            } else if (perf.length > 0) {
                roadHtml = `
                <div class="text-center py-10">
                    <div class="size-14 rounded-full bg-white/10 flex items-center justify-center mx-auto mb-4 border border-white/10">
                        <i class="bx bx-checks text-2xl text-white"></i>
                    </div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-100 mb-2">You're All Clear!</p>
                    <p class="text-[11px] text-indigo-200/80 leading-relaxed">No critical weak spots found. Keep maintaining your score above 60%.</p>
                </div>
            `;
        } else {
             roadHtml = `
                <div class="text-center py-10 opacity-60">
                    <i class="bx bx-data text-3xl mb-3"></i>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-100">Waiting for Data</p>
                </div>
            `;
        }
        $('#studyRoadmap').html(roadHtml);
    }
});
</script>
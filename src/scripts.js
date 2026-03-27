// ── Global Configuration ──────────────────────────────────────────────
const BASE_URL = window.APP_URL || '/';

// ── Security: Disable Right-Click & DevTools Shortcuts ────────────────
(function () {
      // Block right-click context menu
      document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            showSecurityWarning();
      });

      // Block devtools keyboard shortcuts
      document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === 'F12') {
                  e.preventDefault();
                  showSecurityWarning();
                  return;
            }
            // Ctrl+Shift+I (Chrome DevTools)
            // Ctrl+Shift+J (Chrome Console)
            // Ctrl+Shift+C (Inspect Element)
            if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
                  e.preventDefault();
                  showSecurityWarning();
                  return;
            }
            // Ctrl+U (View Source)
            if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                  e.preventDefault();
                  showSecurityWarning();
                  return;
            }
      });

      function showSecurityWarning() {
            // Avoid stacking multiple toasts
            if (document.getElementById('sec-toast')) return;
            const toast = document.createElement('div');
            toast.id = 'sec-toast';
            toast.style.cssText = `
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: #1e1e2e; color: #fff; padding: 14px 24px;
            border-radius: 16px; font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px; z-index: 9999999;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08);
            animation: secToastIn 0.3s ease;
        `;
            toast.innerHTML = `<span style="font-size:18px">🔒</span> This action is restricted for security reasons.`;
            document.body.appendChild(toast);
            setTimeout(() => {
                  toast.style.opacity = '0';
                  toast.style.transition = 'opacity 0.3s ease';
                  setTimeout(() => toast.remove(), 300);
            }, 3000);
      }

      // Inject keyframe animation once
      const style = document.createElement('style');
      style.textContent = `@keyframes secToastIn { from { opacity:0; transform:translateX(-50%) translateY(12px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }`;
      document.head.appendChild(style);
})();

window.showToast = function(message, type = 'success') {
      let toast = document.getElementById('global-toast');
      
      if (!toast) {
            toast = document.createElement('div');
            toast.id = 'global-toast';
            toast.className = 'hidden fixed top-6 right-6 z-[999999] min-w-[320px] max-w-md p-4 rounded-2xl shadow-2xl flex items-center gap-4 transition-all duration-300 transform translate-x-12 opacity-0 glass';
            toast.innerHTML = `
                  <div id="toast-icon-wrapper" class="size-10 rounded-xl flex items-center justify-center shrink-0 shadow-inner">
                        <i id="global-toast-icon" class="text-xl"></i>
                  </div>
                  <div class="flex-1">
                        <p id="global-toast-msg" class="text-sm font-bold text-gray-800 leading-tight"></p>
                  </div>
            `;
            document.body.appendChild(toast);
      }

      const icon = document.getElementById('global-toast-icon');
      const msg = document.getElementById('global-toast-msg');
      const wrapper = document.getElementById('toast-icon-wrapper');

      msg.textContent = message;
      
      // Theme colors
      toast.classList.remove('border-green-100', 'border-red-100', 'border-blue-100');
      wrapper.classList.remove('bg-green-500', 'bg-red-500', 'bg-blue-500', 'text-white');

      if (type === 'success') {
            toast.classList.add('border-green-100');
            wrapper.classList.add('bg-green-500', 'text-white');
            icon.className = 'bx bx-check-circle';
      } else if (type === 'error') {
            toast.classList.add('border-red-100');
            wrapper.classList.add('bg-red-500', 'text-white');
            icon.className = 'bx bx-x-circle';
      } else {
            toast.classList.add('border-blue-100');
            wrapper.classList.add('bg-blue-500', 'text-white');
            icon.className = 'bx bx-info-circle';
      }
      
      // Show animation
      toast.classList.remove('hidden', 'translate-x-12', 'opacity-0');
      toast.classList.add('translate-x-0', 'opacity-100');

      // Auto hide
      clearTimeout(toast._timer);
      toast._timer = setTimeout(() => {
            toast.classList.add('translate-x-12', 'opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300);
      }, 4000);
};

// Global legacy alias
window.showAlert = function(type, message) {
      window.showToast(message, type);
};

$(function () {
      // ── Theme Management (Safe Storage Access) ───────────────────────────
      const $body = $('body');
      const $themeIcon = $('#themeIcon');
      
      let savedTheme = 'light';
      try {
            savedTheme = localStorage.getItem('theme') || 'light';
      } catch (e) {
            console.warn('Storage access blocked by browser privacy settings.');
      }

      if (savedTheme === 'dark') {
            $body.addClass('dark');
            $themeIcon.removeClass('bx-moon').addClass('bx-sun');
      }

      $('#themeToggler').on('click', function() {
            $body.toggleClass('dark');
            const isDark = $body.hasClass('dark');
            
            try {
                  localStorage.setItem('theme', isDark ? 'dark' : 'light');
            } catch (e) {}
            
            // Swap icon
            if (isDark) {
                  $themeIcon.removeClass('bx-moon').addClass('bx-sun');
                  window.showToast('Dark mode activated 🌙', 'info');
            } else {
                  $themeIcon.removeClass('bx-sun').addClass('bx-moon');
                  window.showToast('Light mode activated ☀️', 'info');
            }

            if (typeof window.initGrowthChart === 'function') window.initGrowthChart();
      });

      // ── Inactivity Auto Logout (5 Minutes) ──────────────────────────────────
      const AUTO_LOGOUT_TIME_MS = 5 * 60 * 1000;
      let inactivityTimer;

      const isAuthenticatedPage = $body[0].className.match(/\buser-/);

      if (isAuthenticatedPage) {
            const resetTimer = () => {
                  clearTimeout(inactivityTimer);
                  inactivityTimer = setTimeout(() => {
                        window.location.href = `${BASE_URL}auth/logout.php?reason=timeout`;
                  }, AUTO_LOGOUT_TIME_MS);
            };

            $(document).on('mousemove keydown scroll click touchstart', resetTimer);
            resetTimer();
      }

      // Swiper and UI logic remains inside
      function initSwiper() {
            if ($(".swiper").length) {
                  new Swiper('.swiper', {
                        pagination: {
                              el: '.swiper-pagination',
                              clickable: true,
                        },
                        autoplay: {
                              delay: 5000,
                              disableOnInteraction: false
                        },
                        speed: 3000,
                        effect: 'flip',
                        flipEffect: { slideShadows: false },
                        loop: true,
                    });
            }
      }

      // Active link
      $('.li').on("click", function (e) {
            e.preventDefault();
            $('.li').removeClass("active");
            $(this).addClass("active");
      });

      // Sidebar Dynamic page loading
      let originalContent = "";
      $(document).ready(function() {
          originalContent = $("#mainContent").html();
      });


      // Skeleton loader template
      const getSkeletonHTML = () => `
            <div class="fadeIn w-full md:p-8 p-4">
                  <div class="skeleton-title skeleton mb-6"></div>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="skeleton-card skeleton-pulse h-32"></div>
                        <div class="skeleton-card skeleton-pulse h-32"></div>
                        <div class="skeleton-card skeleton-pulse h-32"></div>
                  </div>
                  <div class="skeleton-card skeleton-pulse h-64 w-full"></div>
            </div>
      `;

      window.loadPage = (url) => {
            // Support for jQuery-style fragment selectors (e.g. "index.php #mainContent > *")
            const parts = url.split(" ");
            const fetchUrl = parts[0];
            const selector = parts.slice(1).join(" ");

            // Clean up chat polling if leaving the chat page
            if (typeof window._stopChatPoll === 'function') {
                  window._stopChatPoll();
                  window._stopChatPoll = null;
            }

            const $mc = $("#mainContent");

            // 1. Fade out current content
            $mc.stop(true).animate({ opacity: 0, transform: 'translateY(10px)' }, 150, function() {
                  // 2. Clear and show skeleton immediately to avoid white flash
                  $mc.html(getSkeletonHTML()).css('opacity', 1);
                  
                  // 3. Load new content
                  $.ajax({
                        url: fetchUrl,
                        method: 'GET',
                        success: function(data) {
                              // Small delay to ensure skeleton is seen
                              setTimeout(() => {
                                    $mc.stop(true).animate({ opacity: 0 }, 100, function() {
                                          // Clear previous content
                                          $mc.empty();
                                          
                                          // Handle fragments
                                          if (selector) {
                                                const $temp = $("<div>").append($.parseHTML(data, document, true));
                                                const $fragment = $temp.find(selector);
                                                if ($fragment.length > 0) {
                                                      $mc.append($fragment);
                                                } else {
                                                      $mc.html(data);
                                                }
                                          } else {
                                                $mc.html(data);
                                          }

                                          $mc.stop(true).animate({ opacity: 1, transform: 'translateY(0)' }, 250);
                                          window.scrollTo({ top: 0, behavior: 'smooth' });
                                          
                                          // Initialize components
                                          if (typeof window.initBlogEditor === 'function') window.initBlogEditor();
                                          if (typeof initSwiper === 'function') initSwiper();
                                          if (typeof initTooltips === 'function') initTooltips();
                                          if (typeof window.initGrowthChart === 'function') window.initGrowthChart();
                                          
                                          // Animate numbers
                                          $('.tabular-nums').each(function() {
                                                const $el = $(this);
                                                const finalValue = parseInt($el.text());
                                                if (!isNaN(finalValue)) {
                                                      animateValue($el, 0, finalValue, 1500);
                                                }
                                          });
                                    });
                              }, 200);
                        },
                        error: function(xhr, status, error) {
                              $mc.stop(true).animate({ opacity: 1 }, 200);
                              const errMsg = xhr.status === 404 ? 'Page not found (404)' : 'Failed to load content.';
                              if (window.showToast) window.showToast(errMsg, 'error');
                              $mc.html(`
                                    <div class="p-12 text-center glass rounded-[3rem] border-2 border-dashed border-red-100 mt-8">
                                          <div class="size-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                                <i class="bx bx-signal-slash text-4xl text-red-500"></i>
                                          </div>
                                          <h4 class="text-xl font-semibold text-gray-800 mb-2">Navigation Error</h4>
                                          <p class="text-gray-400 text-sm mb-8">${errMsg}</p>
                                          <button onclick="location.reload()" class="px-8 py-3 bg-gray-100 text-gray-600 font-bold rounded-2xl hover:bg-gray-200 transition-all">Reload Portal</button>
                                    </div>
                              `);
                        }
                  });
            });

            // 4. Update sidebar active state
            const filename = parts[0].split('/').pop().split('?')[0];
            $('.li').removeClass('active-link bg-sky-50 bg-blue-50 text-sky-600 text-blue-600 border-r-4 border-sky-600 border-blue-600');
            $(`.li[data-page*="${filename}"]`).addClass('active-link bg-blue-50 text-blue-600 border-r-4 border-blue-600');
      };

      window.loadPageWithCallback = (url, callback) => {
            const parts = url.split(" ");
            const fetchUrl = parts[0];
            const selector = parts.slice(1).join(" ");
            const $mc = $("#mainContent");

            $mc.fadeTo(120, 0, function() {
                  const slowTimer = setTimeout(() => {
                        $mc.html(getSkeletonHTML());
                  }, 300);

                  $.ajax({
                        url: fetchUrl,
                        method: 'GET',
                        success: function(data) {
                              clearTimeout(slowTimer);
                              $mc.fadeTo(180, 1, function() {
                                    $mc.empty();
                                    if (selector) {
                                          const $temp = $("<div>").append($.parseHTML(data, document, true));
                                          const $fragment = $temp.find(selector);
                                          if ($fragment.length > 0) {
                                                $mc.append($fragment);
                                          } else {
                                                $mc.html(data);
                                          }
                                    } else {
                                          $mc.html(data);
                                    }

                                    if (callback) callback();
                                    if (typeof window.initGrowthChart === 'function') window.initGrowthChart();
                              });
                        },
                        error: function(xhr, status, error) {
                              clearTimeout(slowTimer);
                              $mc.fadeTo(180, 1);
                              if (window.showToast) window.showToast('Failed to load page', 'error');
                        }
                  });
            });
      };

      window.goHome = function() {
            window.loadPage("index.php #mainContent > *");
      };

      // ── Advanced Data Visualization ─────────────────────────────────────
      // ── Utilities ───────────────────────────────────────────────────────
      function animateValue($el, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                  if (!startTimestamp) startTimestamp = timestamp;
                  const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                  const easeOutQuad = progress * (2 - progress);
                  const current = Math.floor(easeOutQuad * (end - start) + start);
                  
                  // Preserve suffix like %
                  const suffix = $el.text().includes('%') ? '%' : '';
                  $el.text(current + suffix);
                  
                  if (progress < 1) {
                        window.requestAnimationFrame(step);
                  }
            };
            window.requestAnimationFrame(step);
      }

      window.initGrowthChart = function() {
            const canvas = document.getElementById('growthChart');
            const masteryCanvas = document.getElementById('masteryChart');
            
            if (!canvas && !masteryCanvas) return;

            // Destroy existing instances if they exist
            if (window.currentGrowthChart) { window.currentGrowthChart.destroy(); window.currentGrowthChart = null; }
            if (window.currentMasteryChart) { window.currentMasteryChart.destroy(); window.currentMasteryChart = null; }

            // Ensure BASE_URL is usable
            const path = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/school_app/';
            const endpoint = path + 'student/auth/chart_data.php?action=get_performance';

            $.getJSON(endpoint, function(res) {
                  if (res.status !== 'success') {
                        console.warn("Analytics Error:", res.message);
                        return;
                  }

                  // 1. Trend Chart
                  if (canvas && res.trend && res.trend.length > 0) {
                        const labels = res.trend.map(t => new Date(t.taken_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                        const values = res.trend.map(t => parseFloat(t.percentage));

                        window.currentGrowthChart = new Chart(canvas, {
                              type: 'line',
                              data: {
                                    labels: labels,
                                    datasets: [{
                                          label: 'Score %',
                                          data: values,
                                          borderColor: '#3b82f6',
                                          backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                          borderWidth: 4,
                                          tension: 0.4,
                                          fill: true,
                                          pointBackgroundColor: '#fff',
                                          pointBorderColor: '#3b82f6',
                                          pointBorderWidth: 3,
                                          pointRadius: 6
                                    }]
                              },
                              options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                          y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { weight: 'bold', size: 10 } } },
                                          x: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 10 } } }
                                    }
                              }
                        });
                  }

                  // 2. Mastery Chart
                  if (masteryCanvas && res.mastery && res.mastery.length > 0) {
                        const mLabels = res.mastery.map(m => m.subject);
                        const mValues = res.mastery.map(m => parseFloat(m.avg_score));

                        window.currentMasteryChart = new Chart(masteryCanvas, {
                              type: 'bar',
                              data: {
                                    labels: mLabels,
                                    datasets: [{
                                          label: 'Avg Score',
                                          data: mValues,
                                          backgroundColor: '#6366f1',
                                          borderRadius: 12,
                                          barThickness: 20
                                    }]
                              },
                              options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    plugins: { legend: { display: false } },
                                    scales: {
                                          x: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { weight: 'bold', size: 10 } } },
                                          y: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 10 } } }
                                    }
                              }
                        });
                  }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                  console.error("Chart Data Fetch Failed:", textStatus, errorThrown);
                  console.log("Details:", jqXHR.responseText);
            });
      };
      
      // Auto-trigger on load
      $(function() {
            setTimeout(window.initGrowthChart, 500);
      });

      window.goHome = function() {
            if (typeof originalContent !== 'undefined' && originalContent) {
                  $("#mainContent").fadeOut(200, function () {
                        $(this).html(originalContent).fadeIn(300, function () {
                              if (typeof window.initGrowthChart === "function") window.initGrowthChart();
                        });
                        $(".li").removeClass("active");
                        $("#sideHome").addClass("active");
                  });
            } else {
                  window.loadPage("index.php #mainContent > *");
            }
      };

      // ── Account Dropdown ─────────────────────────────────────
      function openAccountMenu() {
            if (typeof closeSidebar === 'function') closeSidebar();
            if (typeof closeNotifications === 'function') closeNotifications();

            // Disable tooltip
            const btn = document.querySelector('#dropDownMenu');
            if (btn && btn._tippy) { btn._tippy.hide(); btn._tippy.disable(); }

            $("#dropdownOverlay").addClass("active");
            $("body").addClass("overflow-hidden md:overflow-auto");
            $("#dropDownItems").stop(true).fadeIn(180);
      }

      function closeAccountMenu() {
            // Re-enable tooltip
            const btn = document.querySelector('#dropDownMenu');
            if (btn && btn._tippy) btn._tippy.enable();

            $("#dropdownOverlay").removeClass("active");
            $("body").removeClass("overflow-hidden md:overflow-auto");
            $("#dropDownItems").stop(true).fadeOut(150);
      }

      $("#dropDownMenu").on("click", function(e) {
            e.stopPropagation();
            if ($("#dropDownItems").is(":visible")) {
                  closeAccountMenu();
            } else {
                  openAccountMenu();
            }
      });

      // Close when clicking the overlay
      $("#dropdownOverlay").on("click", function() {
            closeAccountMenu();
      });

      // Stop clicks inside the panel from propagating to document
      $("#dropDownItems").on("click", function(e) {
            e.stopPropagation();
      });

      $("#profile").on("click", () => {
            loadPage(`${BASE_URL}pages/profile.php`);
            closeAccountMenu();
      });

      // ── Sidebar helpers ──────────────────────────────────────────
      function openSidebar() {
            $("#sideBar").addClass("sidebar-open");
            $("#sidebarOverlay").removeClass("hidden");
            closeAccountMenu();
      }
      function closeSidebar() {
            $("#sideBar").removeClass("sidebar-open");
            $("#sidebarOverlay").addClass("hidden");
      }
      function isMobile() { return window.innerWidth < 768; }

      // Hamburger toggle
      $("#sideBar").on("click", "#sideBarTogglerContainer", function (e) {
            e.stopPropagation();
            const $sidebar = $("#sideBar");
            const $icon = $("#sideBarToggler");

            if (isMobile()) {
                  if ($sidebar.hasClass("sidebar-open")) {
                        closeSidebar();
                        $icon.removeClass("rotating");
                  } else {
                        openSidebar();
                        $icon.addClass("rotating");
                  }
            } else {
                  // Desktop: toggle collapsed state
                  $sidebar.toggleClass("sidebar-collapsed");
                  $icon.toggleClass("rotating");
            }
      });

      // Close button inside sidebar (mobile)
      $("#sideBar").on("click", "#sidebarCloseBtn", (e) => {
            e.stopPropagation();
            closeSidebar();
      });

      // Hamburger at the Top Nav (Mobile & Tablet)
      $(document).on("click", "#mobileHamburgerBtn", (e) => {
            e.stopPropagation();
            if (isMobile()) {
                  openSidebar();
            } else {
                  $("#sideBar").toggleClass("sidebar-collapsed");
            }
      });

      // Overlay click closes sidebar
      $(document).on("click", "#sidebarOverlay", () => closeSidebar());

      // Keep sidebar from closing when clicking inside it
      $("#sideBar").on("click", (e) => {
            e.stopPropagation();
            closeAccountMenu();
      });

      // Auto-close sidebar on mobile when a nav item is clicked
      $(".sidebar .li").on("click", function () {
            if (isMobile()) closeSidebar();
      });

      // Global click close (closes dropdowns and mobile sidebar if clicking outside)
      $(document).on("click", (e) => {
            if (!$(e.target).closest("#dropDownItems").length && !$(e.target).closest("#dropDownMenu").length) {
                  closeAccountMenu();
            }
            // If on mobile and clicking outside the sidebar and toggler, close it
            if (isMobile() && 
                !$(e.target).closest("#sideBar").length && 
                !$(e.target).closest("#sideBarToggler").length && 
                $("#sideBar").hasClass("sidebar-open")) {
                  closeSidebar();
            }
      });

      // Student Sidebar Dynamic page loading
      $("#sideHome").on("click", e => { e.preventDefault(); loadPage("index.php #mainContent > *"); });
      $("#sideEvents").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/events.php"); });
      $("#sideChat").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/chat.php"); });
      $("#sideTest").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/exam.php"); });
      $("#sideExamHistory").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/exam_history.php"); });
      $("#sideLibrary").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/library.php"); });
      $("#sideStudy").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/study.php"); });
      $("#sideGamification").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/gamification.php"); });
      $("#sideMyQr").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/my_qr.php"); });
      $("#sideTimetableStudent").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/timetable.php"); });
      $("#sideWaecPractice").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/waec_practice.php"); });
      $("#sideStudents").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/students.php"); });
      $("#sidePromote").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/promote_students.php"); });
      $("#sideStaffExams").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/exams.php"); });
      $("#sideAttendance").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/attendance.php"); });
      $("#sideCA").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/ca_records.php"); });
      $("#sideStaffLibrary").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/library.php"); });
      
      // Staff Study Hub Routes
      $("#staffStudyUpload").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/study_materials.php?tab=All"); });
      $("#staffStudyAssignment").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/study_materials.php?tab=Assignment"); });
      $("#staffStudySyllabus").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/study_materials.php?tab=Syllabus"); });
      $("#staffStudyOther").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/study_materials.php?tab=Other_Download"); });

      $("#sideAiGen").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/ai_generator.php"); });
      $("#sideResults").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/results.php"); });
      $("#sideLessonPlans").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/lesson_plans.php"); });
      $("#sideTimetableStaff").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/timetable.php"); });
      $("#sideAssignmentsStudent").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "student/pages/assignments.php"); });
      $("#sideAssignments").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/assignments.php"); });
      $("#sideStaffPasses").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/hall_passes.php"); });

      // Admin sidebar dynamic page loading
      $("#homepage").on("click", e => { e.preventDefault(); loadPage("index.php #mainContent > *"); });

      $("#createAccount").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/auth/register.php"); });

      $("#usersRecord").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/users.php"); });
      
      // Finance routing
      $("#manageFees").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/finance_fees.php"); });
      $("#expenseTracker").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/finance_expenses.php"); });
      $("#financialReports").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/finance_reports.php"); });

      $("#importData").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/import_data.php"); });
      $("#reportCards").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/report_cards.php"); });
      $("#proctoring").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/proctoring.php"); });
      $("#attTrends").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/attendance_analytics.php"); });
      $("#manageLessonPlans").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/manage_lesson_plans.php"); });
      $("#adminLibrary").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/digital_library.php"); });

      // Study Material Routing
      $("#studyUpload").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/study_materials.php?tab=All"); });
      $("#studyAssignment").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/study_materials.php?tab=Assignment"); });
      $("#studySyllabus").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/study_materials.php?tab=Syllabus"); });
      $("#studyOther").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/study_materials.php?tab=Other_Download"); });

      // Operations routing
      $("#manageTimetable").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/timetable.php"); });
      $("#idCards").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/id_cards.php"); });
      $("#docTemplates").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/templates.php"); });
      $("#qrScanner").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/qr_scanner.php"); });
      $("#hostelManager").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hostels.php"); });
      $("#passManager").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hall_passes.php"); });
      $("#behaviorLog").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/behavior.php"); });
      $("#transport").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/transport.php"); });
      $("#inventory").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/inventory.php"); });
      $("#physicalLibrary").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/physical_library.php"); });

      // HR routing
      $("#hrStaffDirectory").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hr_staff_directory.php"); });
      $("#hrAttendance").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hr_attendance.php"); });
      $("#hrAttendanceReport").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hr_attendance_report.php"); });
      $("#hrPayroll").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hr_payroll.php"); });
      $("#hrPayrollReport").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/hr_payroll_report.php"); });

      $("#classes").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/classes.php"); });
      $("#promoteStudents").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/promote_students.php"); });

      $("#staffRecord").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/staff.php"); });

      $("#broadcast").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/broadcast.php"); });

      $("#createBlog").on("click", e => {
            e.preventDefault();
            loadPageWithCallback(BASE_URL + "admin/pages/blog.php", function () {
                  if (typeof window.initBlogEditor === "function") {
                        window.initBlogEditor();
                  }
            });
      });

      $("#viewLogs").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/logs.php"); });
      $("#schoolSettings").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/school_settings.php"); });
      $("#announcements").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/announcements.php"); });
      $("#events").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/events.php"); });
      $("#adminLibrary").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/library.php"); });

      $("#createExam").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/create-exam.php"); });

      $("#viewExam").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/view-exam.php"); });


      // cards Ajax (Admin Page)

      $(document).on("click", ".ajax-card", function () {

            const url = $(this).data("url");
            if (!url) return;

            $("#mainContent")
                  .html(`
            <div class="animate-pulse space-y-4 p-6">
                <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                <div class="h-4 bg-gray-200 rounded w-full"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>
        `)
                  .fadeIn(100);

            $("#mainContent").load(url, function () {
                  $(this).hide().fadeIn(150);
            });

            initSwiper();
      });


      // Notification toggler
      $('#notification_toggler').on('click', function (e) {
            e.stopPropagation();
            closeAccountMenu();

            $('#notification_screen')
                  .removeClass('hidden opacity-0 translate-x-[-50%] pointer-events-none')
                  .addClass('opacity-100 translate-x-0');

            // Bind mark-as-read handlers when panel opens
            bindMarkAsReadHandlers();
      });

      function closeNotifications() {
            $('#notification_screen')
                  .addClass('opacity-0 translate-x-[-50%] pointer-events-none')
                  .removeClass('opacity-100 translate-x-0')
                  .one('transitionend', function () {
                        if (!$(this).hasClass('opacity-100')) $(this).addClass('hidden');
                  });
      }

      $('#notification_closeBtn').on('click', function (e) {
            e.stopPropagation();
            closeNotifications();
      });

      $('#notification_area').on('click', function (e) {
            e.stopPropagation();
      });

      $(document).on('click', function () {
            $('#notification_screen')
                  .addClass('opacity-0 translate-x-[-50%] pointer-events-none')
                  .removeClass('opacity-100 translate-x-0')
                  .one('transitionend', function () {
                        if (!$(this).hasClass('opacity-100')) $(this).addClass('hidden');
                  });
      });
      //---------Notification toggler ends here------------//
      //---------------------------------------------------//

      // Mark as read script
      function bindMarkAsReadHandlers() { // updated handler
            const ping = $('#unread_ping');
            const counter = $('#unread_count');

            // Use event delegation for better reliability with dynamic content
            $('#notification_area').off('click.markread', '.mark-read-btn').on('click.markread', '.mark-read-btn', function (e) {
                  e.stopPropagation();

                  const $btn = $(this);
                  const id = $btn.data('id');
                  const $container = $btn.closest('.notification');

                  if (!id) return;

                  // Visual feedback: loading state
                  const originalHtml = $btn.html();
                  $btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);

                        $.ajax({
                              url: BASE_URL + 'admin/auth/mark_read.php',
                              type: 'POST',
                              data: { id: id },
                              dataType: 'json',
                              success: function (data) {
                                    if (data.status === 'success') {

                                          // Match style from notification.php
                                          $btn.replaceWith(`
                                          <span class="flex items-center gap-1 text-gray-400 text-[10px] font-semibold uppercase tracking-widest animate-fadeIn">
                                                <i class="bx bx-check-circle text-sm text-green-500"></i> Viewed
                                          </span>
                                    `);

                                          if ($container.length) {
                                                $container.css('opacity', '0.6').addClass('grayscale-[0.5]');
                                          }

                                          if (counter.length) {
                                                let count = parseInt(counter.text(), 10) || 0;
                                                count = Math.max(0, count - 1);
                                                counter.text(count);

                                          if (count === 0) {
                                                ping.fadeOut();
                                                counter.fadeOut();
                                          }
                                    }

                                          if (window.triggerHaptic) window.triggerHaptic(20);

                                    } else {
                                          $btn.html(originalHtml).prop('disabled', false);
                                          if (window.showToast) window.showToast(data.message || 'Failed to mark as read', 'error');
                                    }
                              },
                              error: function (xhr, status, error) {
                              $btn.html(originalHtml).prop('disabled', false);
                              console.error('AJAX error:', error);
                              if (window.showToast) window.showToast('Network error: ' + error, 'error');
                        }
                  });
            });
      }
      // calling the function
      bindMarkAsReadHandlers();

      // ── Fullscreen Toggle Logic ──────────────────────────────────────────
      window.enterAppMode = function() {
            const doc = document.documentElement;
            if (doc.requestFullscreen) {
                  doc.requestFullscreen();
            } else if (doc.webkitRequestFullscreen) {
                  doc.webkitRequestFullscreen();
            } else if (doc.msRequestFullscreen) {
                  doc.msRequestFullscreen();
            }
      };

      const fsBtn = $('#fullscreenToggler');

      function toggleFullscreen() {
            if (!document.fullscreenElement) {
                  window.enterAppMode();
            } else {
                  if (document.exitFullscreen) {
                        document.exitFullscreen();
                  }
            }
      }

      fsBtn.on('click', toggleFullscreen);

      // Detect fullscreen change to update button UI
      $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
            const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
            
            if (isFs) {
                  fsBtn.html('<i class="bx bx-fullscreen-exit text-xl"></i>');
                  fsBtn.removeClass('bg-gray-100 text-gray-700').addClass('bg-red-50 text-red-600 border border-red-100');
            } else {
                  fsBtn.html('<i class="bx bx-fullscreen text-xl"></i>');
                  fsBtn.removeClass('bg-red-50 text-red-600 border-red-100').addClass('bg-gray-100 text-gray-700');
            }
            
            if (fsBtn[0] && fsBtn[0]._tippy) {
                  fsBtn[0]._tippy.setContent(isFs ? 'Exit Full Screen' : 'View in App Mode');
            }
      });

      // ── Premium Tooltips Initialization ─────────────────────────────────────
      function initTooltips() {
            // Disable tooltips for mobile/touch devices
            if (window.innerWidth < 768) return;

            if (typeof tippy !== 'function') return;

            // Destroy any existing Tippy instances before re-initialising
            // to prevent stacked instances from multiple ajaxComplete calls
            document.querySelectorAll('[data-tippy-content]').forEach(el => {
                  if (el._tippy) el._tippy.destroy();
            });

            // General tooltips (not sidebar items)
            tippy('[data-tippy-content]:not(.sidebar .li)', {
                  animation: 'shift-away',
                  arrow: true,
                  theme: 'material',
                  delay: [100, 50],
                  allowHTML: true,
            });

            // Sidebar specific tooltips
            tippy('.sidebar .li[data-tippy-content]', {
                  animation: 'shift-away',
                  arrow: true,
                  theme: 'material',
                  placement: 'right',
                  offset: [0, 20],
                  delay: [100, 50],
                  allowHTML: true,
            });
      }

      // Initial call on page load
      initTooltips();

      // Re-init after AJAX — but only on elements inside #mainContent
      // to avoid re-destroying the already-stable sidebar/navbar tooltips
      $(document).ajaxComplete(function() {
            initTooltips();
      });

      // greetings Modal script
      $("#greetingsModal").on('click', function(e) {
            if (e.target === this) {
                  $(this).addClass("hidden");
            }
      });

      // ── Pull-to-Refresh Implementation ──────────────────────────────────
      let touchStart = 0;
      let touchMove = 0;
      const ptrThreshold = 150; // Increased from 80 to 150 to make it less sensitive
      
      // Inject PWA Pull to Refresh container
      if (!$('#ptr-container').length) {
            $('body').prepend(`
                  <div id="ptr-container">
                        <div id="ptr-loader">
                              <i class="bx bxs-loader-dots"></i>
                        </div>
                  </div>
            `);
      }

      const ptrContainer = $('#ptr-container');
      const ptrLoader = $('#ptr-loader');
      const ptrIcon = ptrLoader.find('i');

      $(window).on('touchstart', function(e) {
            // Only allow pull-to-refresh if:
            // 1. We are at the very top of the window
            // 2. We are NOT inside a scrollable element that is currently scrolled down (like the chat)
            const $scrollable = $(e.target).closest('.overflow-y-auto');
            const isScrolledDown = $scrollable.length && $scrollable.scrollTop() > 0;

            if ($(window).scrollTop() === 0 && !isScrolledDown) {
                  touchStart = e.originalEvent.touches[0].pageY;
            } else {
                  touchStart = 0; // Reset
            }
      });

      $(window).on('touchmove', function(e) {
            if ($(window).scrollTop() === 0 && touchStart > 0) {
                  touchMove = e.originalEvent.touches[0].pageY - touchStart;
                  
                  if (touchMove > 0) {
                        // Limit the visual pull distance
                        const pullDist = Math.min(touchMove * 0.35, ptrThreshold + 10);
                        ptrContainer.css('height', pullDist + 'px');
                        
                        // Scale up the loader
                        const scale = Math.min(pullDist / ptrThreshold, 1);
                        ptrLoader.css('transform', `scale(${scale})`);
                        
                        // Rotate icon based on pull
                        ptrIcon.css('transform', `rotate(${touchMove * 2}deg)`);
                  }
            }
      });

      $(window).on('touchend', function() {
            if (touchMove > ptrThreshold) {
                  // Trigger Refresh
                  ptrLoader.addClass('ptr-spinning');
                  ptrContainer.css('height', ptrThreshold + 'px');
                  
                  // Simulate a brief loading state before reload
                  setTimeout(() => {
                        ptrLoader.removeClass('ptr-spinning').addClass('ptr-success');
                        ptrIcon.attr('class', 'bx bx-check').css('transform', 'rotate(0deg)');
                        
                        setTimeout(() => {
                              // SOFT REFRESH: Instead of location.reload() which resets the browser UI,
                              // we just re-load the main content or go back to home.
                              if ($('#chatMessages').length) {
                                    // If in chat, just refresh messages
                                    if (typeof window.refreshChat === 'function') window.refreshChat();
                              } else {
                                    // Otherwise, reload the current view seamlessly
                                    window.goHome();
                              }

                              ptrLoader.removeClass('ptr-spinning ptr-success').css('transform', 'scale(0)');
                              ptrContainer.css('height', '0');
                              ptrIcon.attr('class', 'bx bxs-loader-dots').css('transform', 'rotate(0deg)');
                        }, 500);
                  }, 800);
            } else {
                  // Snap back
                  ptrContainer.css('height', '0');
                  ptrLoader.css('transform', 'scale(0)');
            }

            touchStart = 0;
            touchMove = 0;
      });

      // ── Table Toolkit: Search + Filter + CSV Download ──────────────────────
      // Usage: window.initTableToolkit({ ... })
      // Replaces the old initTableSearch — backward compatible via alias.
      //
      // Config object:
      //   searchId   : ID of the search input
      //   tableId    : ID of the <table> element (or tbody if no table wrapper)
      //   bodyId     : ID of the <tbody> (optional, defaults to first tbody in tableId)
      //   filterBtnId: ID of the Filter toggle button (optional)
      //   csvBtnId   : ID of the Download CSV button (optional)
      //   filters    : Array of { col: 0-indexed column#, label: "Label" } (optional)
      //   csvName    : CSV filename without extension (optional, default "export")
      //   itemSelector: what to search within (default "tr")
      //   countBadgeId: ID of an element to show result count (optional)
      //   noResultsMsg: message when nothing matches (optional)
      // ────────────────────────────────────────────────────────────────────────

      window.initTableToolkit = function (cfg) {
            const defaults = {
                  searchId: '',
                  tableId: '',
                  bodyId: '',
                  filterBtnId: '',
                  csvBtnId: '',
                  filters: [],       // e.g. [{col:2, label:'Class'}, {col:4, label:'Status'}]
                  csvName: 'export',
                  itemSelector: 'tr',
                  countBadgeId: '',
                  noResultsMsg: 'No matching records found.'
            };
            const c = Object.assign({}, defaults, cfg);

            const $search = c.searchId ? $(`#${c.searchId}`) : $();
            const $table = c.tableId ? $(`#${c.tableId}`) : $();
            const $tbody = c.bodyId ? $(`#${c.bodyId}`) : $table.find('tbody').first();
            const $filterBtn = c.filterBtnId ? $(`#${c.filterBtnId}`) : $();
            const $csvBtn = c.csvBtnId ? $(`#${c.csvBtnId}`) : $();
            const $countBadge = c.countBadgeId ? $(`#${c.countBadgeId}`) : $();

            if (!$tbody.length) return;

            // ── Store active filter values ────────────────────────────────
            let activeFilters = {}; // { colIndex: "value" }

            // ── Build filter panel (injected after filter button) ─────────
            let $filterPanel = $();
            if ($filterBtn.length && c.filters.length > 0) {
                  const panelId = c.filterBtnId + '_panel';
                  const panelHtml = `
                  <div id="${panelId}" class="hidden absolute right-0 top-full mt-2 z-50 bg-white rounded-2xl shadow-2xl shadow-gray-200/60 border border-gray-100 p-4 min-w-[240px] animate-fadeIn">
                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Filter By</p>
                        <div class="space-y-3" id="${panelId}_selects"></div>
                        <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                              <button type="button" id="${panelId}_clear" class="flex-1 px-3 py-2 text-xs font-bold text-gray-500 bg-gray-50 rounded-xl hover:bg-gray-100 transition cursor-pointer">Clear All</button>
                              <button type="button" id="${panelId}_apply" class="flex-1 px-3 py-2 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition cursor-pointer">Apply</button>
                        </div>
                  </div>`;

                  // Insert panel right after the filter button (needs relative parent)
                  $filterBtn.css('position', 'relative').append(panelHtml);
                  $filterPanel = $(`#${panelId}`);

                  // Prevent clicks inside the panel from closing it
                  $filterPanel.on('click', function (e) {
                        e.stopPropagation();
                  });

                  // Toggle panel
                  $filterBtn.on('click', function (e) {
                        e.stopPropagation();
                        $filterPanel.toggleClass('hidden');
                        if (!$filterPanel.hasClass('hidden')) {
                              buildFilterSelects();
                        }
                  });

                  // Close on outside click
                  $(document).on('click', function (e) {
                        if (!$(e.target).closest($filterBtn).length) {
                              $filterPanel.addClass('hidden');
                        }
                  });

                  // Apply button
                  $(`#${panelId}_apply`).on('click', function () {
                        activeFilters = {};
                        c.filters.forEach(f => {
                              const val = $(`#${panelId}_sel_${f.col}`).val();
                              if (val) activeFilters[f.col] = val;
                        });
                        applyAll();
                        $filterPanel.addClass('hidden');
                        // Show active filter count on button
                        const count = Object.keys(activeFilters).length;
                        $filterBtn.find('.filter-count').remove();
                        if (count > 0) {
                              $filterBtn.append(`<span class="filter-count absolute -top-1.5 -right-1.5 size-5 bg-blue-600 text-white text-[9px] font-semibold rounded-full flex items-center justify-center">${count}</span>`);
                        }
                  });

                  // Clear button
                  $(`#${panelId}_clear`).on('click', function () {
                        activeFilters = {};
                        c.filters.forEach(f => $(`#${panelId}_sel_${f.col}`).val(''));
                        applyAll();
                        $filterPanel.addClass('hidden');
                        $filterBtn.find('.filter-count').remove();
                  });
            }

            function buildFilterSelects() {
                  if (!c.filters.length) return;
                  const panelId = c.filterBtnId + '_panel';
                  const $selectContainer = $(`#${panelId}_selects`);
                  $selectContainer.empty();

                  c.filters.forEach(f => {
                        // Collect unique values from that column
                        const values = new Set();
                        $tbody.find(c.itemSelector).each(function () {
                              const $cells = $(this).find('td');
                              if ($cells.length > f.col) {
                                    const txt = $cells.eq(f.col).text().trim();
                                    if (txt) values.add(txt);
                              }
                        });

                        const sorted = [...values].sort();
                        let optionsHtml = `<option value="">All ${f.label}</option>`;
                        sorted.forEach(v => {
                              const sel = activeFilters[f.col] === v ? 'selected' : '';
                              optionsHtml += `<option value="${v}" ${sel}>${v}</option>`;
                        });

                        $selectContainer.append(`
                              <div>
                                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">${f.label}</label>
                                    <select id="${panelId}_sel_${f.col}" class="w-full mt-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white transition">
                                          ${optionsHtml}
                                    </select>
                              </div>
                        `);
                  });
            }

            // ── Core filter + search logic ────────────────────────────────
            function applyAll() {
                  const query = $search.length ? $search.val().toLowerCase().trim() : '';
                  let visible = 0;
                  const $rows = $tbody.find(c.itemSelector);

                  $rows.each(function () {
                        const $row = $(this);
                        const text = $row.text().toLowerCase();

                        // Text search
                        let matchSearch = !query || text.includes(query);

                        // Column filters
                        let matchFilter = true;
                        Object.keys(activeFilters).forEach(col => {
                              const filterVal = activeFilters[col].toLowerCase();
                              const $cells = $row.find('td');
                              if ($cells.length > col) {
                                    const cellText = $cells.eq(parseInt(col)).text().trim().toLowerCase();
                                    if (!cellText.includes(filterVal)) {
                                          matchFilter = false;
                                    }
                              }
                        });

                        const show = matchSearch && matchFilter;
                        $row.toggle(show);
                        if (show) visible++;
                  });

                  // Update count badge
                  if ($countBadge.length) {
                        $countBadge.text(`${visible} result${visible !== 1 ? 's' : ''}`);
                  }

                  // No results message
                  $tbody.find('.tt-no-results').remove();
                  if (visible === 0 && $rows.length > 0) {
                        const colspan = $rows.first().find('td').length || 5;
                        $tbody.append(`
                              <tr class="tt-no-results">
                                    <td colspan="${colspan}" class="px-6 py-16 text-center">
                                          <div class="flex flex-col items-center gap-3">
                                                <div class="size-14 rounded-2xl bg-gray-50 flex items-center justify-center">
                                                      <i class="bx bx-search-alt text-2xl text-gray-300"></i>
                                                </div>
                                                <p class="text-sm text-gray-400 font-medium">${c.noResultsMsg}</p>
                                          </div>
                                    </td>
                              </tr>
                        `);
                  }
            }

            // ── Bind search input ─────────────────────────────────────────
            if ($search.length) {
                  $search.on('input', function () {
                        applyAll();
                  });
            }

            // ── CSV Download ──────────────────────────────────────────────
            if ($csvBtn.length) {
                  $csvBtn.on('click', function () {
                        let csvContent = '';

                        // Headers from thead
                        const $thead = $table.length ? $table.find('thead') : $tbody.prev('thead');
                        if ($thead.length) {
                              const headers = [];
                              $thead.find('th').each(function () {
                                    const txt = $(this).text().trim();
                                    if (txt.toLowerCase() !== 'actions') {
                                          headers.push('"' + txt.replace(/"/g, '""') + '"');
                                    }
                              });
                              csvContent += headers.join(',') + '\n';
                        }

                        // Visible rows only
                        $tbody.find(c.itemSelector + ':visible').each(function () {
                              const cells = [];
                              const $tds = $(this).find('td');
                              $tds.each(function (i) {
                                    // Skip last column if it has actions
                                    if (i === $tds.length - 1) {
                                          const hasBtn = $(this).find('button, a, .delete-btn, .edit-btn').length > 0;
                                          if (hasBtn) return;
                                    }
                                    const txt = $(this).text().trim().replace(/\s+/g, ' ');
                                    cells.push('"' + txt.replace(/"/g, '""') + '"');
                              });
                              csvContent += cells.join(',') + '\n';
                        });

                        // Trigger download
                        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        const timestamp = new Date().toISOString().slice(0, 10);
                        link.href = url;
                        link.download = `${c.csvName}_${timestamp}.csv`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);

                        if (window.showToast) window.showToast('CSV downloaded!', 'success');
                  });
            }
      };

      // ── Backward-compatible alias for old pages ─────────────────────────
      window.initTableSearch = function (inputId, containerId, itemSelector = 'tr') {
            window.initTableToolkit({
                  searchId: inputId,
                  bodyId: containerId,
                  itemSelector: itemSelector
            });
      };

      // ── PWA Service Worker Registration ──────────────────────────────────
      if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                  navigator.serviceWorker.register(BASE_URL + 'sw.js')
                        .then(reg => console.log('Service Worker registered:', reg.scope))
                        .catch(err => console.log('Service Worker registration failed:', err));
            });
      }

      // ── Haptic Feedback System ──────────────────────────────────────────
      window.triggerHaptic = function(duration = 15) {
            if ("vibrate" in navigator) {
                  navigator.vibrate(duration);
            }
      };

      // Attach haptics to all interactive elements
      $(document).on('click', 'button, a, .li, .cursor-pointer', function() {
            window.triggerHaptic(15);
      });

      // ── Background Chat Notification Poll ───────────────────────────────
      // Only runs for students (sideChat exists). Stops when user opens chat page.
      (function() {
            const $dot = $('#chatNotifDot');
            if (!$dot.length) return; // not a student / sidebar not present

            let bgPollTimer;

            function showDot() {
                  $dot.removeClass('hidden');
            }
            function hideDot() {
                  $dot.addClass('hidden');
            }

            // Expose globally so chat.php can hide it on open
            window._hideChatDot = hideDot;

            function bgPoll() {
                  // Don't poll if the chat page is currently open (it handles its own poll)
                  if (typeof window._stopChatPoll === 'function' && document.getElementById('chatMessages')) return;

                  const lastSeen = window._chatLastSeenId || 0;

                  $.ajax({
                        url: BASE_URL + 'student/auth/fetch_messages.php',
                        type: 'GET',
                        data: { last_id: lastSeen },
                        dataType: 'json',
                        success: function(res) {
                              if (!res.success) return;
                              let hasNew = false;
                              res.messages.forEach(function(msg) {
                                    if (msg.sender_id != res.my_id) hasNew = true;
                                    // Advance last seen so we don't re-alert same message
                                    window._chatLastSeenId = Math.max(window._chatLastSeenId || 0, parseInt(msg.id));
                              });
                              if (hasNew) showDot();
                        }
                  });
            }

            // Poll every 8s in background (less aggressive than chat page's 3s)
            bgPollTimer = setInterval(bgPoll, 8000);

            // Clear dot and stop duplicating when user opens chat
            $('#sideChat').on('click', function() {
                  hideDot();
            });
      })();
      // ── End Background Chat Notification Poll ───────────────────────────

});

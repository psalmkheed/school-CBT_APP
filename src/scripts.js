// ── Global Toast Notification ──────────────────────────────────────────
window.showToast = function(message, type = 'success') {
      let toast = document.getElementById('global-toast');
      
      if (!toast) {
            toast = document.createElement('div');
            toast.id = 'global-toast';
            toast.className = 'hidden fixed top-5 right-5 z-[999999] min-w-[280px] max-w-sm px-4 py-2 rounded-xl shadow-2xl text-white text-sm font-bold flex items-center gap-3 fade-in-left';
            toast.innerHTML = '<span id="global-toast-icon" class="text-xl"></span><span id="global-toast-msg"></span>';
            document.body.appendChild(toast);
      }

      const icon = document.getElementById('global-toast-icon');
      const msg = document.getElementById('global-toast-msg');

      msg.textContent = message;
      icon.textContent = type === 'success' ? '✅' : '❌';
      
      // Reset classes and show
      toast.classList.remove('hidden', 'bg-green-600', 'bg-red-600');
      toast.classList.add(type === 'success' ? 'bg-green-600' : 'bg-red-600');

      // Auto hide
      clearTimeout(toast._timer);
      toast._timer = setTimeout(() => {
            toast.classList.add('hidden');
      }, 4000);
};

// Global legacy alias
window.showAlert = function(type, message) {
      window.showToast(message, type);
};

$(function () {
      // Base URL for the application
      const BASE_URL = '/school_app/';

      // swiper
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
            const startTime = Date.now();
            $("#mainContent").fadeOut(150, function() {
                  $(this).html(getSkeletonHTML()).show();
                  $(this).load(url, function() {
                        const elapsed = Date.now() - startTime;
                        const delay = Math.max(0, 600 - elapsed);
                        
                        setTimeout(() => {
                              $("#mainContent").hide().fadeIn(250);
                              window.scrollTo({ top: 0, behavior: 'smooth' });
                        }, delay);
                  });
            });
      };

      window.loadPageWithCallback = (url, callback) => {
            const startTime = Date.now();
            $("#mainContent").fadeOut(150, function() {
                  $(this).html(getSkeletonHTML()).show();
                  $(this).load(url, function() {
                        const elapsed = Date.now() - startTime;
                        const delay = Math.max(0, 600 - elapsed);

                        setTimeout(() => {
                              $("#mainContent").hide().fadeIn(250);
                              if(callback) callback();
                        }, delay);
                  });
            });
      };

      window.goHome = function() {
            if (originalContent) {
                  $("#mainContent").fadeOut(200, function () {
                        $(this).html(originalContent).fadeIn(300);
                  });
                  // Also reset active class in sidebar if possible
                  $(".li").removeClass("active");
                  $("#homepage, #sideHome").addClass("active");
            } else {
                  window.location.reload();
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
      $("#sideBarToggler").on("click", e => {
            e.stopPropagation();
            if ($("#sideBar").hasClass("sidebar-open")) {
                  closeSidebar();
            } else {
                  openSidebar();
            }
      });

      // Close button inside sidebar (mobile)
      $("#sideBar").on("click", "#sidebarCloseBtn", (e) => {
            e.stopPropagation();
            closeSidebar();
      });

      // Overlay click closes sidebar
      $(document).on("click", "#sidebarOverlay", () => closeSidebar());

      // Keep sidebar from closing when clicking inside it
      $("#sideBar").on("click", (e) => {
            e.stopPropagation();
            closeAccountMenu();
      });

      // Auto-close sidebar on mobile when a nav item is clicked
      $(".sidebar .li, .sidebar li").on("click", function () {
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
      $("#sideChat").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/chat.php"); });
      $("#sideTest").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/test.php"); });
      $("#sideStudy").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "pages/study.php"); });
      $("#sideStudents").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/students.php"); });
      $("#sideStaffExams").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/exams.php"); });
      $("#sideResults").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "staff/pages/results.php"); });

      // Admin sidebar dynamic page loading
      $("#homepage").on("click", e => { e.preventDefault(); loadPage("index.php #mainContent > *"); });

      $("#createAccount").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/auth/register.php"); });

      $("#usersRecord").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/users.php"); });
      
      $("#classes").on("click", e => { e.preventDefault(); loadPage(BASE_URL + "admin/pages/classes.php"); });

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
                  .removeClass('opacity-0 translate-x-[-50%] pointer-events-none')
                  .addClass('opacity-100 translate-x-0');

            // Bind mark-as-read handlers when panel opens
            bindMarkAsReadHandlers();
      });

      function closeNotifications() {
            $('#notification_screen')
                  .addClass('opacity-0 translate-x-[-50%] pointer-events-none')
                  .removeClass('opacity-100 translate-x-0');
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
                  .removeClass('opacity-100 translate-x-0');
      });
      //---------Notification toggler ends here------------//
      //---------------------------------------------------//

      // Mark as read script
      function bindMarkAsReadHandlers() {
            const ping = $('#unread_ping');
            const counter = $('#unread_count');

            $('.mark-read-btn').each(function () {
                  const btn = $(this);

                  btn.off('click.markread').on('click.markread', function (e) {
                        e.stopPropagation();

                        const btn = $(this);
                        const id = btn.data('id');

                        if (!id) {
                              alert('Notification ID missing');
                              return;
                        }

                        $.ajax({
                              url: '/school_app/admin/auth/mark_read.php',
                              type: 'POST',
                              data: { id },
                              dataType: 'json',
                              success: function (data) {
                                    if (data.status === 'success') {

                                          btn.html(`
                            <span class="flex items-center gap-1 text-gray-500 text-sm"><i class="bx-check-circle text-lg"></i> <p class="m-0 p-0">Read</p></span>
                        `)
                                                .prop('disabled', true)
                                                .removeClass('bg-green-500 hover:bg-green-600')
                                                .addClass('cursor-default');

                                          btn.closest('.notification').css('opacity', '0.6');

                                          if (counter.length) {
                                                let count = parseInt(counter.text(), 10) || 0;
                                                count = Math.max(0, count - 1);
                                                counter.text(count);

                                                if (count === 0) {
                                                      ping.hide();
                                                      counter.hide();
                                                } else {
                                                      ping.show();
                                                      counter.show();
                                                }
                                          }

                                    } else {
                                          alert(data.message || 'Failed to mark as read');
                                    }
                              },
                              error: function (xhr, status, error) {
                                    console.error('AJAX error:', error);
                                    console.error('Response:', xhr.responseText);
                                    alert('Network error: ' + error);
                              }
                        });
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
                  fsBtn.html('<i class="bx bx-exit-fullscreen text-lg"></i> <span>Exit Fullscreen</span>');
                  fsBtn.removeClass('bg-gray-100 text-gray-700').addClass('bg-red-50 text-red-600 border border-red-100');
            } else {
                  fsBtn.html('<i class="bx bx-fullscreen text-lg"></i> <span>Go Fullscreen</span>');
                  fsBtn.removeClass('bg-red-50 text-red-600 border-red-100').addClass('bg-gray-100 text-gray-700');
            }
            
            if (fsBtn[0] && fsBtn[0]._tippy) {
                  fsBtn[0]._tippy.setContent(isFs ? 'Exit Full Screen' : 'View in App Mode');
            }
      });

      // ── Premium Tooltips Initialization ─────────────────────────────────────
      function initTooltips() {
            // Disable tooltips for mobile/touch devices (breakpoint: 768px)
            if (window.innerWidth < 768) {
                  // If they were already initialized, we might want to destroy them
                  // but for simplicity, we just won't init them on small screens.
                  return;
            }

            if (typeof tippy === 'function') {
                  // General tooltips
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
                        placement: 'right', // Move to the right of the li
                        offset: [0, 20],   // Give it some breathing room from the text
                        delay: [100, 50],
                        allowHTML: true,
                  });
            }
      }

      // Initial call
      initTooltips();

      // Also re-init tooltips after AJAX content is loaded
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
      const ptrThreshold = 80;
      
      // Inject PWA Pull to Refresh container
      if (!$('#ptr-container').length) {
            $('body').prepend(`
                  <div id="ptr-container">
                        <div id="ptr-loader">
                              <i class="bx bx-loader-alt"></i>
                        </div>
                  </div>
            `);
      }

      const ptrContainer = $('#ptr-container');
      const ptrLoader = $('#ptr-loader');
      const ptrIcon = ptrLoader.find('i');

      $(window).on('touchstart', function(e) {
            if ($(window).scrollTop() === 0) {
                  touchStart = e.originalEvent.touches[0].pageY;
            }
      });

      $(window).on('touchmove', function(e) {
            if ($(window).scrollTop() === 0 && touchStart > 0) {
                  touchMove = e.originalEvent.touches[0].pageY - touchStart;
                  
                  if (touchMove > 0) {
                        // Limit the visual pull distance
                        const pullDist = Math.min(touchMove * 0.4, ptrThreshold + 20);
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
                              window.location.reload();
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

      // ── PWA Service Worker Registration ──────────────────────────────────
      if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                  navigator.serviceWorker.register('/school_app/sw.js')
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
});

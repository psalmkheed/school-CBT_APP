$(function () {
      // Base URL for the application
      const BASE_URL = '/school_app/';


      // Create Session script
      $(document).on('click', '#createSession', function () {
            const modal = $('#sessionModal');

            modal
                  .removeClass('hidden fadeOut')
                  .addClass('fadeIn');

            $('body').addClass('overflow-hidden');
      });

      // Session Form Modal
      // close Modal only if clicking on the background overlay
      $(document).on('click', '#sessionModal', function (e) {
            if (e.target !== this) return; // Only trigger if clicking the overlay itself

            const modal = $(this);
            modal
                  .removeClass('fadeIn')
                  .addClass('fadeOut')
                  .one('animationend', function () {
                        modal.addClass('hidden').removeClass('fadeOut');
                  });

            $('body').removeClass('overflow-hidden');
      });

      // prevent close on form click
      $(document).on('click', '#sessionForm', function (e) {
            e.stopPropagation();
      });

      // close on Esc key
      $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                  $('#sessionModal').trigger('click');
            }
      });

      // Handle Add Term Button (Delegated for robustness)
      $(document).on('click', '#add-term-btn', function() {
            const container = $('#terms-container');
            const termCount = container.find('.term-block').length;

            if (termCount >= 3) {
                  showAlert('error', 'Maximum of 3 terms per session allowed');
                  return;
            }

            const nextIndex = termCount; // 0-based for the array index
            const termBlock = `
                  <div class="term-block relative bg-white p-5 rounded-2xl border-2 border-gray-100 transition-all duration-300 hover:border-green-100 group fadeIn">
                        <button type="button" class="remove-term absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm hover:bg-red-600 transition-colors z-10">
                              <i class="bx bx-x text-sm"></i>
                        </button>
                        <div class="flex items-center gap-2 mb-4">
                              <span class="w-6 h-6 rounded-full bg-green-600 text-white text-[10px] flex items-center justify-center font-bold">${termCount + 1}</span>
                              <h4 class="text-sm font-bold text-gray-700">Additional Academic Term</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div class="md:col-span-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Term Name</label>
                                    <select name="terms[${nextIndex}][term]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition bg-white">
                                          <option value="First Term">First Term</option>
                                          <option value="Second Term">Second Term</option>
                                          <option value="Third Term" selected>Third Term</option>
                                    </select>
                              </div>
                              <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Starts On</label>
                                    <input type="date" name="terms[${nextIndex}][start_date]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                              </div>
                              <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Ends On</label>
                                    <input type="date" name="terms[${nextIndex}][end_date]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                              </div>
                        </div>
                  </div>
            `;

            container.append(termBlock);
      });

      // Handle Remove Term
      $(document).on('click', '.remove-term', function() {
            $(this).closest('.term-block').remove();
            reindexTerms();
      });

      function reindexTerms() {
            $('.term-block').each(function(index) {
                  $(this).find('span.bg-green-600').text(index + 1);
                  $(this).find('select, input').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                              const newName = name.replace(/terms\[\d+\]/, `terms[${index}]`);
                              $(this).attr('name', newName);
                        }
                  });
            });
      }

      // session Form submission
      function sessionForm() {

            function checkDuplicateSession() {
                  let sessionExists = false;
                  const session = $('input[name="session"]').val();
                  const term = $('select[name="term"]').val();

                  if (!session || !term) return;

                  $.ajax({
                        url: '/school_app/admin/auth/check_session.php',
                        method: 'POST',
                        data: { session, term },
                        dataType: 'json',
                        success: function (res) {
                              if (res.exists) {
                                    sessionExists = true;
                                    showAlert('error', 'This session already exists for the selected term');
                                    $("#sessionBtn").prop("disabled", true);
                              } else {
                                    sessionExists = false;
                                    $("#sessionBtn").prop("disabled", false);
                              }
                        }
                  });
            }

            // Trigger check when session or term changes
            $('input[name="session"], select[name="term"]').on('change keyup', checkDuplicateSession);


            const form = $('#sessionForm');

            form.off('submit').on('submit', function (e) {
                  e.preventDefault();

                  $.ajax({
                        url: '/school_app/admin/auth/sch_session.php',
                        method: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        beforeSend: function () {
                              $("#sessionBtn")
                                    .prop("disabled", true)
                                    .html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>`);
                        },
                        success: function (res) {
                              if (res.success === true) {
                                    showAlert('success', res.message);
                                    form[0].reset();
                                    // Refresh to update the global session indicators
                                    setTimeout(() => {
                                          location.reload();
                                    }, 1500);
                              } else {
                                    showAlert('error', res.message);
                              }
                        },
                        error: function (xhr) {
                              console.error(xhr.responseText);
                              showAlert('error', 'Network error. Try again.');
                        },
                        complete: function () {
                              $("#sessionBtn")
                                    .prop("disabled", false)
                                    .text("Save & Activate Session");
                        }
                  });
            });
      }
      // calling the function
      sessionForm();



      // add subject script ------------------------------------- //
      $('#addNewSubject').on('click', () => {
            $('#subjectModal').removeClass('hidden fadeOut').addClass('fadeIn');
      });

      $(document).on('click', '#subjectModal', function () {
            const modal = $(this);
            modal
                  .removeClass('fadeIn')
                  .addClass('fadeOut')
                  .one('animationend', function () {
                        modal.addClass('hidden').removeClass('fadeOut');
                  });
      });

      $('#add-subject-form').on('click', (e) => e.stopPropagation());


      // add subject AJAX function
      $('#add-subject-form').off('submit').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                  url: '/school_app/admin/auth/add_subject.php',
                  method: 'POST',
                  data: $(this).serialize(),
                  dataType: 'json',
                  beforeSend: function () {
                        $('#subjectBtn').prop('disabled', true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>
                        `);
                  },
                  success: function (data) {
                        if (data.status === 'success') {
                              showAlert('success', data.message);
                              $("#add-subject-form")[0].reset();
                        } else (
                              showAlert('error', data.message)
                        )
                  },
                  error: function () {
                        showAlert('error', 'Network error. Try again')
                  },
                  complete: function () {
                        $('#subjectBtn').prop('disabled', false).text('Add Subject');
                  }
            })
      })

      // -----------------------------------------------------------------------//

      // create new exam script
      $('#createNewExam').on('click', () => {
            $('#examModal').removeClass('hidden fadeOut').addClass('fadeIn');
      });

      $(document).on('click', '#examModal', function () {
            const modal = $(this);
            modal
                  .removeClass('fadeIn')
                  .addClass('fadeOut')
                  .one('animationend', function () {
                        modal.addClass('hidden').removeClass('fadeOut');
                  });
      });

      $('#create-exam-form').on('click', (e) => e.stopPropagation());


      // create exam AJAX function
      $('#create-exam-form').off('submit').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                  url: '/school_app/admin/auth/create_exam.php',
                  method: 'POST',
                  data: $(this).serialize(),
                  dataType: 'json',
                  beforeSend: function () {
                        $('#create_exam_btn').prop('disabled', true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>
                        `);
                  },
                  success: function (data) {
                        if (data.status === 'success') {
                              showAlert('success', data.message);
                              $("#create-exam-form")[0].reset();
                        } else (
                              showAlert('error', data.message)
                        )
                  },
                  error: function () {
                        showAlert('error', 'Network error. Try again')
                  },
                  complete: function () {
                        $('#create_exam_btn').prop('disabled', false).text('Create Exam');
                  }
            })
      })

      // ----------------------------------------------------------------------- //
      // aJAX page load for Examination
      function loadPage(page = 1) {
            $.ajax({
                  url: "/school_app/admin/auth/fetch_exams.php",
                  method: "POST",
                  data: { page: page },
                  success: function (data) {
                        $("#examTable").html(data);
                        if (window.initTooltips) {
                              window.initTooltips();
                        }
                  }
            });
      }

      // load first page
      loadPage();

      // pagination click
      $(document).on("click", ".page-btn", function () {
            let page = $(this).data("page");
            loadPage(page);
      });

      // Delete Exam Handler
      $(document).on('click', '.delete-exam-btn', function () {
            const id = $(this).data('id');
            const row = $(this).closest('tr');

            Swal.fire({
                  title: 'Are you sure?',
                  text: "This will permanently delete the examination and all its questions!",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#d33',
                  cancelButtonColor: '#3085d6',
                  confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                  if (result.isConfirmed) {
                        $.ajax({
                              url: '/school_app/admin/auth/delete_exam.php',
                              method: 'POST',
                              data: { id: id },
                              dataType: 'json',
                              success: function (res) {
                                    if (res.success) {
                                          showAlert('success', res.message);
                                          row.fadeOut(400, function() { $(this).remove(); });
                                    } else {
                                          showAlert('error', res.message);
                                    }
                              },
                              error: function () {
                                    showAlert('error', 'Network error. Try again.');
                              }
                        });
                  }
            });
      });

      // Update Exam Status Handler (Publish)
      $(document).on('click', '.status-exam-btn', function () {
            const id = $(this).data('id');
            const currentStatus = $(this).data('status');
            let nextStatus = '';
            let confirmMsg = '';

            if (currentStatus === 'ready') {
                  nextStatus = 'published';
                  confirmMsg = 'Do you want to publish this examination? It will become visible to students.';
            } else if (currentStatus === 'published') {
                  nextStatus = 'ready';
                  confirmMsg = 'Do you want to unpublish this examination? It will be hidden from students.';
            } else if (currentStatus === 'set up') {
                  showAlert('info', 'Please notify the staff assigned to this exam to complete setting the questions.');
                  return;
            } else {
                  return;
            }

            Swal.fire({
                  title: 'Update Status?',
                  text: confirmMsg,
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#10b981',
                  cancelButtonColor: '#3085d6',
                  confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                  if (result.isConfirmed) {
                        $.ajax({
                              url: '/school_app/admin/auth/update_exam_status.php',
                              method: 'POST',
                              data: { id: id, status: nextStatus },
                              dataType: 'json',
                              success: function (res) {
                                    if (res.success) {
                                          showAlert('success', res.message);
                                          loadPage(); // Reload the table to reflect changes
                                    } else {
                                          showAlert('error', res.message);
                                    }
                              },
                              error: function () {
                                    showAlert('error', 'Network error. Try again.');
                              }
                        });
                  }
            });
      });


      // create new class script
      $('#create-class').on('click', () => {
            $('#classModal').removeClass('hidden fadeOut').addClass('fadeIn');
      });

      $('#create-class-form').on('click', (e) => e.stopPropagation()
      )

      $(document).on('click', '#classModal', function () {
            $('#classModal').removeClass('fadeIn').addClass('hidden fadeOut');

      });

      // create class AJAX function
      $('#create-class-form').off('submit').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                  url: '/school_app/admin/auth/create_class.php',
                  method: 'POST',
                  data: $(this).serialize(),
                  dataType: 'json',
                  beforeSend: function () {
                        $('#classBtn').prop('disabled', true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>
                        `);
                  },
                  success: function (data) {
                        if (data.status === 'success') {
                              showAlert('success', data.message);
                              $("#create-class-form")[0].reset();
                        } else (
                              showAlert('error', data.message)
                        )
                  },
                  error: function () {
                        showAlert('error', 'Network error. Try again')
                  },
                  complete: function () {
                        $('#classBtn').prop('disabled', false).text('Create class');
                  }
            })
      })

})
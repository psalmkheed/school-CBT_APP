<?php
require '../../connections/db.php';

// Fetch all classes
$class_stmt = $conn->prepare("SELECT * FROM class ORDER BY class ASC");
$class_stmt->execute();
$all_classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch all staff members to populate the dropdown
$staff_stmt = $conn->prepare("SELECT id, first_name, surname FROM users WHERE role = 'staff' ORDER BY first_name ASC");
$staff_stmt->execute();
$all_staff = $staff_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex flex-col md:flex-row items-center gap-4">
                  <div>
                        <h3 class="text-2xl font-bold text-gray-800">Classroom Management</h3>
                        <p class="text-sm text-gray-500">Assign teachers to classes and manage classroom organization.</p>
                  </div>
                  <div class="relative w-full md:w-64 group">
                        <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input type="text" id="classSearch" 
                              class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition shadow-sm"
                              placeholder="Search classes...">
                  </div>
            </div>
            <button
                  class="bg-green-600 text-white px-5 py-2.5 rounded-xl hover:bg-green-700 transition-all duration-300 cursor-pointer flex gap-2 items-center shadow-lg shadow-green-100 font-bold text-sm self-start md:self-center"
                  id="create-class">
                  <i class="bx bx-plus text-lg"></i>
                  Create New Class
            </button>
      </div>

      <!-- Classes Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($all_classes as $cls): ?>
                  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">
                        <div class="p-6">
                              <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                                          <i class="bx-door-open text-2xl"></i>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest">Active Class</span>
                              </div>

                              <h4 class="text-xl font-semibold text-gray-800 mb-1"><?= htmlspecialchars($cls->class) ?></h4>
                              
                              <div class="mb-6">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Assigned Class Teacher</label>
                                    <select 
                                          onchange="assignTeacher(<?= $cls->id ?>, this.value)"
                                          class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:bg-white transition-all appearance-none cursor-pointer">
                                          <option value="">-- No Teacher Assigned --</option>
                                          <?php foreach ($all_staff as $staff): ?>
                                                <option value="<?= $staff->id ?>" <?= ($cls->teacher_id == $staff->id) ? 'selected' : '' ?>>
                                                      <?= htmlspecialchars($staff->first_name . ' ' . $staff->surname) ?>
                                                </option>
                                          <?php endforeach; ?>
                                    </select>
                              </div>

                              <div class="mb-6">
                                    <label class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block mb-1.5">Promotion Path (Next Class)</label>
                                    <select 
                                          onchange="assignNextClass(<?= $cls->id ?>, this.value)"
                                          class="w-full bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-all appearance-none cursor-pointer">
                                          <option value="">-- Graduated / Leave School --</option>
                                          <?php foreach ($all_classes as $nextCls): ?>
                                                <?php if($nextCls->id !== $cls->id): ?>
                                                <option value="<?= $nextCls->id ?>" <?= ($cls->next_class_id == $nextCls->id) ? 'selected' : '' ?>>
                                                      <?= htmlspecialchars($nextCls->class) ?>
                                                </option>
                                                <?php endif; ?>
                                          <?php endforeach; ?>
                                    </select>
                              </div>

                              <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                                    <div class="flex items-center gap-2">
                                          <?php 
                                                // Count students in this class
                                                $count_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND class = :class");
                                                $count_stmt->execute([':class' => $cls->class]);
                                                $student_count = $count_stmt->fetchColumn();
                                          ?>
                                          <div class="flex -space-x-2">
                                                <div class="w-7 h-7 rounded-full bg-orange-100 border-2 border-white flex items-center justify-center text-orange-600">
                                                      <i class="bx-user text-xs"></i>
                                                </div>
                                          </div>
                                          <span class="text-xs font-bold text-gray-500"><?= $student_count ?> Students Enrolled</span>
                                    </div>
                                    <button 
                                          title="Manage subject teachers for this class"
                                          onclick="manageClassSubjects(<?= $cls->id ?>, '<?= htmlspecialchars($cls->class) ?>')"
                                          class="p-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-xl transition duration-300 font-semibold cursor-pointer text-sm flex items-center justify-center">
                                          <i class="bx bx-book-bookmark"></i>
                                    </button>
                               </div>
                        </div>
                  </div>
            <?php endforeach; ?>
      </div>
</div>

<!-- create class form modal  -->
<div class="hidden fixed inset-0 bg-black/90 flex items-center justify-center p-2 z-[99999] backdrop-blur-md"
      id="classModal">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden fadeIn">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                              <i class="bx-door-open text-emerald-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Create New Class</h3>
                  </div>
                  <button type="button" class="text-gray-400 hover:text-gray-600 transition cursor-pointer" onclick="document.getElementById('classModal').classList.add('hidden')">
                        <i class="bx-x text-2xl"></i>
                  </button>
            </div>

            <!-- Modal Body -->
            <form id="create-class-form" class="p-6 flex flex-col gap-5">
                  <div class="flex flex-col gap-1.5">
                        <label for="class-name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Class Name</label>
                        <input type="text" id="class-name" name="class-name"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition"
                              placeholder="E.g. JSS 1, SS 1...">
                  </div>
                  <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all duration-200 font-bold text-sm cursor-pointer shadow-lg shadow-emerald-100"
                        id="classBtn">Create Class</button>
            </form>
      </div>
</div>

<script>
// Handle Modal Toggle (if not already handled by modal.js)
$('#classSearch').on('input', function() {
    const q = $(this).val().toLowerCase();
    $('.grid > div').each(function() {
        const t = $(this).text().toLowerCase();
        $(this).toggle(t.includes(q));
    });
});

$('#create-class').on('click', function() {
    $('#classModal').removeClass('hidden fadeOut').addClass('fadeIn');
});

$('#classModal').on('click', function(e) {
    if (e.target === this) {
        $(this).addClass('fadeOut').removeClass('fadeIn');
        setTimeout(() => $(this).addClass('hidden'), 300);
    }
});

$('#create-class-form').on('click', (e) => e.stopPropagation());

// Handle Form Submission
$('#create-class-form').off('submit').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#classBtn');
    
    $.ajax({
        url: 'auth/create_class.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function() {
            btn.prop('disabled', true).html(`<div class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mx-auto"></div>`);
        },
        success: function(data) {
            if (data.status === 'success') {
                window.showToast(data.message, 'success');
                $('#classModal').trigger('click'); // Close modal
                // Reload the classes page content
                setTimeout(() => {
                    $("#classes").click(); 
                }, 500);
            } else {
                window.showToast(data.message, 'error');
            }
        },
        error: function() {
            window.showToast('Network error. Try again', 'error');
        },
        complete: function() {
            btn.prop('disabled', false).text('Create Class');
        }
    });
});

function assignTeacher(classId, teacherId) {
    if (!classId) return;

    window.showToast('Updating assignment...', 'success');

    $.ajax({
        url: 'auth/assign_teacher.php',
        type: 'POST',
        data: {
            class_id: classId,
            teacher_id: teacherId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.showToast(response.message, 'success');
            } else {
                window.showToast(response.message, 'error');
            }
        },
        error: function() {
            window.showToast('Network error. Please try again.', 'error');
        }
    });
}

function assignNextClass(classId, nextClassId) {
    if (!classId) return;
    
    window.showToast('Updating path...', 'success');
    
    $.ajax({
        url: 'auth/assign_next_class.php',
        type: 'POST',
        data: { class_id: classId, next_class_id: nextClassId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.showToast(response.message, 'success');
            } else {
                window.showToast(response.message, 'error');
            }
        },
        error: function() {
            window.showToast('Network error. Please try again.', 'error');
        }
    });
}

function manageClassSubjects(classId, className) {
    $.ajax({
        url: 'auth/get_class_subjects.php',
        type: 'GET',
        data: { class_id: classId },
        dataType: 'json',
        success: function(res) {
            if (!res.success) {
                Swal.fire('Error', res.message, 'error');
                return;
            }

            let html = '<div class="space-y-4 max-h-[400px] overflow-y-auto px-2">';
            res.subjects.forEach(sub => {
                let options = '<option value="">-- Unassigned --</option>';
                res.staff.forEach(st => {
                    const isSelected = (res.assignments[sub.id] == st.id) ? 'selected' : '';
                    options += `<option value="${st.id}" ${isSelected}>${st.first_name} ${st.surname}</option>`;
                });
                
                html += `
                    <div class="flex flex-col gap-1 text-left">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">${sub.subject}</label>
                        <select class="class-subject-select w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-gray-50 font-bold text-gray-700" data-subject-id="${sub.id}">
                            ${options}
                        </select>
                    </div>
                `;
            });
            html += '</div>';

            Swal.fire({
                title: 'Manage Subjects',
                html: `<p class="text-sm font-bold text-gray-400 mb-4 uppercase tracking-widest text-center">${className}</p>` + html,
                confirmButtonText: 'Save Assignments',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                width: '500px',
                preConfirm: () => {
                    let subjectsData = {};
                    $('.class-subject-select').each(function() {
                        const subId = $(this).data('subject-id');
                        const teacherId = $(this).val();
                        if (teacherId) {
                            subjectsData[subId] = teacherId;
                        }
                    });
                    
                    return {
                        class_id: classId,
                        subjects: subjectsData
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'auth/update_class_subjects.php',
                        type: 'POST',
                        data: result.value,
                        dataType: 'json',
                        success: function(updateRes) {
                            if (updateRes.success) {
                                window.showToast(updateRes.message, 'success');
                            } else {
                                Swal.fire('Error', updateRes.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Network error while saving.', 'error');
                        }
                    });
                }
            });
        },
        error: function() {
            Swal.fire('Error', 'Network error while fetching subjects.', 'error');
        }
    });
}
</script>

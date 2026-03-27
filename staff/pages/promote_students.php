<?php
require '../../connections/db.php';
require '../../auth/check.php';

if ($_SESSION['role'] !== 'staff') {
    echo "Access Denied.";
    exit;
}

// Get ONLY the classes this teacher is assigned to for "From Class"
/** @var stdClass|false $user */
$teacher_id = $user->id;
$stmt_teacher = $conn->prepare("SELECT id, class, next_class_id FROM class WHERE teacher_id = ? ORDER BY id ASC");
$stmt_teacher->execute([$teacher_id]);
$teacher_classes = $stmt_teacher->fetchAll(PDO::FETCH_ASSOC);

// Get all classes sequentially to calculate next class
$stmt_all = $conn->query("SELECT id, class, next_class_id FROM class ORDER BY id ASC");
$all_classes = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-700 hover:border-emerald-200 hover:bg-emerald-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0 shadow-sm border border-emerald-200">
                    <i class="fas fa-exchange-alt text-emerald-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Promote Students</h3>
                    <p class="text-sm text-gray-400 font-medium">Promote your students to their next class.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
        
        <!-- Left Panel: Configuration -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)]">
                <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-6">1. Setup Promotion</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">From Class (Current)</label>
                        <select id="fromClass" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 text-sm font-semibold text-gray-700 transition-all">
                            <option value="">Select Current Class</option>
                            <?php foreach($teacher_classes as $c): ?>
                                <option value="<?= htmlspecialchars($c['class']) ?>" data-id="<?= $c['id'] ?>" data-next="<?= $c['next_class_id'] ?? '' ?>"><?= htmlspecialchars($c['class']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex justify-center py-2">
                        <div class="size-8 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                            <i class="bx bx-down-arrow-alt text-xl"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">To Class (Next)</label>
                        <select id="toClass" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 text-sm font-semibold text-gray-700 transition-all">
                            <option value="">Select Destination Class</option>
                            <?php foreach($all_classes as $c): ?>
                                <option value="<?= htmlspecialchars($c['class']) ?>" data-id="<?= $c['id'] ?>" data-next="<?= $c['next_class_id'] ?? '' ?>"><?= htmlspecialchars($c['class']) ?></option>
                            <?php endforeach; ?>
                            <option value="Graduated">Graduated (Leave School)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <button id="loadStudentsBtn" class="w-full py-3.5 bg-gray-900 border border-transparent text-white rounded-xl font-bold text-sm shadow-xl hover:bg-black transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="bx bx-search-alt text-lg"></i> Load Eligible Students
                    </button>
                </div>
            </div>
            
            <!-- Warning Card -->
            <div class="bg-orange-50 rounded-3xl p-6 border border-orange-100">
                <div class="flex gap-3 text-orange-800">
                    <i class="bx bx-info-circle text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-medium leading-relaxed">
                        <span class="font-bold">Heads up:</span> Promoting a student changes their main class. Please do this only at the end of the academic session.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Panel: Students List -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] h-full min-h-[500px] flex flex-col relative overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">2. Select Students</h4>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Choose who gets promoted</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer group bg-white px-3 py-1.5 rounded-lg border border-gray-200 hover:border-emerald-300 transition-colors">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500" disabled>
                        <span class="text-xs font-bold text-gray-600 group-hover:text-emerald-700">Select All</span>
                    </label>
                </div>

                <!-- Empty/Loading State -->
                <div id="studentsPlaceholder" class="flex-1 flex flex-col items-center justify-center p-12 text-center">
                    <div class="size-20 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                        <i class="bx bx-group text-3xl text-gray-300"></i>
                    </div>
                    <h5 class="text-gray-500 font-bold text-sm">No Class Selected</h5>
                    <p class="text-xs text-gray-400 mt-2 max-w-xs mx-auto">Select the current and destination classes, then click load to view students.</p>
                </div>

                <!-- Students List -->
                <div id="studentsList" class="flex-1 overflow-y-auto hidden">
                    <!-- Inserted via JS -->
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex justify-between items-center hidden" id="promotionActions">
                    <div class="text-xs font-bold text-gray-500">
                        Selected: <span id="selectedCount" class="text-emerald-600 text-sm">0</span> students
                    </div>
                    <button id="promoteBtn" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-[0_4px_15px_rgb(16,185,129,0.3)] transition-all flex items-center justify-center gap-2 cursor-pointer group disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="bx bx-check-double text-xl group-hover:scale-110 transition-transform"></i> Apply Promotion
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    let loadedStudents = [];

    // Auto select class via next_class_id mapping or fallback to id+1
    $('#fromClass').on('change', function() {
        // use .attr('data-next') to prevent jQuery from parsing empty string as undefined in some older versions, or just check carefully
        const $selected = $(this).find(':selected');
        const nextId = $selected.attr('data-next');
        const selectedId = parseInt($selected.data('id'));
        
        if(nextId && nextId !== '') {
            if (nextId === '0') {
                $('#toClass').val('Graduated'); // Explicitly mapped to Graduation
            } else {
                const $nextOption = $('#toClass').find(`option[data-id="${nextId}"]`);
                if($nextOption.length > 0) {
                    $nextOption.prop('selected', true);
                } else {
                    $('#toClass').val('Graduated');
                }
            }
        } else if (selectedId) {
            // Fallback to mathematical ID + 1 if not mapped in DB
            const fallbackId = selectedId + 1;
            const $nextOption = $('#toClass').find(`option[data-id="${fallbackId}"]`);
            if($nextOption.length > 0) {
                $nextOption.prop('selected', true);
            } else {
                $('#toClass').val('Graduated');
            }
        } else {
            $('#toClass').val('');
        }
    });

    $('#loadStudentsBtn').on('click', function() {
        const fromClass = $('#fromClass').val();
        const toClass = $('#toClass').val();

        if(!fromClass) {
            showAlert('error', 'Please select the "From Class" to load students.');
            return;
        }

        if(fromClass === toClass) {
            showAlert('warning', 'The current class and destination class cannot be the same.');
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin text-xl"></i> Loading...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/promote_api.php?action=get_students',
            type: 'POST',
            data: { class: fromClass },
            success: function(res) {
                if(res.status === 'success') {
                    loadedStudents = res.data;
                    renderStudents();
                } else {
                    showAlert('error', res.message || 'Failed to load students');
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred. Please try again.');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

    function renderStudents() {
        if(loadedStudents.length === 0) {
            $('#studentsPlaceholder').removeClass('hidden').html(`
                <div class="size-20 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                    <i class="bx bx-user-x text-3xl text-gray-300"></i>
                </div>
                <h5 class="text-gray-500 font-bold text-sm">No Students Found</h5>
                <p class="text-xs text-gray-400 mt-2">There are no students currently enrolled in the selected class.</p>
            `);
            $('#studentsList, #promotionActions').addClass('hidden');
            $('#selectAll').prop('disabled', true);
            return;
        }

        let html = '<div class="divide-y divide-gray-100">';
        loadedStudents.forEach((st, idx) => {
            const avatar = st.profile_photo ? '../uploads/profile_photos/' + st.profile_photo : `https://ui-avatars.com/api/?name=${st.first_name}+${st.surname}&background=random`;
            html += `
                <label class="flex items-center gap-4 p-4 hover:bg-emerald-50/30 transition-colors cursor-pointer group">
                    <input type="checkbox" name="student_ids[]" value="${st.id}" class="student-checkbox w-5 h-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 ml-2">
                    <img src="${avatar}" class="size-10 rounded-full object-cover shadow-sm bg-white shrink-0">
                    <div class="flex-1 min-w-0">
                        <h5 class="text-sm font-bold text-gray-800 truncate">${st.first_name} ${st.surname}</h5>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest mt-0.5">${st.user_id}</p>
                    </div>
                </label>
            `;
        });
        html += '</div>';

        $('#studentsPlaceholder').addClass('hidden');
        $('#studentsList').removeClass('hidden').html(html);
        $('#promotionActions').removeClass('hidden');
        $('#selectAll').prop('disabled', false).prop('checked', false);
        updateSelectedCount();
    }

    $(document).on('change', '.student-checkbox', function() {
        updateSelectedCount();
        const total = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked && total > 0);
    });

    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.student-checkbox').prop('checked', isChecked);
        updateSelectedCount();
    });

    function updateSelectedCount() {
        const count = $('.student-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#promoteBtn').prop('disabled', count === 0);
    }

    $('#promoteBtn').on('click', function() {
        const fromClass = $('#fromClass').val();
        const toClass = $('#toClass').val();
        const selectedIds = [];

        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if(!toClass) {
            showAlert('error', 'Please select a destination "To Class".');
            return;
        }

        if(selectedIds.length === 0) {
            showAlert('warning', 'Please select at least one student to promote.');
            return;
        }

        Swal.fire({
            title: 'Confirm Promotion',
            html: `You are about to promote <b>${selectedIds.length}</b> student(s) from <br><span class='text-gray-500 text-sm'>${fromClass} &rarr; <b>${toClass}</b></span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Promote Them',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if(result.isConfirmed) {
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="bx bx-loader-alt bx-spin text-xl"></i> Applying...').prop('disabled', true);

                $.ajax({
                    url: BASE_URL + 'admin/auth/promote_api.php?action=promote',
                    type: 'POST',
                    data: {
                        from_class: fromClass,
                        to_class: toClass,
                        student_ids: selectedIds
                    },
                    success: function(res) {
                        if(res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Promotion Successful',
                                text: res.message,
                                confirmButtonColor: '#10b981'
                            });
                            // Refresh students list
                            $('#loadStudentsBtn').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Promotion failed.');
                        }
                    },
                    error: function() {
                        showAlert('error', 'Network error occurred. Please try again.');
                    },
                    complete: function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            }
        });
    });

</script>

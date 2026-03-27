<?php
require '../../connections/db.php';
require '../../auth/check.php';

/** @var stdClass|false $user */
if ($user->role !== 'student') {
    exit('Unauthorized');
}

$class = $_SESSION['class'] ?? $user->class ?? '';

$active_session_id = $_SESSION['active_session_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT a.*, u.first_name, u.surname,
        (SELECT grade FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = ? LIMIT 1) as my_score,
        (SELECT id FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = ? LIMIT 1) as is_submitted
    FROM assignments a
    JOIN users u ON a.teacher_id = u.id
    WHERE a.class = ? AND a.session_id = ?
    ORDER BY a.due_date DESC
");

$stmt->execute([$user->id, $user->id, $class, $active_session_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_OBJ);

?>
<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex items-center gap-5 mb-10">
        <div
            class="size-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-xl shadow-indigo-200 flex items-center justify-center">
            <i class="bx bx-list-ul text-3xl"></i>
        </div>
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight mb-1">My Assignments</h1>
            <p class="text-sm text-gray-500 font-medium">Pending & Completed homework for <span
                    class="text-indigo-600 font-bold"><?= htmlspecialchars($class) ?></span></p>
        </div>
    </div>

    <!-- Assignments Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($assignments as $a):
            $isPastDue = strtotime($a->due_date) < time();
            $statusColor = $a->is_submitted ? 'emerald' : ($isPastDue ? 'red' : 'indigo');
            $statusText = $a->is_submitted ? 'Submitted' : ($isPastDue ? 'Overdue' : 'Pending');
            $statusIcon = $a->is_submitted ? 'bx-check-double' : ($isPastDue ? 'bx-error-circle' : 'bx-time');
            ?>
            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all p-6 group flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <span
                        class="px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-widest bg-<?= $statusColor ?>-50 text-<?= $statusColor ?>-600 border border-<?= $statusColor ?>-100 flex items-center gap-1">
                        <i class="bx <?= $statusIcon ?> text-sm"></i> <?= $statusText ?>
                    </span>
                    <?php if ($a->is_submitted && $a->my_score !== null): ?>
                        <span
                            class="text-xs font-semibold text-gray-800 bg-gray-100 px-2 py-1 rounded-lg border border-gray-200">
                            <?= $a->my_score ?> / 100
                        </span>
                    <?php endif; ?>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2 truncate" title="<?= htmlspecialchars($a->title) ?>">
                    <?= htmlspecialchars($a->title) ?></h3>
                <p class="text-xs font-bold text-gray-400 mb-4 italic">By: Teacher <?= htmlspecialchars($a->first_name) ?>
                </p>

                <p class="text-sm text-gray-500 line-clamp-3 mb-6 flex-1 text-ellipsis overflow-hidden">
                    <?= htmlspecialchars($a->description) ?></p>

                <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Due: <?= date('M d, g:ia', strtotime($a->due_date)) ?>
                    </p>
                    <div class="flex items-center gap-2">
                        <?php if ($a->attachment): ?>
                            <a href="../uploads/assignments/<?= htmlspecialchars($a->attachment) ?>" target="_blank"
                                class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center cursor-pointer"
                                title="Download Material">
                                <i class="fas fa-file-download text-lg"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!$a->is_submitted && !$isPastDue): ?>
                            <button onclick="openSubmitModal(<?= $a->id ?>, '<?= htmlspecialchars(addslashes($a->title)) ?>')"
                                class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white hover:shadow-md transition-all flex items-center justify-center"
                                title="Submit Answer">
                                <i class="bx bx-pencil text-lg"></i>
                            </button>
                        <?php else: ?>
                            <button disabled
                                class="w-8 h-8 rounded-full bg-gray-50 text-gray-300 flex items-center justify-center cursor-not-allowed">
                                <i class="bx <?= $a->is_submitted ? 'bx-check' : 'bx-x' ?> text-xl"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($assignments)): ?>
            <div class="col-span-full py-20 text-center bg-gray-50 border border-dashed border-gray-200 rounded-[3rem]">
                <div
                    class="w-20 h-20 bg-white shadow-sm rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="bx bx-badge-check text-4xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">You're All Caught Up!</h3>
                <p class="text-gray-400 text-sm mt-1">No pending assignments found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Submit Assignment -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in"
    id="modalSubmit">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2" id="submitModalTitle"><i
                    class="bx bx-upload text-indigo-500"></i> Turn In Homework</h3>
            <button class="text-gray-400 hover:text-gray-700 transition"
                onclick="$('#modalSubmit').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <form id="formSubmit" class="p-6 space-y-5">
            <input type="hidden" name="assignment_id" id="submitAssignmentId">
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Your Answer
                    <span class="text-gray-400 font-normal">(Optional if file attached)</span></label>
                <textarea name="text_answer" rows="5" placeholder="Type your answer here..."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none text-sm text-gray-700"></textarea>
            </div>

            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Attach
                    Document <span class="text-gray-400 font-normal">(Max 5MB)</span></label>
                <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.png"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all cursor-pointer">
            </div>

            <button type="submit"
                class="w-full py-4 bg-indigo-600 text-white rounded-xl font-semibold shadow-lg shadow-indigo-100 hover:-translate-y-1 hover:shadow-xl hover:bg-indigo-700 transition-all mt-6 uppercase tracking-widest text-[10px]">Submit
                Answer</button>
        </form>
    </div>
</div>

<script>
    function openSubmitModal(id, title) {
        $('#submitAssignmentId').val(id);
        $('#submitModalTitle').html('<i class="bx bx-upload text-indigo-500"></i> ' + title);
        $('#formSubmit')[0].reset();
        $('#modalSubmit').removeClass('hidden');
    }

    $('#formSubmit').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);
        const formData = new FormData(this);

        $.ajax({
            url: '../student/auth/assignment_api.php?action=submit',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                btn.html(og).prop('disabled', false);
                if (res.status === 'success') {
                    window.showToast(res.message, "success");
                    $('#modalSubmit').addClass('hidden');
                    setTimeout(() => window.loadPage('pages/assignments.php'), 500);
                } else {
                    window.showToast(res.message, "error");
                }
            },
            error: function () {
                btn.html(og).prop('disabled', false);
                window.showToast("Network Error", "error");
            }
        });
    });
</script>
<?php
require __DIR__ . '/../../auth/check.php';

$teacher_id = $user->id;
$session_year = $_SESSION['active_session'] ?? '';
$term = $_SESSION['active_term'] ?? '';

// Fetch teacher's subjects & classes
$stmt = $conn->prepare("
    SELECT DISTINCT s.id as subject_id, s.subject, c.class as class_name 
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    JOIN class c ON ta.class_id = c.id
    WHERE ta.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_OBJ);

$selected_subject = $_GET['subject_id'] ?? ($assignments[0]->subject_id ?? 0);
$selected_class = $_GET['class_name'] ?? ($assignments[0]->class_name ?? '');

// Fetch students in the selected class
$stmt = $conn->prepare("SELECT id, first_name, surname, user_id FROM users WHERE role = 'student' AND class = ? ORDER BY first_name ASC");
$stmt->execute([$selected_class]);
$students = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch existing CA scores
$ca_stmt = $conn->prepare("SELECT student_id, ca_score FROM continuous_assessment WHERE subject_id = ? AND class_name = ? AND term = ? AND session_year = ?");
$ca_stmt->execute([$selected_subject, $selected_class, $term, $session_year]);
$existing_ca = $ca_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-emerald-600 text-white shadow-xl shadow-emerald-100 flex items-center justify-center">
                <i class="bx bx-edit text-4xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Continuous Assessment</h1>
                <p class="text-sm text-gray-400 font-medium italic">Record project, test, and classwork scores (Max 40)</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50">
            <select onchange="let parts = this.value.split('|'); filterCA(parts[0], parts[1]);" class="border-none focus:ring-0 text-sm font-bold text-gray-700 bg-transparent cursor-pointer">
                <?php foreach($assignments as $a): ?>
                    <option value="<?= $a->subject_id ?>|<?= htmlspecialchars($a->class_name) ?>" <?= ($selected_subject == $a->subject_id && $selected_class === $a->class_name) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a->subject) ?> - <?= htmlspecialchars($a->class_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-2xl overflow-hidden">
        <form id="caForm">
            <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
            <input type="hidden" name="class_name" value="<?= $selected_class ?>">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-8 py-6 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Student Information</th>
                            <th class="px-8 py-6 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-center">Admission ID</th>
                            <th class="px-8 py-6 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-center">CA Score (40%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($students as $s): ?>
                            <tr class="hover:bg-gray-50/30 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="size-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                            <?= ucfirst($s->first_name[0]) ?>
                                        </div>
                                        <span class="text-sm font-bold text-gray-800"><?= ucfirst($s->first_name) . ' ' . ucfirst($s->surname) ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-widest">
                                    <?= $s->user_id ?>
                                </td>
                                <td class="px-8 py-5 flex justify-center">
                                    <input type="number" step="0.5" min="0" max="40" name="scores[<?= $s->id ?>]" 
                                        value="<?= $existing_ca[$s->id] ?? '' ?>"
                                        class="w-24 bg-gray-50 border-2 border-transparent text-center font-semibold text-emerald-700 rounded-xl px-4 py-2.5 focus:border-emerald-500 focus:bg-white focus:ring-0 transition-all"
                                        placeholder="0.0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-8 bg-gray-50/30 border-t border-gray-100 flex justify-end">
                <button type="submit" class="px-12 py-4 bg-emerald-600 text-white rounded-[1.25rem] font-semibold text-xs uppercase tracking-widest hover:shadow-2xl hover:shadow-emerald-200 hover:-translate-y-1 transition-all">
                    Save Assessment Records
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterCA(sid, cls) {
    loadPage(BASE_URL + 'staff/pages/ca_records.php?subject_id=' + sid + '&class_name=' + encodeURIComponent(cls));
}

$('#caForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: BASE_URL + 'staff/auth/ca_api.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire('Saved!', 'CA scores have been updated successfully.', 'success');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});
</script>

<?php
require '../../connections/db.php';

$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$limit = 10;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

/* FETCH DATA */
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE role = 'student' 
    ORDER BY first_name ASC 
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_OBJ);

/* COUNT TOTAL */
$totalRecords = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalPages = ceil($totalRecords / $limit);
?>

<?php if (count($students) > 0): ?>
    <?php $i = $offset; foreach ($students as $row): 
        $initials = strtoupper(substr($row->first_name, 0, 1) . substr($row->last_name, 0, 1));
        $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500', 'bg-indigo-500'];
        $randomColor = $colors[$row->id % count($colors)];
    ?>
        <tr class="hover:bg-gray-50/80 transition-colors group border-b border-gray-50">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <?php if (!empty($row->profile_photo)): ?>
                        <img src="/school_app/uploads/profile_photos/<?= $row->profile_photo ?>" class="w-10 h-10 rounded-full object-cover shadow-sm ring-2 ring-white">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full <?= $randomColor ?> flex items-center justify-center text-white text-xs font-bold shadow-sm ring-2 ring-white">
                            <?= $initials ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="student-fullname text-sm font-bold text-gray-800"><?= htmlspecialchars($row->first_name . ' ' . $row->last_name) ?></p>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-tight"><?= htmlspecialchars($row->user_id) ?></p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="px-3 py-1 bg-gray-100 rounded-lg text-[10px] font-mono font-black text-gray-500 uppercase">
                    <?= htmlspecialchars($row->user_id) ?>
                </span>
            </td>
            <td class="px-4 py-3">
                 <p class="student-class text-sm font-bold text-gray-600"><?= htmlspecialchars($row->class) ?></p>
            </td>
            <td class="px-4 py-3 user-status-cell">
                <?php if($row->status == 1): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>
                        Active
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                        Inactive
                    </span>
                <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2 text-gray-400 group-hover:text-gray-600 transition-colors">
                    <button class="reset-btn p-2 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl transition cursor-pointer" data-id="<?= $row->id ?>" data-name="<?= htmlspecialchars($row->first_name . ' ' . $row->last_name) ?>"
                        data-tippy-content="Reset Password">
                        <i class="bx-key text-xl"></i>
                    </button>
                    <button class="edit-btn p-2 hover:bg-sky-50 hover:text-sky-600 rounded-xl transition cursor-pointer" data-id="<?= $row->id ?>" data-tippy-content="Edit Student">
                        <i class="bx-pencil text-xl"></i>
                    </button>
                    <button class="delete-btn p-2 hover:bg-red-50 hover:text-red-600 rounded-xl transition cursor-pointer" data-id="<?= $row->id ?>" data-tippy-content="Delete Student">
                        <i class="bx-trash text-xl"></i>
                    </button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>

    <!-- PAGINATION -->
    <tr>
        <td colspan="5" class="p-6 bg-gray-50/30">
            <div class="flex justify-center items-center gap-2">
                <?php if ($page > 1): ?>
                    <button class="student-page-btn w-9 h-9 shrink-0 rounded-xl border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200 transition shadow-sm cursor-pointer" data-page="<?= $page - 1 ?>">
                        <i class="bx bx-chevron-left text-xl"></i>
                    </button>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                        <button
                            class="student-page-btn w-9 h-9 shrink-0 rounded-xl border <?= $p == $page ? 'border-sky-600 bg-sky-600 text-white shadow-md shadow-sky-100' : 'border-gray-200 bg-white text-gray-500 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200' ?> flex items-center justify-center text-xs font-bold transition shadow-sm cursor-pointer"
                            data-page="<?= $p ?>">
                            <?= $p ?>
                        </button>
                    <?php elseif ($p == $page - 3 || $p == $page + 3): ?>
                        <span class="text-gray-400 px-1">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <button class="student-page-btn w-9 h-9 shrink-0 rounded-xl border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200 transition shadow-sm cursor-pointer" data-page="<?= $page + 1 ?>">
                        <i class="bx bx-chevron-right text-xl"></i>
                    </button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php else: ?>
    <tr>
        <td colspan="5" class="p-12 text-center text-gray-400">
            <div class="flex flex-col items-center gap-2">
                <i class="bx-user-x text-4xl"></i>
                <p class="font-bold">No students found.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>

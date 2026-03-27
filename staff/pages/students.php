<?php
require __DIR__ . '/../../auth/check.php';

// Only staff can access this page
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$staff_class = $_SESSION['class'] ?? '';

// Fetch unique classes for this teacher
$class_stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.class 
    FROM class c 
    WHERE c.teacher_id = :tid 
    ORDER BY c.class ASC
");
$class_stmt->execute([':tid' => $user->id]);
$assigned_classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);
$assigned_class_names = array_map(function ($c) {
    return $c->class;
}, $assigned_classes);

// Filter by selected class if set
$selected_class = isset($_GET['filter_class']) ? $_GET['filter_class'] : '';

// Final classes to filter students
$filter_classes = $selected_class ? [$selected_class] : $assigned_class_names;

// If the teacher has no assigned classes, use an empty array
if (empty($filter_classes))
    $filter_classes = ['__NONE__'];

$placeholders = implode(',', array_fill(0, count($filter_classes), '?'));

// Pagination Settings
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

// Total records for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND class IN ($placeholders)");
$count_stmt->execute($filter_classes);
$totalRecords = $count_stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch students
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'student' AND class IN ($placeholders) ORDER BY class ASC, first_name ASC LIMIT $limit OFFSET $offset");
$stmt->execute($filter_classes);
$students = $stmt->fetchAll(PDO::FETCH_OBJ);

?>

<div class="fadeIn w-full md:p-8 p-4">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-orange-100 text-orange-600 shadow-sm">
                <i class="bx-group text-3xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">My Students</h1>
                <p class="text-sm text-gray-500">
                    <?php if ($selected_class): ?>
                        Managing students in Class: <span class="font-bold text-orange-600"><?= htmlspecialchars($selected_class) ?></span>
                    <?php else: ?>
                        All students in your assigned classes: <span
                            class="font-bold text-orange-600"><?= htmlspecialchars(implode(', ', $assigned_class_names) ?: 'None') ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-center gap-3">
            <!-- Class Filter -->
            <div class="relative w-full md:w-56 group">
                <i class="bx bx-filter absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                <select id="staffClassFilter" 
                    class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition shadow-sm appearance-none cursor-pointer font-semibold text-gray-600">
                    <option value="">All My Classes</option>
                    <?php foreach ($assigned_classes as $ac): ?>
                        <option value="<?= htmlspecialchars($ac->class) ?>" <?= $selected_class === $ac->class ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ac->class) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

             <div class="relative w-full md:w-64 group">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                <input type="text" id="staffStudentSearch" 
                    class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition shadow-sm"
                    placeholder="Search students..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            <button id="staffStudentCSV"
                class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="bx bx-cloud-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Students Table Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="staffStudentTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Student Info</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Student ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="staffStudentTableBody" class="divide-y divide-gray-50">
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student):
                            $initials = strtoupper(substr($student->first_name, 0, 1) . substr($student->surname, 0, 1));
                            $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500', 'bg-indigo-500'];
                            $randomColor = $colors[array_rand($colors)];
                            ?>
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($student->profile_photo)): ?>
                                                                    <img src="<?= $base ?>uploads/profile_photos/<?= $student->profile_photo ?>"
                                                class="w-10 h-10 rounded-full object-cover shadow-sm ring-2 ring-white">
                                        <?php else: ?>
                                            <div
                                                class="w-10 h-10 rounded-full <?= $randomColor ?> flex items-center justify-center text-white text-xs font-bold shadow-sm ring-2 ring-white">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">
                                                <?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?></p>
                                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-tight">
                                                <?= htmlspecialchars($student->class) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-gray-100 rounded-lg text-xs font-mono font-bold text-gray-600">
                                        <?= htmlspecialchars($student->user_id) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm"><?php if ($student->status == 1): ?>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="View Performance">
                                            <i class="bx-line-chart text-xl"></i>
                                        </button>
                                        <button class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition"
                                            title="Send Message">
                                            <i class="bx-envelope text-xl"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center text-gray-300">
                                        <i class="bx-user-circle text-4xl"></i>
                                    </div>
                                    <p class="text-gray-400 font-medium italic">No students found in your assigned class.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between gap-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                    Showing Page <span class="text-orange-600"><?= $page ?></span> of <?= $totalPages ?>
                </p>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <button
                            class="staff-student-pagination-btn w-9 h-9 shrink-0 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 transition shadow-sm cursor-pointer"
                            data-page="<?= $page - 1 ?>">
                            <i class="bx bx-chevron-left text-xl"></i>
                        </button>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button
                            class="staff-student-pagination-btn w-9 h-9 shrink-0 rounded-lg border <?= $i == $page ? 'border-orange-600 bg-orange-600 text-white' : 'border-gray-200 bg-white text-gray-500 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200' ?> flex items-center justify-center text-xs font-bold transition shadow-sm cursor-pointer"
                            data-page="<?= $i ?>">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <button
                            class="staff-student-pagination-btn w-9 h-9 shrink-0 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 transition shadow-sm cursor-pointer"
                            data-page="<?= $page + 1 ?>">
                            <i class="bx bx-chevron-right text-xl"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Initialize search
    window.initTableToolkit({
        searchId: 'staffStudentSearch',
        tableId: 'staffStudentTable',
        bodyId: 'staffStudentTableBody',
        csvBtnId: 'staffStudentCSV',
        csvName: 'my_students'
    });

    // Handle class filter
    $("#staffClassFilter").on("change", function() {
        const val = $(this).val();
        const url = "pages/students.php?filter_class=" + encodeURIComponent(val);
        if (typeof loadPage === "function") {
            loadPage(url);
        } else {
            $("#mainContent").load(url);
        }
    });

    // Handle pagination clicks without reloading the whole page browser-wise
    $(".staff-student-pagination-btn").off("click").on("click", function () {
        const page = $(this).data("page");
        const selectedClass = "<?= htmlspecialchars($selected_class) ?>";
        const url = "pages/students.php?page=" + page + (selectedClass ? "&filter_class=" + encodeURIComponent(selectedClass) : "");

        // Use the global loadPage function if available, or manually load
        if (typeof loadPage === "function") {
            loadPage(url);
        } else {
            $("#mainContent").load(url);
        }
    });
</script>
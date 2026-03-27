<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch basic data
$stmt = $conn->query("SELECT * FROM physical_books ORDER BY id DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT i.*, b.title as book_title, b.isbn,
           CASE 
             WHEN i.user_type = 'student' THEN u1.first_name
             ELSE u2.first_name 
           END as first_name,
           CASE 
             WHEN i.user_type = 'student' THEN u1.surname
             ELSE u2.surname 
           END as surname
    FROM physical_book_issues i
    JOIN physical_books b ON i.book_id = b.id
    LEFT JOIN users u1 ON i.user_id = u1.id AND i.user_type = 'student'
    LEFT JOIN users u2 ON i.user_id = u2.id AND i.user_type = 'staff'
    ORDER BY i.id DESC
");
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch members (students + staff) for the issue dropdown
$all_members = [];

$stmt = $conn->query("SELECT id, first_name, surname, class, user_id as username FROM users WHERE role = 'student' ORDER BY class ASC, first_name ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($students as $s) {
    $all_members[] = [
        'id' => $s['id'],
        'name' => $s['first_name'] . ' ' . $s['surname'],
        'detail' => 'Student - ' . $s['class'] . ' (' . $s['username'] . ')',
        'type' => 'student'
    ];
}

$stmt = $conn->query("SELECT id, first_name, surname, role FROM users WHERE role IN ('super', 'admin', 'staff') ORDER BY first_name ASC");
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($staff_list as $st) {
    if (in_array($st['role'], ['super', 'admin', 'staff'])) {
        $all_members[] = [
            'id' => $st['id'],
            'name' => $st['first_name'] . ' ' . $st['surname'],
            'detail' => 'Staff / Admin',
            'type' => 'staff'
        ];
    }
}
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-amber-700 hover:border-amber-200 hover:bg-amber-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0 shadow-sm border border-amber-200">
                    <i class="bx bx-book text-amber-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Physical Library</h3>
                    <p class="text-sm text-gray-400 font-medium">Catalog physical books and manage issues/returns</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-white rounded-[2rem] shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-2 mb-8 inline-flex overflow-x-auto custom-scrollbar max-w-full">
        <button onclick="switchLibraryTab('catalog')" id="tabBtnLib_catalog" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm bg-amber-500 text-white shadow-lg shadow-amber-200 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-book text-lg"></i> Book Catalog</span>
        </button>
        <button onclick="switchLibraryTab('issues')" id="tabBtnLib_issues" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-scan-barcode text-lg"></i> Issue & Returns</span>
        </button>
    </div>

    <!-- TABS CONTENT SECTION -->
    
    <!-- Tab 1: Catalog -->
    <div id="tabLib_catalog" class="tab-pane-lib">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Add Book Form -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-book-add text-amber-500 mr-1"></i> Add to Catalog</h4>
                    <form id="bookForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Book Title</label>
                            <input type="text" name="title" required placeholder="e.g. Introduction to Physics" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Author(s)</label>
                            <input type="text" name="author" required placeholder="John Doe" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Category</label>
                                <select name="category" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                                    <option value="Science">Science</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Languages">Languages</option>
                                    <option value="History">History</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Arts">Arts</option>
                                    <option value="Novels">Novels</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Quantity</label>
                                <input type="number" name="quantity" required value="1" min="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">ISBN (Optional)</label>
                            <input type="text" name="isbn" placeholder="ISBN-13" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                        </div>
                        <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm shadow-xl transition-all">
                            Add Book
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Book Catalog List -->
            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php if(empty($books)): ?>
                        <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                            <i class="bx bx-book-open text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">Catalog is Empty</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach($books as $b): 
                            $available = $b['quantity'] - $b['issued_qty'];
                            $availableColor = $available > 0 ? 'text-emerald-600' : 'text-red-500';
                        ?>
                            <div class="bg-white p-5 rounded-3xl border border-gray-100 relative group hover:shadow-[0_4px_20px_rgb(0,0,0,0.06)] transition-all flex flex-col justify-between">
                                <div class="absolute top-4 right-4">
                                    <button onclick="deleteResourceLib('book', <?= $b['id'] ?>)" class="size-8 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Delete Book">
                                        <i class="bx bx-trash text-sm"></i>
                                    </button>
                                </div>
                                <div>
                                    <div class="px-2 py-1 bg-amber-50 text-amber-600 rounded text-[10px] font-semibold uppercase tracking-widest border border-amber-100 inline-block mb-3">
                                        <?= htmlspecialchars($b['category']) ?>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-800 leading-tight mb-1 pr-8 line-clamp-2" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></h4>
                                    <p class="text-xs font-semibold text-gray-500 mb-2">By: <?= htmlspecialchars($b['author']) ?></p>
                                    
                                    <?php if($b['isbn']): ?>
                                        <p class="text-[10px] text-gray-400 font-mono mb-4">ISBN: <?= htmlspecialchars($b['isbn']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total: <?= $b['quantity'] ?></div>
                                    <div class="text-xs font-bold <?= $availableColor ?>"><?= $available ?> Available</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Issues -->
    <div id="tabLib_issues" class="tab-pane-lib hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Issue Form -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6 sticky top-24">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-scan-barcode text-amber-500 mr-1"></i> Issue Book</h4>
                    <form id="issueForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select Book</label>
                            <select name="book_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold max-h-48 overflow-y-auto">
                                <option value="">Choose a text...</option>
                                <?php foreach($books as $b): 
                                    $av = $b['quantity'] - $b['issued_qty'];
                                    if ($av > 0): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> (<?= $av ?> avail)</option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Member</label>
                            <select name="user_data" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold max-h-48 overflow-y-auto">
                                <option value="">Select recipient...</option>
                                <?php foreach($all_members as $m): ?>
                                    <option value="<?= $m['id'] ?>|<?= $m['type'] ?>"><?= htmlspecialchars($m['name']) ?> - <?= htmlspecialchars($m['detail']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Due Date</label>
                            <input type="date" name="due_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-100 focus:border-amber-400 transition-all text-sm font-semibold">
                        </div>
                        <button type="submit" class="w-full mt-2 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-sm shadow-xl shadow-amber-200 transition-all">
                            Process Issue
                        </button>
                    </form>
                </div>
            </div>

            <!-- Issues Table -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-800">Issue History</h4>
                        <span class="text-xs font-bold text-gray-400 bg-white px-3 py-1 rounded-full border border-gray-200"><?= count($issues) ?> Records</span>
                    </div>
                    
                    <?php if(empty($issues)): ?>
                        <div class="py-16 text-center">
                            <i class="bx bx-receipt text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">No Circulation Data</h5>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 text-[10px] uppercase tracking-widest font-semibold text-gray-400 border-b border-gray-100">
                                        <th class="p-4 pl-6">Book / ISBN</th>
                                        <th class="p-4">Member</th>
                                        <th class="p-4">Timeline</th>
                                        <th class="p-4 text-center">Status / Return</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach($issues as $i): 
                                        $now = date('Y-m-d');
                                        $due = $i['due_date'];
                                        $is_overdue = ($i['status'] === 'issued' && $now > $due);
                                        $statusClass = '';
                                        if($i['status'] === 'returned') $statusClass = 'bg-emerald-100 text-emerald-700';
                                        elseif($is_overdue) $statusClass = 'bg-red-100 text-red-700 animate-pulse';
                                        else $statusClass = 'bg-amber-100 text-amber-700';
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-4 pl-6">
                                            <p class="text-sm font-bold text-gray-800 line-clamp-1" title="<?= htmlspecialchars($i['book_title']) ?>"><?= htmlspecialchars($i['book_title']) ?></p>
                                            <p class="text-[10px] font-mono text-gray-400"><?= htmlspecialchars($i['isbn'] ?? 'N/A') ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-sm font-bold text-gray-700"><?= htmlspecialchars($i['first_name'] . ' ' . $i['surname']) ?></p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase"><?= htmlspecialchars($i['user_type']) ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-xs font-semibold text-gray-500">Issued: <span class="text-gray-800"><?= date('M j, Y', strtotime($i['issue_date'])) ?></span></p>
                                            <p class="text-[11px] font-bold <?= $is_overdue ? 'text-red-500' : 'text-amber-500' ?>">Due: <?= date('M j, Y', strtotime($i['due_date'])) ?></p>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <span class="text-[9px] font-semibold uppercase tracking-widest px-2 py-1 rounded <?= $statusClass ?>">
                                                    <?= $is_overdue ? 'OVERDUE' : htmlspecialchars($i['status']) ?>
                                                </span>
                                                <?php if($i['status'] === 'issued'): ?>
                                                <button onclick="returnBook(<?= $i['id'] ?>)" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-gray-900 text-white hover:bg-black transition-all">
                                                    Mark Return
                                                </button>
                                                <?php else: ?>
                                                    <p class="text-[10px] text-gray-400 font-bold"><?= date('M j', strtotime($i['return_date'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    // UI Tab Switcher
    function switchLibraryTab(tabId) {
        $('.tab-pane-lib').addClass('hidden');
        $('#tabLib_' + tabId).removeClass('hidden');

        // Reset all buttons
        $('[id^="tabBtnLib_"]').removeClass('bg-amber-500 text-white shadow-lg shadow-amber-200').addClass('text-gray-500 hover:text-amber-600 hover:bg-amber-50');
        
        // Active button
        $('#tabBtnLib_' + tabId).removeClass('text-gray-500 hover:text-amber-600 hover:bg-amber-50').addClass('bg-amber-500 text-white shadow-lg shadow-amber-200');
    }

    // Default Tab
    switchLibraryTab('catalog');

    // Forms Handlers
    $('#bookForm').on('submit', function(e) {
        e.preventDefault();
        submitLibForm($(this), 'add_book', 'Saving...');
    });

    $('#issueForm').on('submit', function(e) {
        e.preventDefault();
        let payload = $(this).serializeArray();
        let userData = payload.find(x => x.name === 'user_data').value.split('|');
        if(userData.length === 2) {
            payload = payload.filter(x => x.name !== 'user_data');
            payload.push({name: 'user_id', value: userData[0]});
            payload.push({name: 'user_type', value: userData[1]});
        }

        const btn = $(this).find('button[type="submit"]');
        const ogText = btn.html();
        btn.html(`<i class="bx bx-loader-alt bx-spin"></i> Processing...`).prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/physical_library_api.php?action=issue_book',
            type: 'POST',
            data: $.param(payload),
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    $('#physicalLibrary').trigger('click'); 
                } else {
                    showAlert('error', res.message || 'An error occurred.');
                }
            },
            error: function() {
                showAlert('error', 'Network failure.');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    function submitLibForm(form, action, loadingText) {
        const btn = form.find('button[type="submit"]');
        const ogText = btn.html();
        btn.html(`<i class="bx bx-loader-alt bx-spin"></i> ${loadingText}`).prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/physical_library_api.php?action=' + action,
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    form[0].reset();
                    $('#physicalLibrary').trigger('click'); 
                } else {
                    showAlert('error', res.message || 'An error occurred.');
                }
            },
            error: function() {
                showAlert('error', 'Network communication failed.');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    }

    window.deleteResourceLib = function(type, id) {
        Swal.fire({
            title: 'Remove Book?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/physical_library_api.php?action=delete_' + type,
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#physicalLibrary').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Deletion failed.');
                        }
                    }
                });
            }
        });
    };

    window.returnBook = function(id) {
        Swal.fire({
            title: 'Confirm Return',
            text: "Mark this book as returned by the member?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Returned'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/physical_library_api.php?action=return_book',
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#physicalLibrary').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Action failed.');
                        }
                    }
                });
            }
        });
    };
</script>

<?php
session_start();
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    exit;
}

$user = $_SESSION['user_id'] ?? '';

$stmt = $conn->prepare("SELECT * FROM broadcast WHERE user_id = $user ORDER BY created_at DESC");
$stmt->execute();
$broadcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($broadcasts) === 0) {
    echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No broadcasts found.</td></tr>';
    exit;
}

foreach ($broadcasts as $item) {
    $date = date('M j, Y • g:i A', strtotime($item['created_at']));
    $statusText = $item['is_read'] ? 'Read' : 'Unread';
    $statusColor = $item['is_read'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
    
    echo '<tr class="hover:bg-gray-50/50 transition-colors border-b border-gray-50/50 group">';
    echo '<td class="px-6 py-4 max-w-[150px] align-middle">';
    echo '  <p class="text-sm font-bold text-gray-800 break-words">' . htmlspecialchars($item['recipient']) . '</p>';
    echo '</td>';
    echo '<td class="px-6 py-4 max-w-[200px] align-middle">';
    echo '  <p class="text-sm font-bold text-gray-800 truncate" title="' . htmlspecialchars($item['subject']) . '">' . htmlspecialchars($item['subject']) . '</p>';
    echo '</td>';
    echo '<td class="px-6 py-4 align-middle whitespace-nowrap">';
    echo '  <p class="text-xs text-gray-500">' . $date . '</p>';
    echo '</td>';
    echo '<td class="px-6 py-4 align-middle whitespace-nowrap">';
    echo '  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest ' . $statusColor . '">' . $statusText . '</span>';
    echo '</td>';
    echo '<td class="px-6 py-4 text-right align-middle whitespace-nowrap">';
    echo '  <button data-id="' . $item['id'] . '" class="edit-broadcast-btn mr-2 size-8 inline-flex items-center justify-center rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-500 hover:text-white transition-all shadow-sm" data-tippy-content="Edit"><i class="bx bx-edit text-lg"></i></button>';
    echo '  <button data-id="' . $item['id'] . '" class="delete-broadcast-btn size-8 inline-flex items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-all shadow-sm" data-tippy-content="Delete"><i class="bx bx-trash text-lg"></i></button>';
    echo '</td>';
    echo '</tr>';
}
?>

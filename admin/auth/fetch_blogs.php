<?php
session_start();
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    exit;
}

$stmt = $conn->prepare("SELECT * FROM blog ORDER BY posted_at DESC");
$stmt->execute();
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($blogs) === 0) {
    echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No blogs found.</td></tr>';
    exit;
}

foreach ($blogs as $item) {
    $date = date('M j, Y • g:i A', strtotime($item['posted_at']));
    $imagePath = !empty($item['blog_image']) ? $base . 'uploads/blogs/' . htmlspecialchars($item['blog_image']) : $base . 'src/img/placeholder.jpg';
    
    echo '<tr class="hover:bg-gray-50/50 transition-colors border-b border-gray-50/50 group">';
    echo '<td class="px-6 py-4">';
    echo '  <p class="text-sm font-bold text-gray-800 break-words max-w-[200px]">' . htmlspecialchars($item['blog_title']) . '</p>';
    echo '</td>';
    echo '<td class="px-6 py-4">';
    echo '  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest bg-emerald-100 text-emerald-700">' . htmlspecialchars($item['blog_category']) . '</span>';
    echo '</td>';
    echo '<td class="px-6 py-4">';
    echo '  <img src="' . $imagePath . '" alt="Cover" class="w-12 h-12 rounded-lg object-cover shadow-sm border border-gray-100">';
    echo '</td>';
    echo '<td class="px-6 py-4">';
    echo '  <p class="text-xs text-gray-500 whitespace-nowrap">' . $date . '</p>';
    echo '</td>';
    echo '<td class="px-6 py-4 text-right whitespace-nowrap">';
    echo '  <button data-id="' . $item['id'] . '" class="edit-blog-btn mr-2 size-8 inline-flex items-center justify-center rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-500 hover:text-white transition-all shadow-sm" data-tippy-content="Edit"><i class="bx bx-edit text-lg"></i></button>';
    echo '  <button data-id="' . $item['id'] . '" class="delete-blog-btn size-8 inline-flex items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-all shadow-sm" data-tippy-content="Delete"><i class="bx bx-trash text-lg"></i></button>';
    echo '</td>';
    echo '</tr>';
}
?>

<?php
require __DIR__ . '/../auth/check.php';

// redirect to configuration page if school settings is not yet configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();

if ($check_config->rowCount() < 1) {
      session_unset();
      session_destroy();
      header("Location: /school_app/index.php");
}
;
// -------------------------------------------------------------//

// Only students can access this page
if ($user->role !== 'student') {
      header("Location: /school_app/auth/login.php");
      exit();
}

$stmt = $conn->prepare("
    SELECT * FROM broadcast
    WHERE recipient = :recipient
    ORDER BY created_at DESC
");

$stmt->execute([
      ':recipient' => $_SESSION['username']
]);

/** @var array $results */
$results = $stmt->fetchAll(PDO::FETCH_OBJ);


$blog_stmt = $conn->prepare('SELECT * FROM blog ORDER BY id DESC LIMIT 6');
$blog_stmt->execute();

/** @var array $blogs_list */
$blogs_list = $blog_stmt->fetchAll(PDO::FETCH_OBJ);

// Count unread messages
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM broadcast WHERE recipient = :recipient AND is_read = 0");
$unread_stmt->execute([':recipient' => $_SESSION['username']]);
$unread_messages = (int) $unread_stmt->fetchColumn();

?>

<?php require '../components/header.php'; ?>
<?php require '../components/sidebar.php'; ?>

<!-- right nav -->
<main class="ml-0 md:ml-72">
      <!-- top Nav -->
      <?php require '../components/navbar.php'; ?>

      <!-- Main Content -->
      <div class="flex w-full" id="mainContent">

            <div class="fadeIn w-full md:p-8 p-4">

                  <!-- Welcome Banner -->
                  <div class="relative overflow-hidden bg-gradient-to-br from-sky-600 via-blue-500 to-indigo-500 rounded-2xl p-6 md:p-8 mb-6 shadow-lg">
                        <!-- Decorative circles -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white/5 rounded-full"></div>

                        <div class="relative z-10">
                              <p class="text-blue-100 text-sm font-medium mb-1">
                                    <?= date('l, F j, Y') ?>
                              </p>
                              <h3 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                    Hey, <?= ucfirst($user->first_name) ?> 👋
                              </h3>
                              <p class="text-blue-100 text-sm">
                                    Ready to learn something new today? Let's go!
                              </p>
                        </div>
                  </div>

                  <!-- Quick Action Cards -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">

                        <!-- Practice Past Questions Card -->
                        <div class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer"
                              data-url="../pages/test.php">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-200 flex-shrink-0">
                                    <i class="bx-book-bookmark text-2xl text-white"></i>
                              </div>
                              <div>
                                    <h3 class="text-base font-bold text-gray-800 group-hover:text-orange-600 transition">Practice Past Questions</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Practice questions from previous years</p>
                              </div>
                        </div>

                        <!-- Take Exam Card -->
                        <div class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer"
                              data-url="../pages/exam.php">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg shadow-green-200 flex-shrink-0">
                                    <i class="bx-pencil text-2xl text-white"></i>
                              </div>
                              <div>
                                    <h3 class="text-base font-bold text-gray-800 group-hover:text-green-600 transition">Take Exam</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Start an exam when you're ready</p>
                              </div>
                        </div>
                  </div>

                  <!-- Quick Actions Row -->
                  <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Quick Actions</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                              <button onclick="$('#sideChat').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                                          <i class="bx-message-circle-detail text-lg text-blue-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Chat</span>
                              </button>
                              <button onclick="$('#sideTest').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition">
                                          <i class="bx-book-open text-lg text-orange-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Test</span>
                              </button>
                              <button onclick="$('#sideStudy').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition">
                                          <i class="bx-book-library text-lg text-green-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Study</span>
                              </button>
                              <button onclick="$('#profile').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                                          <i class="bx-cog text-lg text-purple-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Profile</span>
                              </button>
                        </div>
                  </div>

                  <!-- Content: News + Messages side by side on desktop -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- Latest News / Blog -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                              <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                          <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                                                <i class="bx-news text-orange-600"></i>
                                          </div>
                                          <h4 class="text-sm font-bold text-gray-800">Latest News</h4>
                                    </div>
                                    <span class="text-xs text-gray-400"><?= count($blogs_list) ?> posts</span>
                              </div>
                              <?php if (count($blogs_list) > 0): ?>
                                    <div class="space-y-3 max-h-72 overflow-y-auto">
                                          <?php foreach (array_slice($blogs_list, 0, 5) as $blog): ?>
                                                <div class="blog_post flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition cursor-pointer group"
                                                      data-url="/school_app/student/blog/post.php?post_id=<?= $blog->id ?>">
                                                      <div class="min-w-0 flex-1">
                                                            <p class="text-[11px] font-semibold text-blue-500 uppercase tracking-wide"><?= htmlspecialchars($blog->blog_category ?? '') ?></p>
                                                            <p class="text-sm font-semibold text-gray-700 truncate group-hover:text-gray-900 transition"><?= htmlspecialchars($blog->blog_title) ?></p>
                                                            <p class="text-xs text-gray-400 mt-0.5">
                                                                  <?php
                                                                  $date = new DateTime($blog->posted_at);
                                                                  echo $date->format('d F Y');
                                                                  ?>
                                                            </p>
                                                      </div>
                                                      <?php if (!empty($blog->blog_image)): ?>
                                                            <img src="../uploads/blogs/<?= $blog->blog_image ?>" alt="<?= htmlspecialchars($blog->blog_title) ?>"
                                                                  class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
                                                      <?php endif ?>
                                                </div>
                                          <?php endforeach; ?>
                                    </div>
                              <?php else: ?>
                                    <div class="text-center py-8">
                                          <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                                <i class="bx-news text-gray-400 text-xl"></i>
                                          </div>
                                          <p class="text-sm text-gray-400">No news yet</p>
                                    </div>
                              <?php endif ?>
                        </div>

                        <!-- Recent Messages -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                              <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="bx-bell text-blue-600"></i>
                                          </div>
                                          <h4 class="text-sm font-bold text-gray-800">Recent Messages</h4>
                                    </div>
                                    <?php if ($unread_messages > 0): ?>
                                          <span class="text-xs bg-red-100 text-red-600 font-bold px-2 py-0.5 rounded-full"><?= $unread_messages ?> unread</span>
                                    <?php else: ?>
                                          <span class="text-xs text-gray-400"><?= count($results) ?> messages</span>
                                    <?php endif ?>
                              </div>
                              <?php if (count($results) > 0): ?>
                                    <div class="space-y-3 max-h-72 overflow-y-auto">
                                          <?php foreach (array_slice($results, 0, 5) as $msg): ?>
                                                <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                                                      <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                            <i class="bx-envelope text-green-600 text-sm"></i>
                                                      </div>
                                                      <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-gray-700 truncate"><?= htmlspecialchars($msg->subject ?? 'No Subject') ?></p>
                                                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($msg->username ?? '') ?><?php if (!empty($msg->created_at)): ?> • <?= date('M j', strtotime($msg->created_at)) ?><?php endif ?></p>
                                                      </div>
                                                </div>
                                          <?php endforeach ?>
                                    </div>
                              <?php else: ?>
                                    <div class="text-center py-8">
                                          <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                                <i class="bx-envelope text-gray-400 text-xl"></i>
                                          </div>
                                          <p class="text-sm text-gray-400">No messages yet</p>
                                    </div>
                              <?php endif ?>
                        </div>
                  </div>

            </div>
      </div>
</main>

<script>
      $(document).on('click', '.blog_post', function (e) {
            e.preventDefault();

            const url = $(this).data('url');
            // Extract post_id from the data-url query string
            const postId = new URL(url, window.location.origin).searchParams.get('post_id');

            $('#mainContent').fadeOut(200, function () {
                  $.ajax({
                        url: '/school_app/student/blog/post.php',
                        type: 'GET',
                        data: { post_id: postId },
                        success: function (response) {
                              $('#mainContent').html(response).fadeIn(300);
                        },
                        error: function () {
                              $('#mainContent').html('<p class="p-8 text-red-500">Failed to load post.</p>').fadeIn(300);
                        }
                  });
            });
      });
</script>

<?php require '../components/footer.php'; ?>
<?php require '../components/notification.php'; ?>

<!-- greetings Modal -->
<?php if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true): ?>
<div class="w-full h-screen bg-black/90 flex items-center justify-center fixed top-0 left-0 z-[99999] backdrop-blur-md"
      id="greetingsModal">
      <!-- msg container -->
      <div class="relative rounded-2xl bg-white p-6 shadow-md border-gray-200/80 w-[450px] max-w-[95%] backdrop-blur-sm fade-in-bottom" id="greetingMsgContainer">
            <!-- close button -->
             <button type="button" class="bg-red-500 hover:bg-red-600 transition cursor-pointer flex items-center justify-center rounded-full p-1.5 hover:shadow-md top-4 right-4 absolute" onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                        <i class="bx-x text-2xl text-white"></i>
                  </button>
            <!-- msg box -->
            <div class="flex flex-col gap-4">
                  <div class="flex flex-col items-center gap-2">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                              <i class="bx-party text-4xl text-blue-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Welcome to <?= strtoupper(explode(' ', $result->school_name ?? 'SCHOOL')[0])?> Portal</h3>
                  </div>
                  
                  <p class="text-lg text-gray-500 text-center leading-relaxed">
                        Hey <span class="text-blue-600 font-bold"><?= ucfirst($_SESSION['first_name']) ?></span>! 🌟 <br>
                        We're excited to have you back. Here's your mission for today:
                  </p>

                  <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100 flex flex-col items-center gap-1">
                              <i class="bx-book-open text-blue-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Practice</span>
                        </div>
                        <div class="bg-green-50/50 p-3 rounded-xl border border-green-100 flex flex-col items-center gap-1">
                              <i class="bx-pencil text-green-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Exams</span>
                        </div>
                        <div class="bg-orange-50/50 p-3 rounded-xl border border-orange-100 flex flex-col items-center gap-1">
                              <i class="bx-news text-orange-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Stories</span>
                        </div>
                        <div class="bg-purple-50/50 p-3 rounded-xl border border-purple-100 flex flex-col items-center gap-1">
                              <i class="bx-message-rounded-dots text-purple-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Chat</span>
                        </div>
                  </div>

                  <button type="button" 
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-blue-700 hover:shadow-blue-200 transition-all cursor-pointer"
                        onclick="enterAppMode(); document.getElementById('greetingsModal').classList.add('hidden')">
                        Let's Get Started!
                  </button>
                  
                  <p class="text-[11px] text-gray-400 text-center italic">Don't forget to check your messages for latest updates!</p>
            </div>
      </div>
</div>
<?php unset($_SESSION['show_welcome']); ?>
<?php endif; ?>

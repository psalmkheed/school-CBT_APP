<?php
require '../../connections/db.php';
$stmt = $conn->prepare('SELECT * FROM categories');
$stmt->execute();
$category = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<div class="w-full p-4 md:p-8 min-h-screen">
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                  <button onclick="goHome()"
                        class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-green-700 hover:border-green-200 hover:bg-green-50 transition-all cursor-pointer"
                        title="Go back" data-tippy-content="Back to Dashboard">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
                  <div class="flex items-center gap-2">
                        <div class="size-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                              <i class="bx-news text-orange-600"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-800">Blog Management</h3>
                  </div>
            </div>
      </div>

      <div class="w-full fadeIn">
            <div class="flex items-center gap-2 mb-4">
                  <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                        <i class="bx-edit text-purple-600"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800">Create New Blog Post</h3>
            </div>
            
            <form id="blogForm" enctype="multipart/form-data">
                  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col gap-6">
                        
                        <div class="flex flex-col gap-2">
                              <label class="text-xs font-bold text-gray-500 uppercase tracking-widest">Featured Image</label>
                              <input type="file" name="blog_image"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-green-50 file:text-green-600 hover:file:bg-green-100">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div class="flex flex-col gap-2">
                                    <label for="blog_title" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Blog Title</label>
                                    <input type="text" name="blog_title" id="blog_title"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition"
                                          placeholder="Enter blog title">
                              </div>
                              <div class="flex flex-col gap-2">
                                    <label for="blog_category" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Category</label>
                                    <select name="blog_category" id="blog_category"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white cursor-pointer">
                                          <option value="" disabled selected>Select category</option>
                                          <?php foreach ($category as $cat): ?>
                                                <option value="<?= $cat->blog_category ?>"><?= $cat->blog_category ?></option>
                                          <?php endforeach ?>
                                    </select>
                              </div>
                        </div>

                        <div class="flex flex-col gap-2 w-full">
                              <label for="blog_message" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Blog Content</label>
                              <textarea name="blog_message" id="blog_message" placeholder="Write your blog..." style="display:none;"></textarea>
                        </div>

                        <button type="submit"
                              class="w-full md:w-max px-10 py-3.5 bg-green-600 text-white rounded-xl hover:bg-green-500 hover:shadow-lg transition-all duration-300 font-bold text-sm cursor-pointer ml-auto"
                              id="blogBtn">
                              <i class="bx-upload mr-2 text-lg"></i>
                              Publish Blog Post
                        </button>
                  </div>
            </form>
      </div>
</div>


<script>
      // Blog creating script

      $('#blogForm').off('submit').on('submit', function (e) {
            e.preventDefault();

            
            if (window.blogEditor) {
                  const editorData = window.blogEditor.getData();
                  $('#blog_message').val(editorData);
            }

            let formData = new FormData(this);
            $.ajax({
                  url: '/school_app/admin/auth/blog.php',
                  type: 'POST',
                  data: formData,
                  dataType: 'json',
                  processData: false,
                  contentType: false,
                  beforeSend: function () {
                        $('#blogBtn').prop('disabled', true).html('<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>');
                  },
                  success: function (res) {
                        if (res.status == 'success') {
                              showAlert('success', res.message);
                              $('#blogForm')[0].reset();
                             
                              if (window.blogEditor) {
                                    window.blogEditor.setData('');
                              }
                        } else {
                              showAlert('error', res.message)
                        }
                  }, error: function () {
                        showAlert('error', 'Failed to publish blog');
                  },
                  complete: function () {
                        $('#blogBtn').prop('disabled', false).html('<i class="bx-upload mr-2 text-lg"></i> Publish Blog Post');
                  }
            });
      });

</script>
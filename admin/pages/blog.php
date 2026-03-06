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
                  <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-orange-100 flex items-center justify-center shrink-0 shadow-sm border border-orange-200">
                              <i class="bx bx-news text-orange-600 text-2xl"></i>
                        </div>
                        <div>
                              <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Blog Management</h3>
                              <p class="text-sm text-gray-400 font-medium">Create posts and manage existing ones</p>
                        </div>
                  </div>
            </div>
            
            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                <div class="flex p-1.5 bg-gray-100/50 rounded-2xl border border-gray-100 w-full md:w-auto overflow-x-auto gap-2">
                    <button class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group bg-white shadow-sm border border-gray-100" id="composeBtn">
                        <i class="bx bx-edit text-lg text-emerald-500 group-hover:scale-110 transition-transform"></i>
                        <span class="text-emerald-600">Compose</span>
                    </button>
                    <button class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group border border-transparent hover:bg-white/50" id="manageBtn">
                        <i class="bx bx-history text-lg text-purple-500 group-hover:scale-110 transition-transform"></i>
                        <span class="text-gray-600">History</span>
                    </button>
                </div>
            </div>
      </div>

      <div id="composeSection" class="w-full fadeIn">
            <div class="flex items-center gap-2 mb-4">
                  <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                        <i class="bx-edit text-purple-600"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800">Create New Blog Post</h3>
            </div>
            
            <form id="blogForm" enctype="multipart/form-data">
                  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col gap-6">
                        
                        <div class="flex flex-col gap-2">
                              <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Featured Image</label>
                              <input type="file" name="blog_image"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-600 hover:file:bg-emerald-100 cursor-pointer">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div class="flex flex-col gap-2">
                                    <label for="blog_title" class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Blog Title</label>
                                    <input type="text" name="blog_title" id="blog_title"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition shadow-sm"
                                          placeholder="Enter blog title">
                              </div>
                              <div class="flex flex-col gap-2">
                                    <label for="blog_category" class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Category</label>
                                    <select name="blog_category" id="blog_category"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition bg-white cursor-pointer shadow-sm">
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
                              class="w-full md:w-max px-10 py-3.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 hover:shadow-lg shadow-emerald-100 transition-all duration-300 font-bold text-sm cursor-pointer ml-auto flex items-center justify-center gap-2"
                              id="blogBtn">
                              <i class="bx-send text-lg"></i>
                              Publish Blog Post
                        </button>
                  </div>
            </form>
            </div>

      <div id="manageSection" class="w-full fadeIn hidden">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
              <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                          <i class="bx bx-history text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Blog History</h3>
              </div>
              <div class="flex items-center gap-3">
                  <div class="relative w-full md:w-64 group">
                      <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors"></i>
                      <input type="text" id="blogSearch" 
                          class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition shadow-sm"
                          placeholder="Search blogs...">
                  </div>
              </div>
          </div>
          
          <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
              <div class="overflow-x-auto">
                    <table id="blogTable" class="w-full text-left border-collapse">
                          <thead class="bg-gray-50/80 border-b border-gray-100">
                                <tr>
                                      <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Title</th>
                                      <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Category</th>
                                      <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Image</th>
                                      <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Posted At</th>
                                      <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                          </thead>
                          <tbody id="blogBody" class="divide-y divide-gray-50">
                                <!-- Loaded via AJAX -->
                          </tbody>
                    </table>
              </div>
          </div>
      </div>
</div>


<script>
      // Tab switching
      function switchTab(showId, hideId, activeBtn, inactiveBtn, activeClasses, inactiveClasses) {
            $("#" + hideId).addClass("hidden");
            $("#" + showId).removeClass("hidden");
            
            $(inactiveBtn).removeClass(activeClasses).addClass(inactiveClasses)
                          .find("span").removeClass("text-emerald-600 text-purple-600").addClass("text-gray-600");
            $(activeBtn).removeClass(inactiveClasses).addClass(activeClasses);
      }

      $("#composeBtn").on("click", function() {
            switchTab("composeSection", "manageSection", this, "#manageBtn", "bg-white shadow-sm border-gray-100", "border-transparent hover:bg-white/50");
            $(this).find("span").removeClass("text-gray-600").addClass("text-emerald-600");
      });

      $("#manageBtn").on("click", function() {
            switchTab("manageSection", "composeSection", this, "#composeBtn", "bg-white shadow-sm border-gray-100", "border-transparent hover:bg-white/50");
            $(this).find("span").removeClass("text-gray-600").addClass("text-purple-600");
            loadBlogs();
      });

      function loadBlogs() {
            $("#blogBody").html('<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400"><i class="bx bx-loader-dots bx-spin animate-spin text-2xl"></i></td></tr>');
            $.ajax({
                  url: '/school_app/admin/auth/fetch_blogs.php',
                  type: 'GET',
                  success: function(html) {
                        $("#blogBody").html(html);
                        window.initTableToolkit({
                            searchId: 'blogSearch',
                            tableId: 'blogTable',
                            bodyId: 'blogBody'
                        });
                        bindBlogActions();
                  }
            });
      }

      function bindBlogActions() {
          $('.delete-blog-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const row = $(this).closest('tr');
                
                Swal.fire({
                      title: 'Delete Blog?',
                      text: "The blog and its image will be permanently removed.",
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonColor: '#d33',
                      cancelButtonColor: '#3085d6',
                      confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                      if (result.isConfirmed) {
                            $.post('/school_app/admin/auth/delete_blog.php', { id: id }, function(res) {
                                  if (res.success) {
                                        row.fadeOut(300, function() { $(this).remove(); });
                                        Swal.fire('Deleted!', 'Blog post deleted.', 'success');
                                  } else {
                                        Swal.fire('Error', res.message, 'error');
                                  }
                            });
                      }
                });
          });

          $('.edit-blog-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                
                $.post('/school_app/admin/auth/get_blog.php', { id: id }, function(res) {
                      if (res.success) {
                            const b = res.data;
                            let catOptions = '';
                            <?php foreach ($category as $cat): ?>
                                          catOptions += `<option value="<?= htmlspecialchars($cat->blog_category) ?>"><?= htmlspecialchars($cat->blog_category) ?></option>`;
                                    <?php endforeach; ?>
                                    Swal.fire({
                                          title: 'Edit Blog',
                                          width: '800px',
                                          html: `
                                        <form id="editBlogForm" class="flex flex-col gap-3 text-left w-full mt-4">
                                            <input type="hidden" name="blog_id" value="${b.id}">
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Title</label>
                                            <input name="blog_title" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" value="${b.blog_title.replace(/"/g, '&quot;')}">
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-2">Category</label>
                                            <select name="blog_category" id="editCategory" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                                                  ${catOptions}
                                            </select>
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-2">New Featured Image (Optional)</label>
                                            <input type="file" name="blog_image" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gray-50 file:text-gray-700">
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-2">Content</label>
                                            <textarea id="edit_blog_content" name="blog_message" style="display:none;"></textarea>
                                        </form>
                                  `,
                                          didOpen: () => {
                                                const contentElement = document.querySelector('#edit_blog_content');
                                                $('#editCategory').val(b.blog_category);

                                                console.log('Modal Opened - Checking for ClassicEditor:', window.ClassicEditor);
                                                console.log('Modal Opened - Editor Config:', window.editorConfig);

                                                if (window.ClassicEditor) {
                                                      window.ClassicEditor.create(contentElement, window.editorConfig || {})
                                                            .then(editor => {
                                                                  window.tempEditBlog = editor;
                                                                  if (b.blog_message) {
                                                                        editor.setData(b.blog_message);
                                                                  }
                                                            })
                                                            .catch(err => console.error('Modal CKEditor Error:', err));
                                                } else {
                                                      console.error('Fatal: window.ClassicEditor is MISSING from the window object.');
                                                }
                                          },
                                          showCancelButton: true,
                                          confirmButtonText: 'Save Changes',
                                          confirmButtonColor: '#10b981',
                                          preConfirm: () => {
                                                if (window.tempEditBlog) {
                                                      $('#edit_blog_content').val(window.tempEditBlog.getData());
                                                }
                                                return new FormData(document.getElementById('editBlogForm'));
                                          }
                                    }).then((result) => {
                                          if (result.isConfirmed) {
                                                $.ajax({
                                                      url: '/school_app/admin/auth/update_blog.php',
                                                      type: 'POST',
                                                      data: result.value,
                                                      processData: false,
                                                      contentType: false,
                                                      success: function (upd) {
                                                            if (upd.status === 'success') {
                                                                  Swal.fire('Saved!', 'Blog updated successfully.', 'success');
                                                                  loadBlogs();
                                                            } else {
                                                                  Swal.fire('Error', upd.message, 'error');
                                                            }
                                                      }
                                                });
                                          }
                                    });
                              } else {
                                    Swal.fire('Error', res.message || 'Failed to load blog.', 'error');
                              }
                        });
                  });
            }

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
                        $('#blogBtn').prop('disabled', false).html('<i class="bx-send mr-2 text-lg"></i> Publish Blog Post');
                  }
            });
      });

</script>
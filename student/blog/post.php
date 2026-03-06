<?php
session_start();
require '../../components/header.php';
require '../../connections/db.php';

if (isset($_GET['post_id'])) {

      $id = (int) $_GET['post_id'];

      /** @var \PDOStatement $select */
      $select = $conn->prepare("SELECT * FROM blog WHERE id = :id");
      $select->bindValue(':id', $id, PDO::PARAM_INT);
      $select->execute();

      /** @var object $post */
      $post = $select->fetch(PDO::FETCH_OBJ);

      if (!$post) {
            echo "<p class='text-4xl p-8 text-red-500'>Post not found.</p>";
            exit;
      }

} else {
      echo '<div class="relative flex items-center justify-center fixed h-screen w-full bg-black z-[99999]">
      <h1 class="absolute text-[10rem] text-gray-900 font-black">401</h1>
      <h1 class="animate-pulse text-3xl text-white font-bold"> Unauthorized Access </h1>
      
      
      </div>';
      exit;
}

?>
      <div class="relative">
            <header class="masthead rounded-br-md rounded-bl-md"
                  style="background-image: url('/school_app/uploads/blogs/<?= $post->blog_image ?>'); background-size:cover">
            </header>
            <div class="absolute top-4 left-4 md:top-6 md:left-6 z-50">
                  <button onclick="goHome()"
                        class="size-12 rounded-md bg-white backdrop-blur-md border border-white/30 flex items-center justify-center text-gray-400 hover:bg-gray-200 transition-all shadow-sm cursor-pointer"
                        data-tippy-content="Back to Dashboard">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
            </div>
      </div>

      <div class='max-w-5xl mx-auto px-6 md:px-12 mb-12 mt-10'>
            <div class="mb-8">
                  <h1 class="text-3xl md:text-4xl text-blue-700 font-extrabold mb-4 leading-tight"><?= $post->blog_title ?></h1>
                  <div class="flex items-center gap-4 mb-6">
                        <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                              <?= $post->blog_category ?>
                        </span>
                        <p class="text-sm text-gray-500 font-medium flex items-center gap-1">
                              <i class="bx bx-calendar text-base"></i>
                              <?php
                              $date = new DateTime($post->posted_at);
                              echo $date->format('d F Y');
                              ?>
                        </p>
                  </div>
            </div>
            
            <div class="ck-content w-full text-gray-700 text-lg leading-relaxed space-y-4" id="ck-content">
                  <?= $post->blog_message ?>
            </div>
      </div>

</div>

<!-- show ads on every page -->
<script>
      document.addEventListener("DOMContentLoaded", function () {

            const blog = document.getElementById("ck-content");
            if (!blog) return;

            const paragraphs = blog.querySelectorAll("p");

            const ads = [
                  {
                        position: 3,
                        html: `
                <div class="inline-ad">
                    <a href="https://example.com/ad1" target="_blank">
                        <img src="https://picsum.photos/728/90?random=1" alt="Ad 1">
                    </a>
                </div>`
                  },
                  {
                        position: 6,
                        html: `
                <div class="inline-ad">
                    <a href="https://example.com/ad2" target="_blank">
                        <img src="https://picsum.photos/300/250?random=2" alt="Ad 2">
                    </a>
                </div>`
                  },
                  {
                        position: 10,
                        html: `
                <div class="inline-ad">
                    <a href="https://example.com/ad3" target="_blank">
                        <img src="https://picsum.photos/728/90?random=3" alt="Ad 3">
                    </a>
                </div>`
                  },
                     {
                        position: 16,
                        html: `
                <div class="inline-ad" style="background-color: #f3f3f3;">
                
                <ul style="color: black; list-style: none;">
                <h6 style="color: gray; text-align: center; margin-bottom: 10px; text-transform:uppercase; font-size: 10px;">Advertisement</h3>
                <a href="https://doestdot.sch.ng"><li>Do-Estdot International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Meiran International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Alaagba International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Abesan International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Isale Eko International School</li></a>
                                
                </ul>
      
                </div>`
                  },
                     {
                        position: 25,
                        html: `
                <div class="inline-ad" style="background-color: #f3f3f3;">
                
                <ul style="color: black; list-style: none;">
                <h6 style="color: gray; text-align: center; margin-bottom: 10px; text-transform:uppercase; font-size: 10px;">Advertisement</h3>
                <a href="https://doestdot.sch.ng"><li>Do-Estdot International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Meiran International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Alaagba International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Abesan International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Isale Eko International School</li></a>
                                
                </ul>
      
                </div>`
                  } ,
                     {
                        position: 40,
                        html: `
                <div class="inline-ad" style="background-color: #f3f3f3;">
                
                <ul style="color: black; list-style: none;">
                <h6 style="color: gray; text-align: center; margin-bottom: 10px; text-transform:uppercase; font-size: 10px;">Advertisement</h3>
                <a href="https://doestdot.sch.ng"><li>Do-Estdot International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Meiran International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Alaagba International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Abesan International School</li></a>
                <hr>
                <a href="https://doestdot.sch.ng"><li>Isale Eko International School</li></a>
                                
                </ul>
      
                </div>`
                  },
                  {
                        position: 55,
                        html: `
                <div class="inline-ad">
                    <a href="https://example.com/ad2" target="_blank">
                        <img src="https://picsum.photos/300/250?random=2" alt="Ad 2">
                    </a>
                </div>`
                  }
            ];

            ads.forEach(ad => {
                  if (paragraphs.length >= ad.position) {
                        paragraphs[ad.position - 1].insertAdjacentHTML('afterend', ad.html);
                  }
            });

      });
</script>
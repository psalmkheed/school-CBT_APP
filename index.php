<?php

require __DIR__ . '/connections/db.php';
// APP_URL is already defined in connections/db.php
$base = APP_URL;


// redirect to login page if school settings has been configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();

if ($check_config->rowCount() > 0) {
      header("Location: {$base}auth/splash.php");
      exit();
}
;
// -------------------------------------------------------------//


$dev_name = '<p class="absolute bottom-0 left-0 right-0 text-center text-sm text-gray-700 mb-2">' . date('Y') . "&copy; App Developed by <a href='mailto:[psalmkheed123@gmail.com]'>@BlaqDev</a> </p>";
?>

<!DOCTYPE html>
<html lang="en">

<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>School Configuration</title>
      <link href="<?= $base ?>src/output.css" rel="stylesheet">
      <link href="<?= $base ?>src/input.css" rel="stylesheet">
      <link href="<?= $base ?>src/fontawesome.css" rel="stylesheet">
      <link href="<?= $base ?>src/swiper.css" rel="stylesheet">
      <link href="<?= $base ?>src/boxicons.css" rel="stylesheet">
      <script src="<?= $base ?>src/jquery.js"></script>
      <script src="<?= $base ?>src/swiper-bundle.js"></script>
      <script src="<?= $base ?>src/sweetAlert.js"></script>
      <script src="<?= $base ?>src/scripts.js"></script>
</head>

<body class="bg-gray-50 select-none">

      <div class="fadeIn w-full">

            <div class="flex flex-col gap-2 items-center justify-center h-screen" id="">
                  <div class="flex items-center gap-1 md:text-3xl text-2xl text-red-700 font-bold z-50">
                        <i class="bx-cog"></i>
                        <h1 class="">School Configuration</h1>
                  </div>
                  <h3 class="text-red-700 animate-bounce z-50">Note: <span class=" text-gray-700">Fill in all details to
                              configure your school settings</span></h3>

                  <form class="relative md:w-200 bg-white border border-gray-100 shadow-xl rounded-2xl overflow-hidden"
                        id="configForm" enctype="multipart/form-data">
                        <div class="absolute size-12 rounded-full bg-gray-50 -top-[5%] -left-5"></div>
                        <div class="absolute size-12 rounded-full bg-gray-50 -top-[9%] right-[50%] left-[50%] "></div>
                        <div class="absolute size-12 rounded-full bg-gray-50 -top-[5%] -right-5 "></div>
                        <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                              <div class="flex items-center justify-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                          <i class="bx-cog text-red-600"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-800">Enter Your School Details</h3>
                              </div>
                        </div>

                        <div class="p-6 flex flex-col gap-5">
                              <div class="flex flex-col gap-1.5">
                                    <label for="school_logo" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">School Logo</label>
                                    <input type="file" name="school_logo" id="school_logo"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-600 hover:file:bg-red-100 cursor-pointer">
                              </div>

                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                          <label for="school_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">School Name</label>
                                          <input type="text" name="school_name" id="school_name"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition"
                                                placeholder="Enter school name">
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                          <label for="school_tagline" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">School Tagline</label>
                                          <input type="text" name="school_tagline" id="school_tagline"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition"
                                                placeholder="Enter school tagline">
                                    </div>
                              </div>

                              <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                          <label for="school_primary_color" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Primary Color</label>
                                          <input type="color" name="school_primary_color" id="school_primary_color"
                                                class="w-full border border-gray-200 rounded-xl p-1.5 h-11 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition cursor-pointer">
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                          <label for="school_secondary_color" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Secondary Color</label>
                                          <input type="color" name="school_secondary_color" id="school_secondary_color"
                                                class="w-full border border-gray-200 rounded-xl p-1.5 h-11 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition cursor-pointer">
                                    </div>
                              </div>

                              <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-700 text-white rounded-xl hover:bg-red-800 transition-all duration-200 font-semibold text-sm cursor-pointer"
                                    id="configBtn">Submit</button>
                        </div>
                  </form>
                  <?= $dev_name ?>
            </div>


      </div>
      <script>


            $("#configForm").off('submit').on('submit', function (e) {
                  e.preventDefault();

                  let formData = new FormData(this);

                  $.ajax({
                        url: 'auth/school_config.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        processData: false,
                        contentType: false,
                        beforeSend: function () {
                              $('#configBtn').prop('disabled', true).html('<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>');
                        },
                        success: function (res) {
                              if (res.status == 'success') {
                                    showAlert('success', res.message);
                                    $('#configForm')[0].reset();

                                    window.location.href = APP_URL + 'auth/login.php';
                              } else {
                                    showAlert('error', res.message)
                              }
                        }, error: function () {
                              showAlert('error', 'Failed to save configuration');
                        },
                        complete: function () {
                              $('#configBtn').prop('disabled', false).html('Submit');
                        }
                  });
            })
      </script>

</body>



</html>

<?php

require '../../connections/db.php';

$class_populate = $conn->prepare("SELECT class FROM class");
$class_populate->execute();

$class_rows = $class_populate->fetchAll(PDO::FETCH_OBJ);

$subject_populate = $conn->prepare("SELECT subject FROM subjects");
$subject_populate->execute();

$subject_rows = $subject_populate->fetchAll(PDO::FETCH_OBJ);

$user_populate = $conn->prepare("SELECT * FROM users WHERE role = 'staff'");
$user_populate->execute();

$user_rows = $user_populate->fetchAll(PDO::FETCH_OBJ);
?>
<div class="w-full p-4 md:p-6">
      <!-- container header -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                  <button onclick="goHome()"
                        class="md:hidden w-10 h-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-green-700 hover:border-green-200 hover:bg-green-50 transition-all cursor-pointer"
                        title="Go back">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                              <i class="bx-book-open text-green-600"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-800">Examinations</h3>
                  </div>
            </div>
            <div class="flex flex-wrap gap-2 items-center w-full lg:w-auto">
                  <!-- add subject btn -->
                  <button
                        class="group bg-orange-100 text-orange-600 p-2 rounded-md hover:bg-orange-200 transition-all duration-800 cursor-pointer flex gap-2 items-center"
                        id="addNewSubject">
                        <i class="bx bx-book-add text-lg hidden md:block"></i>
                        <p class="text-sm">Add Subject</p>
                  </button>
                  <!-- create exam btn -->
                  <button
                        class="group bg-green-100 text-green-600 p-2 rounded-md hover:bg-green-200 transition-all duration-800 cursor-pointer flex gap-2 items-center"
                        id="createNewExam">
                        <i class="bx bx-book-add text-lg hidden md:block"></i>
                        <p class="text-sm">Create Exam</p>
                  </button>

            </div>


      </div>
      <hr class="border-b border-gray-100 my-4">

      <!-- Populate exam table -->
      <div class="fadeIn w-full overflow-x-auto bg-white rounded-xl shadow">

            <table class="md:min-w-[900px] w-full text-sm text-left text-gray-700">

                  <!-- HEADER -->
                  <thead class="bg-green-100 text-green-700 uppercase text-xs tracking-wider sticky top-0">
                        <tr>
                              <th class="px-4 py-3">#</th>
                              <th class="px-4 py-3">Class</th>
                              <th class="px-4 py-3">Subject</th>
                              <th class="px-4 py-3">Questions</th>
                              <th class="px-4 py-3 hidden md:table-cell">Exam Type</th>
                              <th class="px-4 py-3 hidden md:table-cell">Paper Type</th>
                              <th class="px-4 py-3 hidden md:table-cell">Duration</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Due Date</th>
                              <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                  </thead>

                  <!-- BODY -->
                  <tbody class="divide-y divide-gray-200" id="examTable"></tbody>

            </table>
      </div>

</div>

<!-- Add subject Modal -->
<div class="hidden fixed inset-0 bg-black/90 flex items-center justify-center p-2 z-[99999] backdrop-blur-md"
      id="subjectModal">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden fadeIn">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                              <i class="bx-book-add text-orange-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Add New Subject</h3>
                  </div>
                  <button type="button" class="text-gray-400 hover:text-gray-600 transition cursor-pointer" onclick="document.getElementById('subjectModal').classList.add('hidden')">
                        <i class="bx-x text-2xl"></i>
                  </button>
            </div>

            <!-- Modal Body -->
            <form id="add-subject-form" class="p-6 flex flex-col gap-5">
                  <div class="flex flex-col gap-1.5">
                        <label for="subject" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</label>
                        <input type="text" id="subject" name="subject"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition"
                              placeholder="E.g. Chemistry, Commerce...">
                  </div>
                  <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 transition-all duration-200 font-semibold text-sm cursor-pointer"
                        id="subjectBtn">Add Subject</button>
            </form>
      </div>
</div>


<!-- ------------------------------ -->

<script src="/school_app/src/modal.js"></script>


<!-- Create Exam Modal -->
<div class="hidden fixed inset-0 bg-black/90 flex items-center justify-center z-[99999] backdrop-blur-md" id="examModal">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden fadeIn max-h-[90vh] overflow-y-auto">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 sticky top-0 z-10">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                              <i class="bx-book-add text-green-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Create New Exam</h3>
                  </div>
                  <button type="button" class="text-gray-400 hover:text-gray-600 transition cursor-pointer" onclick="document.getElementById('examModal').classList.add('hidden')">
                        <i class="bx-x text-2xl"></i>
                  </button>
            </div>

            <!-- Modal Body -->
            <form class="p-6 flex flex-col gap-5" id="create-exam-form">

                  <!-- Class & Subject -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="class" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</label>
                              <select name="class" id="class"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option disabled selected>Select Class</option>
                                    <?php foreach ($class_rows as $class): ?>
                                          <option value="<?= $class->class ?>"><?= $class->class ?></option>
                                    <?php endforeach ?>
                              </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="subject" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</label>
                              <select name="subject" id="subject"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option disabled selected>Select Subject</option>
                                    <?php foreach ($subject_rows as $subject): ?>
                                          <option value="<?= $subject->subject ?>"><?= $subject->subject ?></option>
                                    <?php endforeach ?>
                              </select>
                        </div>
                  </div>

                  <!-- Questions & Duration -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="num_of_question" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Number of Questions</label>
                              <select name="num_of_question" id="num_of_question"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option value="" disabled selected>Select amount</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                    <option value="35">35</option>
                                    <option value="40">40</option>
                                    <option value="45">45</option>
                                    <option value="50">50</option>
                                    <option value="55">55</option>
                                    <option value="60">60</option>
                                    <option value="65">65</option>
                                    <option value="70">70</option>
                                    <option value="75">75</option>
                                    <option value="80">80</option>
                                    <option value="85">85</option>
                                    <option value="90">90</option>
                                    <option value="95">95</option>
                                    <option value="100">100</option>
                              </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="time_allowed" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Duration (Minutes)</label>
                              <input type="text" placeholder="E.g. 10, 20, 30" name="time_allowed" id="time_allowed"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                        </div>
                  </div>

                  <!-- Teacher & Due Date -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="subject_teacher" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject Teacher</label>
                              <select name="subject_teacher" id="subject_teacher"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option disabled selected value="">Select Teacher</option>
                                    <?php foreach ($user_rows as $staff): ?>
                                          <option value="<?= $staff->first_name . ' ' . $staff->last_name ?>">
                                                <?= $staff->first_name . ' ' . $staff->last_name ?>
                                          </option>
                                    <?php endforeach ?>
                              </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="due_date" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Due Date</label>
                              <input type="date" name="due_date" id="due_date"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                        </div>
                  </div>

                  <!-- Exam Type & Paper Type -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="exam_type" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Exam Type</label>
                              <select name="exam_type" id="exam_type"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option value="" disabled selected>Select Exam Type</option>
                                    <option value="Mid-Term">Mid-Term</option>
                                    <option value="Mock Examination">Mock Examination</option>
                                    <option value="Examination">Examination</option>
                                    <option value="Entrance Test">Entrance Test</option>
                                    <option value="Common Entrance">Common Entrance</option>
                              </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="paper_type" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Paper Type</label>
                              <select name="paper_type" id="paper_type"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option value="" disabled selected>Select paper type</option>
                                    <option value="Objective">Objective</option>
                                    <option value="Theory">Theory</option>
                              </select>
                        </div>
                  </div>

                  <button type="submit" name="create_exam" id="create_exam_btn"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 transition-all duration-200 font-semibold text-sm cursor-pointer">
                        Create Exam
                  </button>
            </form>
      </div>
</div>
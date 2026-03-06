<?php
require '../../connections/db.php';

$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$limit = 10;

if ($page < 1)
      $page = 1;

$offset = ($page - 1) * $limit;

/* FETCH DATA */
$stmt = $conn->prepare("
    SELECT * FROM exams
    ORDER BY id asc
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$exams = $stmt->fetchAll(PDO::FETCH_OBJ);

/* COUNT TOTAL */
$totalRecords = $conn->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$totalPages = ceil($totalRecords / $limit);
?>

<!-- TABLE ROWS -->
<?php $i = 0; foreach ($exams as $exam): ?>
      <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">
                  <?= ++$i ?>
            </td>
            <td class="px-4 py-3">
                  <?= $exam->class ?>
            </td>
            <td class="px-4 py-3">
                  <?= $exam->subject ?>
            </td>
            <td class="px-4 py-3">
                  <?= $exam->num_quest ?>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
                  <?= $exam->exam_type ?>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
                  <?= $exam->paper_type ?>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
                  <?= $exam->time_allowed ?> mins
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
                  <?= $exam->due_date ?>
            </td>
            <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                        <button 
                              data-id="<?= $exam->id ?>"
                              data-status="<?= $exam->exam_status ?>"
                              data-tippy-content="Change Status: Currently <?= $exam->exam_status ?>"
                              class="status-exam-btn px-3 py-1 text-xs font-semibold rounded-md cursor-pointer
                        <?php
                        if ($exam->exam_status === 'published') {
                              echo 'bg-green-500 text-white hover:bg-green-600';
                        } elseif ($exam->exam_status === 'ready') {
                              echo 'bg-yellow-500 text-white hover:bg-yellow-600';
                        } elseif ($exam->exam_status === 'set up') {
                              echo 'bg-sky-500 text-white hover:bg-sky-600';
                        } else {
                              echo 'bg-gray-200 text-gray-500';
                        }
                        ?>">
                              <?= ucfirst($exam->exam_status) ?>
                        </button>

                        <button 
                              data-id="<?= $exam->id ?>"
                              data-tippy-content="Delete Exam"
                              class="delete-exam-btn px-3 py-1 text-xs font-semibold rounded-md bg-red-500 text-white hover:bg-red-600 cursor-pointer">
                              Delete
                        </button>

                  </div>
            </td>
      </tr>
<?php endforeach; ?>

<!-- PAGINATION -->
<tr>
      <td colspan="9" class="p-4">

            <div class="flex justify-center gap-1">

                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button
                              class="page-btn px-4 py-2 rounded cursor-pointer <?= $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200' ?>"
                              data-page="<?= $i ?>">
                              <?= $i ?>
                        </button>
                  <?php endfor; ?>

            </div>

      </td>
</tr>
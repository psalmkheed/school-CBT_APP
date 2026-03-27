<?php
require '../../connections/db.php';
require '../auth/check.php'; // protect page

$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'staff'");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<style>
      tr:nth-of-type(even) {
            background-color: #f1f1f1;
      }
</style>

<div class="fadeIn w-full flex flex-col gap-4">
    <div class="relative w-full md:w-64 group">
        <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
        <input type="text" id="adminStaffSearch" 
            class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition shadow-sm"
            placeholder="Search staff...">
    </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow" id="staffRecord">
      <table class="md:min-w-[900px] w-full text-sm text-left text-gray-700">
            <thead class="bg-orange-200 text-orange-600 uppercase text-xs tracking-wider sticky top-0">
                  <tr>
                        <th class="p-2">#</th>
                        <th class="p-2">First Name</th>
                        <th class="p-2">Surname</th>
                        <th class="p-2">User ID</th>
                        <th class="p-2">Action</th>
                  </tr>
            </thead>

            <tbody>
                  <?php $i = 0;
                  foreach ($result as $row): ?>
                        <tr class="hover:bg-stone-100">
                              <td class="p-2">
                                    <?= ++$i ?>
                              </td>
                              <td class="p-2"><?= ucfirst($row->first_name) ?></td>
                              <td class="p-2"><?= ucfirst($row->surname) ?></td>
                              <td class="p-2"><?= $row->user_id ?></td>
                              <td class="p-2 flex gap-2">

                                    <button type="button"
                                          class="flex items-center gap-[3px] edit-btn bg-sky-600 hover:bg-sky-500 text-white px-2 py-1 rounded-lg font-semibold text-sm cursor-pointer"
                                          data-id="<?= $row->id ?>" data-tippy-content="Modify staff record">
                                          <i class="bx-pencil"></i>
                                          Edit
                                    </button>
                                    <button type="button"
                                          class="flex items-center gap-[3px] delete-btn bg-red-600 hover:bg-red-500 text-white px-2 py-1 rounded-lg font-semibold text-sm cursor-pointer"
                                          data-id="<?= $row->id ?>" data-tippy-content="Remove staff permanently">
                                          <i class="bx-trash"></i>
                                          Delete
                                    </button>

                              </td>
                        </tr>
                  <?php endforeach; ?>
            </tbody>
      </table>
</div>

<script>
      $(document).off('click', '.delete-btn').on('click', '.delete-btn', function (e) {
            e.preventDefault();

            const btn = $(this);
            const userId = btn.data('id');

            Swal.fire({
                  title: 'Are you sure?',
                  text: 'This staff will be permanently deleted.',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#dc2626', // red
                  cancelButtonColor: '#6b7280',  // gray
                  confirmButtonText: 'Yes, delete',
                  cancelButtonText: 'Cancel'
            }).then((result) => {
                  if (!result.isConfirmed) return;

                  $.ajax({
                        url: 'auth/delete.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { del_id: userId },
                        beforeSend: function () {
                              btn.prop('disabled', true).text('Deleting...');
                        },
                        success: function (res) {
                              if (res.success) {
                                    btn.closest('tr').fadeOut(300, function () {
                                          $(this).remove();
                                    });

                                    Swal.fire({
                                          icon: 'success',
                                          title: 'Deleted!',
                                          text: 'Staff record has been deleted.',
                                          timer: 1500,
                                          showConfirmButton: false
                                    });
                              } else {
                                    btn.prop('disabled', false).text('Delete');

                                    Swal.fire({
                                          icon: 'error',
                                          title: 'Failed',
                                          text: res.message || 'Delete failed'
                                    });
                              }
                        },
                        error: function () {
                              btn.prop('disabled', false).text('Delete');

                              Swal.fire({
                                    icon: 'error',
                                    title: 'Server Error',
                                    text: 'Please try again.'
                              });
                        }
                  });
            });
      });
</script>

<script>
      $(document).off('click', '.edit-btn').on('click', '.edit-btn', function () {

            const btn = $(this);
            const userId = btn.data('id');
            const row = btn.closest('tr');

            $.ajax({
                  url: 'auth/get_staff.php',
                  type: 'POST',
                  dataType: 'json',
                  data: { id: userId },
                  success: function (res) {
                        if (!res.success) {
                              Swal.fire('Error', 'Unable to fetch data', 'error');
                              return;
                        }

                        let subjectsHtml = '<div class="mt-4 text-left"><p class="text-xs font-bold text-gray-400 uppercase mb-2">Assign Subjects</p><div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-gray-50 rounded-xl border border-gray-100">';
                        res.all_subjects.forEach(sub => {
                              const checked = res.assigned_subjects.includes(sub.id.toString()) || res.assigned_subjects.includes(parseInt(sub.id)) ? 'checked' : '';
                              subjectsHtml += `
                                    <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-orange-100">
                                          <input type="checkbox" class="subject-checkbox" value="${sub.id}" ${checked}>
                                          <span class="text-xs font-semibold text-gray-700">${sub.subject}</span>
                                    </label>
                              `;
                        });
                        subjectsHtml += '</div></div>';

                        Swal.fire({
                              title: 'Edit Staff',
                              html: `
                                    <div class="flex flex-col gap-4">
                                          <input id="swal_first" class="swal2-input !m-0" placeholder="First Name" value="${res.data.first_name}">
                                          <input id="swal_last" class="swal2-input !m-0" placeholder="Surname" value="${res.data.surname}">
                                          ${subjectsHtml}
                                    </div>
                              `,
                              confirmButtonColor: '#FF6900',
                              cancelButtonColor: '#6b7280',
                              showCancelButton: true,
                              confirmButtonText: 'Save Changes',
                              width: '450px',
                              preConfirm: () => {
                                    const selectedSubjects = [];
                                    $('.subject-checkbox:checked').each(function() {
                                          selectedSubjects.push($(this).val());
                                    });
                                    return {
                                          id: userId,
                                          first_name: $('#swal_first').val(),
                                          surname: $('#swal_last').val(),
                                          subjects: selectedSubjects,
                                          status: res.data.status
                                    };
                              }
                        }).then((result) => {
                              if (!result.isConfirmed) return;

                              $.ajax({
                                    url: 'auth/update_staff.php',
                                    type: 'POST',
                                    dataType: 'json',
                                    data: result.value,
                                    success: function (update) {
                                          if (!update.success) {
                                                Swal.fire('Failed', update.message, 'error');
                                                return;
                                          }

                                          row.find('td:eq(1)').text(result.value.first_name);
                                          row.find('td:eq(2)').text(result.value.surname);

                                          Swal.fire({
                                                icon: 'success',
                                                title: 'Updated',
                                                text: 'Staff record updated',
                                                timer: 1300,
                                                showConfirmButton: false
                                          });
                                    }
                              });
                        });
                  }
            });

            $('#adminStaffSearch').on('input', function() {
                  const q = $(this).val().toLowerCase();
                  $('tbody tr').each(function() {
                        const t = $(this).text().toLowerCase();
                        $(this).toggle(t.includes(q));
                  });
            });
      });
</script>
</div>
@extends('admin.layouts.admin')

@section('content')

<section class="section dashboard">
  <div class="row">

    <!-- Left side columns -->
    <div class="col-lg-12">
      <div class="row">


        <div class="col-lg-3">
          <div class="report-box py-1 sales-card">
            <div class="card-body d-flex flex-column align-items-center">
              <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                <i class="bi bi-headphones fs-1 txt-theme"></i>
              </div>

              <h5 class="card-title text-capitalize py-1 mb-3">This month holiday</h5>
              <h3 class="fw-bold txt-theme mb-0">4</h3>

            </div>

          </div>
        </div>

        <div class="col-lg-3">
          <div class="report-box py-1 sales-card">
            <div class="card-body d-flex flex-column align-items-center">
              <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                <svg xmlns="http://www.w3.org/2000/svg" height="40" fill="#093B63"
                  class="bi bi-person-wheelchair  txt-theme" viewBox="0 0 16 16">
                  <path
                    d="M12 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-.663 2.146a1.5 1.5 0 0 0-.47-2.115l-2.5-1.508a1.5 1.5 0 0 0-1.676.086l-2.329 1.75a.866.866 0 0 0 1.051 1.375L7.361 3.37l.922.71-2.038 2.445A4.73 4.73 0 0 0 2.628 7.67l1.064 1.065a3.25 3.25 0 0 1 4.574 4.574l1.064 1.063a4.73 4.73 0 0 0 1.09-3.998l1.043-.292-.187 2.991a.872.872 0 1 0 1.741.098l.206-4.121A1 1 0 0 0 12.224 8h-2.79zM3.023 9.48a3.25 3.25 0 0 0 4.496 4.496l1.077 1.077a4.75 4.75 0 0 1-6.65-6.65z" />
                </svg>
              </div>

              <h5 class="card-title text-capitalize py-1 mb-3">today's sick</h5>
              <h3 class="fw-bold txt-theme mb-0">0</h3>

            </div>

          </div>
        </div>

        <div class="col-lg-3">
          <div class="report-box py-1 sales-card">
            <div class="card-body d-flex flex-column align-items-center">
              <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                <i class="bi bi-calendar-x txt-theme"></i>
              </div>

              <h5 class="card-title text-capitalize py-1 mb-3">Today's absent</h5>
              <h3 class="fw-bold txt-theme mb-0">1</h3>

            </div>

          </div>
        </div>

        <div class="col-lg-3">
          <div class="report-box py-1 sales-card">
            <div class="card-body d-flex flex-column align-items-center">
              <div class="card-icon  bg-transparent d-flex align-items-center justify-content-center">
                <i class="bi bi-clock txt-theme"></i>
              </div>

              <h5 class="card-title text-capitalize py-1 mb-3">today's total hour</h5>
              <h3 class="fw-bold txt-theme mb-0">0</h3>

            </div>

          </div>
        </div>

        <!-- Task Assign to staff start -->
        <div class="col-lg-12" id="taskAssignForm" style="display: none;">
            <div class="card border shadow-sm mb-3">
                <p class="p-2 bg-theme-light txt-theme px-3 mb-3 text-capitalize d-flex align-items-center">
                    <i class="bx bxs-user-plus fs-4 me-2"></i>Assign Task
                </p>
                <div class="card-body">
                    <div id="successMessage" class="alert alert-success" style="display: none;">
                        <button type="button" class="btn-close" aria-label="Close"></button>
                        <b></b>
                    </div>
                    <div id="errorMessage" class="alert alert-danger" style="display: none;">
                        <button type="button" class="btn-close" aria-label="Close"></button>
                        <b></b>
                    </div>
                    <form id="myForm">
                        <div class="row">
                            <div class="col-lg-6 mb-3" style="margin-top: 10px;">
                                <input type="hidden" id="client_service_id" name="client_service_id">
                                <input type="hidden" name="client_id" id="client_id">
                                <label for="client_id" class="form-label">Client Name</label>
                                <select name="client_id" class="form-control select2">
                                    <option value="">Client Name</option>
                                    @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3" style="margin-top: 10px;">
                                <label for="staff_id" class="form-label">Select Staff</label>
                                <select name="staff_id" class="form-control select2">
                                    <option value="">Select Staff</option>
                                    @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Assigned Services</label>
                                <div id="assigned-services">
                                    <!-- Assigned services checkboxes -->
                                    <input name="assigned_services[]" id="" value="">
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label for="deadline" class="form-label">Deadline</label>
                                <input type="date" name="deadline" class="form-control">
                            </div>
                            <div class="col-lg-12 mb-3">
                                <label for="note" class="form-label">Notes</label>
                                <textarea name="note" class="form-control" placeholder="Enter your notes here..."></textarea>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-lg-6 text-center">
                                <button type="submit" class="btn btn-sm bg-theme text-light btn-outline-dark px-3 mx-2">Submit</button>
                                 <button type="button" id="cancelButton" class="btn btn-sm btn-outline-dark rounded-2 px-3">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Task Assign to staff end -->

        <div class="row px-0 mx-auto">
          <div class="col-lg-5">
            <div class="col-lg-12 px-0 border shadow-sm mb-3">

              <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i>Todays Deadline
              </p>


              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col">Sl</th>
                    <th scope="col">Task</th>
                    <th scope="col">Client</th>
                    <th scope="col">Stuff</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">1</th>
                    <td>Eating</td>
                    <td>Designer</td>
                    <td>Abul</td>
                  </tr>
                  <tr>
                    <th scope="row">1</th>
                    <td>Eating</td>
                    <td>Designer</td>
                    <td>Abul</td>
                  </tr>
                  <tr>
                    <th scope="row">1</th>
                    <td>Eating</td>
                    <td>Designer</td>
                    <td>Abul</td>
                  </tr>
                  <tr>
                    <th scope="row">1</th>
                    <td>Eating</td>
                    <td>Designer</td>
                    <td>Abul</td>
                  </tr>
                  <tr>
                    <th scope="row">1</th>
                    <td>Eating</td>
                    <td>Designer</td>
                    <td>Abul</td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>

          <!-- Task to be asssigned -->
          <div class="col-lg-7">
              <div class="col-lg-12 px-0 border shadow-sm mb-3">
                  <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                      <i class="bx bxs-user-plus fs-4 me-2"></i>Task Need To Be Assigned
                  </p>
                    <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                      <table id="servicesTable" class="table cell-border table-striped" style="width:100%">
                          <thead>
                              <tr>
                                  <th>Client Name</th>
                                  <th>Service Name</th>
                                  <th>Manager Name</th>
                                  <th>Deadline</th>
                                  <th>Frequency</th>
                              </tr>
                          </thead>
                      </table>
                </div>
              </div>
          </div>
          <!-- Task to be asssigned -->

        </div>
        </div>

        <div class="col-lg-12">
          <div class="col-lg-12 px-0 border shadow-sm mb-3">

            <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
              <i class="bx bxs-user-plus fs-4 me-2"></i>All work status
            </p>

            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">Sl</th>
                  <th scope="col">Task</th>
                  <th scope="col">Client</th>
                  <th scope="col">Stuff</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row">1</th>
                  <td>Eating</td>
                  <td>Designer</td>
                  <td>Abul</td>
                </tr>
                <tr>
                  <th scope="row">1</th>
                  <td>Eating</td>
                  <td>Designer</td>
                  <td>Abul</td>
                </tr>
                <tr>
                  <th scope="row">1</th>
                  <td>Eating</td>
                  <td>Designer</td>
                  <td>Abul</td>
                </tr>
                <tr>
                  <th scope="row">1</th>
                  <td>Eating</td>
                  <td>Designer</td>
                  <td>Abul</td>
                </tr>
                <tr>
                  <th scope="row">1</th>
                  <td>Eating</td>
                  <td>Designer</td>
                  <td>Abul</td>
                </tr>

              </tbody>
            </table>

          </div>
        </div>

        <div class="col-lg-12">
          <div class="col-lg-12 px-0 border shadow-sm mb-3">

            <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
              <i class="bx bxs-user-plus fs-4 me-2"></i>Currently logged-in staffs
            </p>

            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
              <table class="table cell-border table-striped" id="active-staff">
              <thead>
                  <tr>
                      <th scope="col">Sl</th>
                      <th scope="col">Name</th>
                      <th scope="col">Login Time</th>
                      <th scope="col">Duration</th>
                      <th scope="col">Note</th>
                       <th scope="col">Action</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach ($loggedStaff as $staff)
                      <tr>
                          <td>{{ $loop->iteration }}</td>
                          <td>{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                          <td>{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i . d/m/Y') }}</td>
                          <td>
                              <div id="duration_{{ $staff->id }}">{{ $staff->duration }}</div>
                          </td>
                          <td>
                              <textarea rows="2" name="note" placeholder="Add a note here..." style="border-radius: 5px;"></textarea>
                          </td>
                          <td>
                              <button class="btn btn-danger btn-sm logout-btn" data-staff-id="{{ $staff->id }}">Logout</button>
                          </td>
                      </tr>
                  @endforeach
              </tbody>
              </table>
            </div>

          </div>
        </div>
        
        <!-- All Work List -->
        <div class="col-lg-12">
          <div class="col-lg-12 px-0 border shadow-sm mb-3">

            <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
              <i class="bx bxs-user-plus fs-4 me-2"></i>Assigned works
            </p>
            <!-- Works assigned to a user and staff -->
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
              <table id="serviceStaffTable" class="table cell-border table-striped" style="width:100%">
                  <thead>
                      <tr>
                          <th scope="col">Sl</th>
                          <th scope="col">Deadline</th>
                          <th scope="col">Client</th>
                          <th scope="col">Tasks</th>
                          <th scope="col">Staff</th>
                          <th scope="col">Status</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <th scope="row">1</th>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                      </tr>
                  </tbody>
              </table>
            </div>
             <!-- Works assigned to a user and staff -->
          </div>
        </div>
        <!-- All Work List -->

        <!-- Completed Work List -->
        <div class="col-lg-12">
          <div class="col-lg-12 px-0 border shadow-sm mb-3">

            <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
              <i class="bx bxs-user-plus fs-4 me-2"></i>Completed Work List
            </p>
            <!-- Works assigned to a user and staff -->
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
              <table id="completedServices" class="table cell-border table-striped" style="width:100%">
                  <thead>
                      <tr>
                          <th scope="col">Sl</th>
                          <th scope="col">Deadline</th>
                          <th scope="col">Client</th>
                          <th scope="col">Tasks</th>
                          <th scope="col">Staff</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <th scope="row">1</th>
                          <td></td>
                          <td></td>
                          <td></td>
                      </tr>
                  </tbody>
              </table>
            </div>
             <!-- Works assigned to a user and staff -->
          </div>
        </div>
        <!-- Completed Work List -->
        
  </div>
</section>

@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>

<!-- Fetch services for each client -->
<script>
  $(document).ready(function() {
      $('select[name="client_id"]').change(function() {
          var clientId = $(this).val();
          if (clientId) {
              $.ajax({
                  url: '/admin/get-assigned-services/' + clientId,
                  type: 'GET',
                  success: function(data) {
                      if (data && data.assigned_services && data.assigned_services.length > 0) {
                          var checkboxes = '';
                          $.each(data.assigned_services, function(key, service) {
                              checkboxes += '<div class="form-check form-check-inline">';
                              checkboxes += '<input class="form-check-input" type="checkbox" id="service_' + service.id + '" name="assigned_services[]" value="' + service.id + '" checked>';
                              checkboxes += '<label class="form-check-label" for="service_' + service.id + '">' + service.name + '</label>';
                              checkboxes += '</div>';
                          });
                          $('#assigned-services').html(checkboxes);
                      } else {
                          $('#assigned-services').html('<small class="text-danger"> Not Found</small>');
                      }

                      if (data && data.deadline) {
                          $('input[name="deadline"]').val(data.deadline);
                      } else {
                          $('input[name="deadline"]').val('');
                      }
                  },
                  error: function(xhr, status, error) {
                      $('#assigned-services').html('<small class="text-danger"> Not Found</small>');
                      $('input[name="deadline"]').val('');
                      console.error(error);
                  }
              });
          } else {
              $('#assigned-services').empty();
              $('input[name="deadline"]').val('');
          }
      });
  });
</script>
<!-- Fetch services for each client -->

<!-- Assign service staff -->
<script>
  $(document).ready(function () {

    $('#myForm').submit(function (event) {
        event.preventDefault();

        var formData = new FormData($(this)[0]);
        $.ajax({
            url: "{{URL::to('/admin/assign-service-staff')}}",
            type: 'POST',
            data: formData,
            async: false,
            success: function (response) {
                if (response.status === 200) {
                    $('#successMessage b').text(response.message);
                    $('#successMessage').show();
                    $('#errorMessage').hide();
                    $('#myForm')[0].reset();
                    window.location.reload();
                } else {
                    $('#errorMessage b').text(response.message);
                    $('#errorMessage').show();
                    $('#successMessage').hide();
                    $('#myForm')[0].reset();
                }
            },
            error: function (xhr, status, error) {
                var errorMessage = "";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        errorMessage += key + ": " + value.join(", ") + "<br>";
                    });
                } else {
                    errorMessage = "An error occurred. Please try again later.";
                }
                $('#errorMessage').html(errorMessage);
                $('#errorMessage').show();
                $('#successMessage').hide();
            },
            cache: false,
            contentType: false,
            processData: false
        });
        return false;
    });

    $('#clearButton').click(function () {
        $('#myForm')[0].reset();
        $('#errorMessage').hide();
        $('#successMessage').hide();
    });
  });

  $('#cancelButton').click(function() {
      $('#taskAssignForm').hide();
  });
</script>
<!-- Assign service staff -->

<!-- Processing + cancelled Work List + change status -->
<script>
  $(document).ready(function() {
      $('#serviceStaffTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/admin/get-services-client-staff',
              type: 'GET',
          },
          columns: [
              { 
                  data: null, 
                  name: 'id', 
                  render: function (data, type, row, meta) {
                      return meta.row + 1;
                  }
              },
              { 
                  data: 'deadline', 
                  name: 'deadline', 
                  render: function(data, type, row) {
                      return moment(data).format('DD.MM.YY');
                  }
              },
              { data: 'client_name', name: 'client_name' },
              { data: 'tasks', name: 'tasks' },
              { data: 'staff_name', name: 'staff_name' },
              { data: 'status', name: 'status', orderable: false, searchable: false,
                render: function(data, type, row) {
                  var options = '<select class="form-select btn btn-outline-secondary status-select" data-id="' + row.id + '">';
                  options += '<option value="1"' + (data === 1 ? ' selected' : '') + '>Processing</option>';
                  options += '<option value="2"' + (data === 2 ? ' selected' : '') + '>Completed</option>';
                  options += '<option value="3"' + (data === 3 ? ' selected' : '') + '>Cancelled</option>';
                  options += '</select>';
                  return options;
                }
              }
          ]
      });

      $(document).on('change', '.status-select', function() {
          var serviceStaffId = $(this).data('id');
          var status = $(this).val();
          $.ajax({
              url: '/admin/update-service-status',
              type: 'POST',
              data: {
                  service_staff_id: serviceStaffId,
                  status: status,
                  _token: '{{ csrf_token() }}'
              },
              success: function(response) {
                  if (response.success) {
                      $('#serviceStaffTable').DataTable().ajax.reload();
                      Toastify({
                          text: "Status changed successfully!"
                      }).showToast();
                  } else {
                      Toastify({
                          text: "Failed to change status."
                      }).showToast();
                  }
              },
              error: function(xhr, status, error) {
              }
          });
      });
  });
</script>
<!-- Processing + cancelled Work List -->

<!-- Completed Work List  -->
<script>
  $(document).ready(function() {
      $('#completedServices').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/admin/get-completed-services',
              type: 'GET',
          },
          columns: [
              { 
                  data: null, 
                  name: 'id', 
                  render: function (data, type, row, meta) {
                      return meta.row + 1;
                  }
              },
              { 
                  data: 'deadline', 
                  name: 'deadline', 
                  render: function(data, type, row) {
                      return moment(data).format('DD.MM.YY');
                  }
              },
              { data: 'client_name', name: 'client_name' },
              { data: 'tasks', name: 'tasks' },
              { data: 'staff_name', name: 'staff_name' }
          ]
      });
  });
</script>
<!-- Completed Work List -->

<!-- Task need to be assigned -->
<script>
  $('#servicesTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
          url: '/admin/get-all-services',
          type: 'GET',
          dataSrc: 'data',
          error: function (xhr, error, thrown) {
              console.error('DataTables error:', error, thrown);
          }
      },
      columns: [
          { data: 'client.name', name: 'client.name' },
          { data: 'service.name', name: 'service.name' },
          { data: 'manager.first_name', name: 'manager.first_name' }, 
          { 
              data: 'service_deadline', 
              name: 'service_deadline',
              render: function(data, type, row) {
                  return moment(data).format('DD.MM.YY');
              }
          },
          { data: 'service_frequency', name: 'service_frequency' }
      ]
  });
</script>
<!-- Task need to be assigned -->


<!-- Data table initialize -->
<script>
    $(function () {
      $("#active-staff").DataTable();
    });
</script>

<!-- Data table initialize -->

<!-- <script>

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.logout-btn').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            var staffId = this.getAttribute('data-staff-id');
            console.log(staffId);

            if (!confirm('Are you sure you want to logout this staff member?')) {
                return;
            }

            var url = '{{ route('customLogoutByAdmin', ['userId' => ':staffId']) }}';
            url = url.replace(':staffId', staffId);

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        alert('Staff member has been logged out successfully.');
                    } else {
                        alert('Failed to logout staff member. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    try {
                        var errorMessage = JSON.parse(xhr.responseText).message;
                        alert('An error occurred: ' + errorMessage);
                    } catch (e) {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });
    });
});

</script> -->



@endsection

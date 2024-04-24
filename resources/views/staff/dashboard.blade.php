@extends('staff.layouts.staff')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
                <div class="card-body p-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Time
                    </div>
                    <div class="d-flex gap-3 my-5">
                        <div class="text-center flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Active Time</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                {{ $activeTime ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="text-center border-start border-3 ps-3 flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Break Time</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                   {{ $breakTime ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Clock in</a>
                        </div>
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Take Break</a>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-12">
                            <a href="#" onclick="event.preventDefault(); $('#noteModal').modal('show');" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold">Clock out</a>
                            <form id="logout-form" action="{{ route('customLogout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog mt-2">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="noteModalLabel">Add Note</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="noteForm" method="POST" action="{{ route('customLogout') }}">
                            @csrf
                            <div class="form-group">
                                <label for="noteInput">Note:</label>
                                <input type="text" class="form-control" id="noteInput" name="note">
                            </div>
                            <input type="hidden" name="_method" value="POST">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveNoteBtn">Save Note</button>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog mt-2">
                <div class="modal-content">
                    <div class="modal-body">

                        <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                            <div class="card-body px-0">
                                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                    Add Note
                                </div>

                                <form id="noteForm" method="POST" action="{{ route('customLogout') }}">
                                    @csrf
                                    <div class="form-group">
                                        <div class="form-group mt-4">
                                            <label for="noteInput">Note:</label>
                                            <textarea class="form-control" id="noteInput" rows="5" name="note" placeholder="Your notes..."></textarea>
                                        </div>
                                    </div>
                                    <input type="hidden" name="_method" value="POST">
                                </form>

                                <div class="text-center">
                                    <button type="button" class="mt-3 btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveNoteBtn">Save Note</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        What's Happening at HD Accountancy
                    </div>
                    <div class="position-absolute bottom-0 mb-4" style="width:90%;">
                        <div class="d-flex align-items-center gap-3 w-full">
                            <i class="bi bi-person-circle fs-3 txt-theme"></i>
                            <input type="text" class="rounded-3 border-2 border-theme form-control" placeholder="Leave a comment">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Notes
                    </div>
                    <div class="mh250">
                        <!-- Your notes content here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Assigned Tasks
                    </div>
                <!-- Works assigned to a user and specified staff -->
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="serviceStaffTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Sl</th>
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
                <!-- Works assigned to a user and specified staff -->
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<!-- All Work List + change status -->
<script>
  $(document).ready(function() {
      $('#serviceStaffTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/staff/get-services-client-staff',
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
              url: '/staff/update-service-status',
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
<!-- All Work List -->


<script>
    $('#saveNoteBtn').click(function() {
        var noteValue = $('#noteInput').val().trim();
        if (noteValue === '') {
            alert('Note field cannot be empty');
        } else {
            $('#noteForm').submit();
        }
    });
</script>
@endsection

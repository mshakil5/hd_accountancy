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

        <div class="row px-0 mx-auto">
          <div class="col-lg-6">
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
          <div class="col-lg-6">
            <div class="col-lg-12 px-0 border shadow-sm mb-3">

              <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i>Assign Task
              </p>


              <!-- Success and Error message -->
            <div class="row my-4 px-3">
                <div class="col-lg-12">
                    <div id="successMessage" class="alert alert-success" style="display: none;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <b></b>
                    </div>
                    <div id="errorMessage" class="alert alert-danger" style="display: none;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <b></b>
                    </div>
                </div>
            </div>
            <!-- Success and Error message -->

              <!-- Task Assign Form -->
              <form id="myForm">
                <div class="row p-3">
                    <div class="col-lg-6 mb-3 d-flex align-items-center justify-content-around">
                        <small for="" class="">Select Client </small>
                        <select name="client_id" class="form-control select2">
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex align-items-center justify-content-around">
                        <small for="" class="">Select Stuff </small>
                        <select name="staff_id" class="form-control select2">
                            <option value="">Select Staff</option>
                            @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6 mb-3 d-flex align-items-center justify-content-around">
                        <small for="" class="">Assigned Services</small>
                        <div id="assigned-services">
                          <input name="assigned_services[]" id="" value="">
                            <!-- Assigned services checkboxes -->
                        </div>
                    </div>

                    <div class="col-lg-6 mb-3 d-flex align-items-center justify-content-around">
                        <small for="" class="">Deadline </small>
                        <input type="date" name="deadline" class="form-control">
                    </div>
                    <div class="col-lg-12 mb-3 d-flex align-items-center justify-content-around">
                      <label for="">Notes</label>
                       <textarea name="note" class="form-control w-100" placeholder="Enter your notes here..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-lg-5 mx-auto text-center">
                            <button type="submit" class="btn btn-sm bg-theme text-light btn-outline-dark px-3 mx-2">Submit</button>
                            <button type="reset" class="btn btn-sm btn-outline-dark rounded-2 px-3">Clear</button>
                        </div>
                    </div>
                </div>
              </form>

            </div>
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
              <i class="bx bxs-user-plus fs-4 me-2"></i>All Work List
            </p>
            <!-- Works assigned to a user and staff -->
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
              <table id="serviceStaffTable" class="table cell-border table-striped">
                  <thead>
                      <tr>
                          <th scope="col">Sl</th>
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
        
  </div>
</section>

@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>


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
                                checkboxes += '<input class="form-check-input" type="checkbox" id="service_' + service.id + '" name="assigned_services[]" value="' + service.id + '">';
                                checkboxes += '<label class="form-check-label" for="service_' + service.id + '">' + service.name + '</label>';
                                checkboxes += '</div>';
                            });
                            $('#assigned-services').html(checkboxes);
                        } else {
                            $('#assigned-services').html('<small class="text-danger"> Not Found</small>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            } else {
                $('#assigned-services').empty();
            }
        });
    });
</script>


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
</script>



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
                { data: 'id', name: 'id' },
              { data: 'client_name', name: 'client_name' },
              { data: 'tasks', name: 'tasks' },
              { data: 'staff_name', name: 'staff_name' },
              ]
        });
    });
</script>


@endsection

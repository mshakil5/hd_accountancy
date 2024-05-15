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

        <div class="col-lg-12">
          <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignTaskSection" style="display: none;">
            <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                Assign Task
            </div>

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

            <div class="col-md-12">
              <div class="row mt-3">
                  <div class="col-3 text-center">
                      <h5 class="mb-3">Choose Service</h5>
                      <div class="form-check">
                        <input type="hidden" id="clientId">
                        <select id="servicesDropdown" class="form-control mt-2">
                            <option value="">Select Service</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                      </div>
                  </div>    
                  <div class="col-3 text-center">
                      <h5 class="mb-3">Choose Manager</h5>
                      <div class="form-check">
                          <select id="managerDropdown" class="form-control mt-2">
                            <option value="">Select Manager</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}">
                                    {{ $manager->first_name }}
                                </option>
                            @endforeach
                        </select>
                      </div>
                  </div>  
                  <div class="col-3 text-center">
                      <h5 class="mb-3">Choose Frequency</h5>
                      <div class="form-check">
                          <select id="service_frequency" class="form-control mt-2" name="service_frequency">
                              <option value="">Select Frequency</option>
                              <option >Daily</option>
                              <option>Weekly</option>
                              <option >Monthly</option>
                              <option >Quarterly</option> 
                              <option >Yearly</option>
                          </select>
                      </div>
                  </div>   
                  <div class="col-3 text-center">
                      <h5 class="mb-3">Deadline</h5>
                      <div class="form-check">
                          <input type="date" id="service_deadline" class="form-control mt-2" name="service_deadline" value="">
                      </div>
                  </div>
              </div>
            </div>

            <table class="table mt-3">
              <thead>
                <tr>
                  <th>Sub Service Name</th>
                  <th>Deadline</th>
                  <th>Staff</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody id="serviceDetailsTable"></tbody>
            </table>
            <div class="row mt-3 mb-3">
              <div class="col-lg-4 mx-auto text-center">
                <button id="sub-service-updateButton" class="btn btn-sm bg-theme text-light btn-outline-dark" style="display: none;">Update</button>
                <button id="sub-service-cancelButton" class="btn btn-sm btn-outline-dark" style="display: none;">Cancel</button>
              </div>
            </div>
          </div>
        </div>

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
                                  <th>Action</th>
                              </tr>
                          </thead>
                      </table>
                </div>
              </div>
          </div>
          <!-- Task to be asssigned -->

        </div>
        </div>

        <div class="col-lg-12" style="display: none;">
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
        <!-- Currently Active Staffs Start-->
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
                      <th scope="col">Attendence Status</th>
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
                      <td>{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i. d/m/Y') }}</td>
                      <td>
                          @if ($staff->prorotaNotFound)
                              Prorota not found
                          @else
                              @if ($staff->is_late)
                                  Late
                              @else
                                  In Time
                              @endif
                          @endif
                      </td>
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
        <!-- Currently Active Staffs End-->

        <!-- Todays's Late Staffs Start -->
        <div class="col-lg-12">
            <div class="col-lg-12 px-0 border shadow-sm mb-3">
                <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class="bx bxs-user-plus fs-4 me-2"></i>Late Staffs
                </p>
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-striped" id="late-staff-prorota">
                        <thead>
                            <tr>
                                <th scope="col">Sl</th>
                                <th scope="col">Name</th>
                                <th scope="col">Login Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lateStaff as $staff)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $staff->first_name }} {{ $staff->last_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i . d/m/Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Todays's Late Staffs End -->

        <!-- Todays's Shift Deviation Staffs Start -->
        <div class="col-lg-12">
            <div class="col-lg-12 px-0 border shadow-sm mb-3">
                <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class="bx bxs-user-plus fs-4 me-2"></i>Departure Status
                </p>
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-striped" id="shift-deviation-staff">
                        <thead>
                            <tr>
                                <th scope="col">Sl</th>
                                <th scope="col">Name</th>
                                <th scope="col">Log Out Time</th>
                                <th scope="col">Departure Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filteredLogs as $index => $log)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $log->user->first_name }} {{ $log->user->last_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($log->end_time)->format('H:i. d/m/Y') }}</td>
                                <td>{{ $log->departure_status }}</td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Todays's Shift Deviation Staffs End -->

        <!-- Todays's Absent Staffs Start -->
        <div class="col-lg-12">
            <div class="col-lg-12 px-0 border shadow-sm mb-3">
                <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class="bx bxs-user-plus fs-4 me-2"></i>Today's Absent Staffs
                </p>
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-striped" id="absent-staff">
                        <thead>
                            <tr>
                                <th scope="col">Sl</th>
                                <th scope="col">Name</th>
                                <th scope="col">Comment</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($absentStaff as $index => $staff)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $staff->first_name }} {{ $staff->last_name }}</td>
                                <td>
                                    @if ($staff->logComments->isNotEmpty())
                                        <textarea class="form-control comment-textarea" rows="1" readonly>{{ $staff->logComments->last()->comment }}</textarea>
                                    @else
                                        <textarea class="form-control comment-textarea" rows="1"></textarea>
                                    @endif
                                </td>
                                <td>
                                    @if ($staff->logComments->isEmpty())
                                        <button class="btn btn-secondary submit-comment" data-staff-id="{{ $staff->id }}">Submit</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Todays's Absent Staffs End -->

        <!-- Assigned service details section start -->
        <div class="col-lg-12">
            <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="assignedTaskSection" style="display: none;">
                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                Assigned Work Details
                </div>

                <div class="container-fluid">
                    <div class="row mt-3">
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Service</h5>
                            <input type="text" id="service_name2" class="form-control mt-2" readonly>
                        </div>    
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Manager</h5>
                            <input type="text" id="manager_name2" class="form-control mt-2" value="" readonly>
                        </div>  
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Frequency</h5>
                            <input type="text" id="service_frequency2" class="form-control mt-2" readonly>
                        </div>   
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Deadline</h5>
                            <input type="date" id="service_deadline2" class="form-control mt-2" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sub Service Name</th>
                                        <th>Deadline</th>
                                        <th>Staff</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedServiceDetailsTable"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 mb-3">
                        <div class="col-lg-4 mx-auto text-center">
                            <button id="assigned-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Assigned service details section start -->

        <!-- Assigned Work List -->
        <div class="col-lg-12">
          <div class="col-lg-12 px-0 border shadow-sm mb-3">

            <p class="p-2 bg-theme-light txt-theme px-3 mb-0 text-capitalize d-flex align-items-center">
              <i class="bx bxs-user-plus fs-4 me-2"></i>Assigned Work List
            </p>
            <!-- Works assigned to a user and staff -->
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
              <table id="assignedServices" class="table cell-border table-striped" style="width:100%">
                  <thead>
                      <tr>
                          <th scope="col">Client Name</th>
                          <th scope="col">Service Name</th>
                          <th scope="col">Deadline</th>
                          <th scope="col">Frequency</th>
                          <th scope="col">Action</th>
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
        <!-- Assigned Work List -->

        <!-- Completed service details section start -->
        <div class="col-lg-12">
            <div class="report-box border-theme sales-card p-4 mb-3 rounded-4 border-3" id="completedTaskSection" style="display: none;">
                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                Completed Work Details
                </div>

                <div class="container-fluid">
                    <div class="row mt-3">
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Service</h5>
                            <input type="text" id="service_name1" class="form-control mt-2" readonly>
                        </div>    
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Manager</h5>
                            <input type="text" id="manager_name1" class="form-control mt-2" value="" readonly>
                        </div>  
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Frequency</h5>
                            <input type="text" id="service_frequency1" class="form-control mt-2" readonly>
                        </div>   
                        <div class="col-md-3 text-center">
                            <h5 class="mb-3">Deadline</h5>
                            <input type="date" id="service_deadline1" class="form-control mt-2" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sub Service Name</th>
                                        <th>Deadline</th>
                                        <th>Staff</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Timer</th>
                                    </tr>
                                </thead>
                                <tbody id="completedServiceDetailsTable"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 mb-3">
                        <div class="col-lg-4 mx-auto text-center">
                            <button id="completed-cancelButton" class="btn btn-sm btn-outline-dark">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed service details section start -->

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
                          <th scope="col">Client Name</th>
                          <th scope="col">Service Name</th>
                          <th scope="col">Deadline</th>
                          <th scope="col">Frequency</th>
                          <th scope="col">Action</th>
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
          columns: 
            [
                { data: 'clientname', name: 'clientname' },
                  { data: 'servicename', name: 'servicename' },
                  { 
                      data: 'service_deadline', 
                      name: 'service_deadline',
                      render: function(data, type, row) {
                          return moment(data).format('DD.MM.YY');
                      }
                  },
                  { data: 'service_frequency', name: 'service_frequency' },
                  { 
                      data: 'action', 
                      name: 'action',
                      orderable: false, 
                      searchable: false 
                  }
            ]
      });

      $(document).on('click', '.task-details', function() {
            var clientserviceId = $(this).data('id');
            var managerFirstName = $(this).data('manager-firstname');
            var rowData = $('#completedServices').DataTable().row($(this).closest('tr')).data();
            var serviceName = rowData.servicename;
            var frequency = rowData.service_frequency;
            var deadline = rowData.service_deadline;

            $('#service_name1').val(serviceName);
            $('#manager_name1').val(managerFirstName);
            $('#service_frequency1').val(frequency);
            $('#service_deadline1').val(deadline);

            $.ajax({
                url: '/admin/getClientSubService/' + clientserviceId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    populateCompletedForm(data);
                    // console.log(data);
                },
                error: function(xhr, error, thrown) {
                    console.error('Error fetching sub-services:', error, thrown);
                }
            });
        });

        function populateCompletedForm(subServices) {
            var completedServiceDetailsTable = $('#completedServiceDetailsTable');
            completedServiceDetailsTable.empty();

            var staffs = @json($staffs);

            $.each(subServices, function(index, subService) {
                var staff = staffs.find(function(staff) {
                    return staff.id === subService.staff_id;
                });

                var staffName = staff ? staff.first_name : 'N/A';
                var duration = '';
                var firstWorkTime = subService.work_times[0];
                
                if (subService.sequence_status === 2) {
                    var firstWorkTime = subService.work_times[0];
                    if (firstWorkTime) {
                        var durationInSeconds = firstWorkTime.duration;
                        var hours = Math.floor(durationInSeconds / 3600);
                        var minutes = Math.floor((durationInSeconds % 3600) / 60);
                        var seconds = durationInSeconds % 60;
                        duration = `<div>${hours}h ${minutes}m ${seconds}s</div>`;
                    }
                }

                var newRow = `
                    <tr>
                        <td>${subService.sub_service.name}</td>
                        <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                        <td>${staffName}</td>
                        <td>${subService.note ? subService.note : ''}</td>
                        <td>
                            ${  subService.sequence_status === 2 ? 'Work is completed' 
                                : subService.sequence_status === 1 ? 'Not Started' 
                                : subService.sequence_status === 0 ? 'Processing'
                                : 'N/A'
                            }
                        </td>
                        <td>
                            <span class="timer-duration">${duration}</span>
                        </td>
                    </tr>
                `;
                completedServiceDetailsTable.append(newRow);
            });

            $('#completedTaskSection').show();
        }


        $('#completed-cancelButton').click(function() {
            $('#completedTaskSection').hide();
        });

  });
</script>
<!-- Completed Work List -->

<!-- Assigned Work List  -->
<script>
  $(document).ready(function() {
      $('#assignedServices').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
              url: '/admin/get-assigned-service',
              type: 'GET',
          },
          columns: [
              { data: 'clientname', name: 'clientname' },
              { data: 'servicename', name: 'servicename' },
              {
                  data: 'service_deadline',
                  name: 'service_deadline',
                  render: function(data, type, row) {
                      return moment(data).format('DD.MM.YY');
                  }
              },
              { data: 'service_frequency', name: 'service_frequency' },
              {
                  data: 'action',
                  name: 'action',
                  orderable: false,
                  searchable: false
              }
          ]
      });

      $(document).on('click', '.task-detail', function() {
          var clientserviceId = $(this).data('id');
          var managerFirstName = $(this).data('manager-firstname');
          var rowData = $('#assignedServices').DataTable().row($(this).closest('tr')).data();
          
          if (rowData) {
              var serviceName = rowData.servicename;
              var frequency = rowData.service_frequency;
              var deadline = rowData.service_deadline;

              $('#service_name2').val(serviceName);
              $('#manager_name2').val(managerFirstName);
              $('#service_frequency2').val(frequency);
              $('#service_deadline2').val(deadline);

              $.ajax({
                  url: '/admin/getClientSubService/' + clientserviceId,
                  type: "GET",
                  dataType: "json",
                  success: function(data) {
                      populateCompletedForm(data);
                      // console.log(data);
                  },
                  error: function(xhr, error, thrown) {
                      console.error('Error fetching sub-services:', error, thrown);
                  }
              });

              $('#assignedTaskSection').show();
          } else {
              console.error('Row data is undefined');
          }
      });

      function populateCompletedForm(subServices) {
          var completedServiceDetailsTable = $('#assignedServiceDetailsTable');
          completedServiceDetailsTable.empty();

          var staffs = @json($staffs);

          $.each(subServices, function(index, subService) {
              var staff = staffs.find(function(staff) {
                  return staff.id === subService.staff_id;
              });

              var staffName = staff ? staff.first_name : 'N/A';

              var newRow = `
                  <tr>
                      <td>${subService.sub_service.name}</td>
                      <td>${moment(subService.deadline).format('DD.MM.YYYY')}</td>
                      <td>${staffName}</td>
                      <td>${subService.note ? subService.note : ''}</td>
                      <td>
                          ${  subService.sequence_status === 2 ? 'Work is completed' 
                              : subService.sequence_status === 1 ? 'Not Started' 
                              : subService.sequence_status === 0 ? 'Processing'
                              : 'N/A'
                          }
                      </td>
                  </tr>
              `;
              completedServiceDetailsTable.append(newRow);
          });
      }

      $('#assigned-cancelButton').click(function() {
          $('#assignedTaskSection').hide();
      });
  });
</script>
<!-- Assigned Work List -->

<!-- Task need to be assigned -->
<script>
  $(document).ready(function() {

    $('#servicesTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/admin/get-all-services',
        type: 'GET',
        dataSrc: 'data',
        error: function(xhr, error, thrown) {
          console.error('DataTables error:', error, thrown);
        }
      },
      columns: [
        { data: 'client.name', name: 'client.name' },
        { data: 'service.name', name: 'service.name' },
        { 
          data: null,
          name: 'manager',
          render: function(data, type, row) {
            return data.manager.first_name + ' ' + data.manager.last_name;
          }
        },
        { 
          data: 'service_deadline', 
          name: 'service_deadline',
          render: function(data, type, row) {
            return moment(data).format('DD.MM.YY');
          }
        },
        { data: 'service_frequency', name: 'service_frequency' },
        {
          data: null,
          name: 'action',
          render: function(data, type, row) {
            return '<button class="btn btn-secondary btn-sm assign-btn" ' +
                  'data-clientservice-id="' + data.id + '" ' +
                  'data-service-id="' + data.service.id + '" ' +
                  'data-client-service-id="' + data.client_service_id + '" ' +
                  'data-client-id="' + data.client.id + '" ' +
                  'data-client-name="' + data.client.name + '" ' +
                  'data-service-id="' + data.service.id + '" ' + 
                  'data-service-name="' + data.service.name + '" ' +
                  'data-manager-id="' + data.manager.id + '" ' +
                  'data-manager-name="' + (data.manager.first_name + ' ' + data.manager.last_name) + '" ' +
                  'data-service-deadline="' + data.service_deadline + '"'+
                  'data-service-frequency="' + data.service_frequency + '">' +
                  'Assign</button>';
          }
        }
      ]
    });

    function populateSubServiceForm(subServices, clientName, serviceName, managerName, serviceDeadline, serviceFrequency) {
      var subServiceTable = $('#serviceDetailsTable');
      subServiceTable.empty();

      $.each(subServices, function(index, subService) {
        var newRow = `
          <tr>
            <td>${subService.sub_service.name}</td>
            <input type="hidden" name="sub_service_id[]" value="${subService.sub_service_id}">
            <td>
              <input type="date" name="deadline[]" class="form-control" value="${subService.deadline}">
            </td>
            <td>
              <select class="form-control select2 staffDropdown" name="staff_id[]">
                <option value="">Select Staff</option>`;
                
                @foreach($staffs as $staff)
                  newRow += `<option value="{{ $staff->id }}" ${subService.staff_id == {{$staff->id}} ? 'selected' : ''}>
                    {{ $staff->first_name }}
                  </option>`;
                @endforeach

              newRow += `</select>
            </td>
            <td><textarea name="note[]" rows="1" class="form-control">${subService.note ? subService.note : ''}</textarea></td>
          </tr>
        `;
        subServiceTable.append(newRow);
      });
    }

    $('#servicesTable').on('click', '.assign-btn', function() {
      var clientserviceId = $(this).data('clientservice-id');
      var serviceId = $(this).data('service-id');
      var clientId = $(this).data('client-id'); 
      var clientName = $(this).data('client-name');
      var serviceName = $(this).data('service-name');
      var managerId = $(this).data('manager-id');
      var managerName = $(this).data('manager-name');
      var serviceDeadline = $(this).data('service-deadline');
      var serviceFrequency = $(this).data('service-frequency');


      $('#clientId').val(clientId);
      $('#servicesDropdown').val(serviceId);
      $('#managerDropdown').val(managerId);
      $('#service_frequency').val(serviceFrequency);
      $('#service_deadline').val(serviceDeadline);
      
      $('#assignTaskSection').toggle();
      $('#sub-service-updateButton').show();
      $('#sub-service-cancelButton').show();

      $.ajax({
        url: '/admin/getClientSubServices/' + clientserviceId,
        type: "GET",
        dataType: "json",
        success: function(data) {
          populateSubServiceForm(data, clientserviceId, serviceId, clientId, clientName, serviceName, managerId, managerName, serviceDeadline, serviceFrequency);
        },
        error: function(xhr, status, error) {
          console.error(error);
        }
      });
    });

    $('#sub-service-cancelButton').click(function() {
      $('#assignTaskSection').hide();
      $('#serviceDetailsTable').empty();
      $('#sub-service-updateButton, #sub-service-cancelButton').hide();
    });
  });
</script>
<!-- Task need to be assigned -->

<!-- Task update / Assign start -->
<script>
  $(document).ready(function() {
    $('#sub-service-updateButton').click(function(e) {
      e.preventDefault(); 

      var clientId = $('#clientId').val(); 
      var serviceId = $('#servicesDropdown').val();
      var managerId = $('#managerDropdown').val(); 
      var serviceFrequency = $('#service_frequency').val(); 
      var serviceDeadline = $('#service_deadline').val(); 

      var subServices = [];
      $('#serviceDetailsTable tr').each(function() {
        var subServiceId = $(this).find('input[name="sub_service_id[]"]').val();
        var deadline = $(this).find('input[name="deadline[]"]').val();
        var staffId = $(this).find('select[name="staff_id[]"]').val();
        var note = $(this).find('textarea[name="note[]"]').val();

        subServices.push({
          subServiceId: subServiceId,
          deadline: deadline,
          staffId: staffId,
          note: note
        });
      });


      var data = {
        clientId: clientId,
        serviceId: serviceId,
        managerId: managerId,
        service_frequency: serviceFrequency,
        service_deadline: serviceDeadline,
        subServices: subServices
      };

      // console.log(data);

      $.ajax({
        url: '/admin/update-service-staff', 
        type: 'POST',
        data: data,
        success: function(response) {
          // console.log(response);
          swal({
              title: "Success!",
              text: "Task updated successfully",
              icon: "success",
              button: "OK",
          });
          setTimeout(function() {
              location.reload();
          }, 2000);
        },
        error: function(xhr, status, error) {
          console.error(error);
          var errorMessage = "";
          if (xhr.responseJSON && xhr.responseJSON.errors){
              $.each(xhr.responseJSON.errors, function (key, value) {
                  errorMessage += key + ": " + value.join(", ") + "<br>";
              });
          } else {
              errorMessage = "An error occurred. Please try again later.";
          }
          $('#errorMessage').html(errorMessage);
          $('#errorMessage').show();
          $('#successMessage').hide();
          console.error("Error occurred: " + error);
          console.error(xhr.responseText);
        }
      });
    });
  });
</script>
<!-- Task update / Assign end -->

<!-- Fetching sub services and putting on table start -->
<script>
    $(document).ready(function() {
        $('#servicesDropdown').change(function() {
            var serviceId = $(this).val();
            if(serviceId) {
                $.ajax({
                    url: '/admin/getSubServices/' + serviceId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#serviceDetailsTable tr').remove();

                        $.each(data, function(key, value) {
                            var newRow = `
                                <tr>
                                    <td>${value.name}</td>
                                    <td><input type="date" name="deadline" class="form-control"></td>
                                    <td>
                                        <select class="form-control select2 staffDropdown" name="staff_id">
                                            <option value="">Select Staff</option>
                                            @foreach($staffs as $staff)
                                                <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><textarea name="note" rows="1" class="form-control"></textarea></td>
                                <input type="hidden" class="sub-service-id" data-sub-service-id="${value.id}">
                                </tr>
                            `;
                            $('#serviceDetailsTable').append(newRow);
                        });
                        $('#subServicesDropdown').show();
                    }
                });
            } else {
                $('#subServicesDropdown').empty().hide();
            }
        });
    });
</script>
<!-- Fetching sub services and putting on table end -->

<!-- Comment On absent staffs start -->
<script>
    $(document).ready(function() {
        $('.submit-comment').click(function() {
            var staffId = $(this).data('staff-id');
            var comment = $(this).closest('tr').find('.comment-textarea').val();
            
            $.ajax({
                type: 'POST',
                url: '{{ route("add.comment") }}',
                data: {
                    user_id: staffId,
                    comment: comment,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Note sent successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Comment On absent staffs start -->

<!-- Data table initialize -->
<script>
    $(document).ready(function () {
        $("#active-staff, #late-staff-prorota, #absent-staff, #shift-deviation-staff").DataTable();
    });
</script>
<!-- Data table initialize -->

<!-- Logout staff start -->
<script>
    $(document).ready(function () {
        $('.logout-btn').click(function () {
            const attendenceId = $(this).data('staff-id');
            const note = $(this).closest('tr').find('textarea[name="note"]').val();

            $.ajax({
                url: '/admin/staff-logout/' + attendenceId,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: {
                    note: note
                },
                success: function (response) {
                  swal({
                        title: "Success!",
                        text: "Staff logged out successfully",
                        icon: "success",
                        button: "OK",
                    });
                    window.setTimeout(function(){location.reload()},2000);
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Logout staff end -->

@endsection

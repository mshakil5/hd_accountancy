@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> Prorota Edit
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

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content pt-2" id="myTabjustifiedContent">

                                <!-- Prorota Form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                                    <form id="myForm">
                                        <div class="row my-4">
                                            
                                            <div class="col-lg-4">
                                                <label for="country">Employee</label>
                                                <div class="mt-2">
                                                    <input type="hidden" name="prorota_id" id="prorota_id" class="form-control" value="{{$data->id}}" >
                                                    <select class="form-control select2 my-2" id="staff_id" name="staff_id">
                                                        <option value="" selected disabled>Choose Employee</option>
                                                        @foreach($staffs as $staff)
                                                        <option value="{{ $staff->id }}" @if ($data->staff_id == $staff->id) selected @endif>{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <label for="schedule_type">Schedule Type</label>
                                                  <div class="mt-2">
                                                    <select class="form-control my-2" name="schedule_type" id="schedule_type">
                                                        <option value="">Please select</option>
                                                        <option value="Salary" @if ($data->schedule_type == "Salary") selected @endif>Salary</option>
                                                        <option value="Wages" @if ($data->schedule_type == "Wages") selected @endif>Wages</option>
                                                    </select>
                                                </div>
                                            </div>


                                            {{-- <div class="col-lg-4">
                                                <label for="">Reporting Employee ID</label>
                                                <input type="number" class="form-control my-2" id="reporting_employee_id" name="reporting_employee_id" placeholder="Reporting Employee ID" readonly>
                                            </div> --}}

                                        </div>
                                        <div class="row align-items-center">
                                            <div class="col-lg-8">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Day</th>
                                                            <th>Start time</th>
                                                            <th>End Time</th>
                                                            <th>
                                                                <button class="btn btn-secondary add-new-day" type="button">+</button>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="scheduleContainer">

                                                        @foreach ($data->prorotaDetail as $detail)

                                                           @if ($detail->day == "Monday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Monday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Tuesday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Tuesday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Wednesday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Wednesday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Thursday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Thursday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Friday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Friday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Saturday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Saturday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 

                                                           @if ($detail->day == "Sunday")
                                                            <tr>
                                                                <td><input type="text" name="day[]" class="form-control" value="Sunday" readonly>
                                                                    <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="{{$detail->id}}" >
                                                                </td>
                                                                <td><input type="time" name="start_time[]" class="form-control" value="{{$detail->start_time}}"></td>
                                                                <td><input type="time" name="end_time[]" class="form-control" value="{{$detail->end_time}}"></td>
                                                                <td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td>
                                                            </tr>
                                                           @endif 
                                                        

                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 mx-auto text-center">
                                                <button id="clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                                                <button id="upButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                                <!-- Prorota Form-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>

<!-- Image preview start -->
<script>
    document.getElementById('pic').addEventListener('change', function(event) {
        var file = event.target.files[0];
        var reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
</script>
<!-- Image preview end -->

<!-- Prorota Start -->
<script>
    $(document).ready(function () {

        $('#upButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/prorota/update')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                        swal({
                            title: "Success!",
                            text: "Staff schedule updated successfully",
                            icon: "success",
                            button: "OK",
                        });
                        console.log(response)
                    // setTimeout(function() {
                    //     window.location.href = "{{ route('prorota') }}";
                    // }, 2000);

                },
                error: function (xhr, status, error) {
                    console.error("Error occurred: " + error);
                    if(xhr.responseJSON.status == 423){
                        console.log(xhr.responseJSON.errors);
                            $('#errorMessage').html(xhr.responseJSON.errors);
                            $('#errorMessage').show();
                            $('#successMessage').hide();
                    } else {
                        var errorMessage = "";

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += value.join(", ") + "<br>";
                            });

                            $('#errorMessage').html(errorMessage);
                        }
                        else {
                            errorMessage = "An error occurred. Please try again later.";
                            $('#errorMessage').html(errorMessage);
                        }
                            $('#errorMessage').show();
                            $('#successMessage').hide();
                    }
                    
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        });

        $('#clearButton').click(function () {
            event.preventDefault();
            $('#myForm')[0].reset();
        });
    });
</script>
<!-- Prorota End -->
<script>
    $(document).ready(function () {
        $(document).on('click', '.add-new-day', function () {
            var inputField = '<tr><td><input type="text" name="day[]" class="form-control" value=""> <input type="hidden" name="prorotaDetail_id[]" class="form-control" value="" </td><td><input type="time" name="start_time[]" class="form-control" value="10:00"></td><td><input type="time" name="end_time[]" class="form-control" value="18:00"></td><td><button class="btn btn-secondary remove-schedule" style="margin-left: 10px;" type="button">-</button></td></tr>';
            $('#scheduleContainer').append(inputField);
        });

        $(document).on('click', '.remove-schedule', function () {
            $(this).closest('tr').remove();
        });
    });
</script>
@endsection
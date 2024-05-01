@extends('admin.layouts.admin')

@section('content')
<link href="{{ asset('assets/css/customize2.css')}}" rel="stylesheet">

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> New Holiday Entry
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

                                <!-- Staff Form -->
                                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                                    <form id="myForm">
                                        <div class="row my-4">
                                            
                                            <div class="col-lg-4">
                                                <label for="country">Employee</label>
                                                <div class="mt-2">
                                                <select class="form-control select2 my-2" id="staff_id" name="staff_id">
                                                    <option value="" selected disabled>Choose Employee</option>
                                                    @foreach($staffs as $staff)
                                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                </div>
                                            </div>
                                            

                                        </div>
                                        <div class="row align-items-center">
                                            <div class="col-lg-8">
                                                
                                                <div class="p-5">
                                                    <h2 class="mb-4">Full Calendar</h2>
                                                    <div class="card">
                                                      <div class="card-body p-3">
                                                        <div id="calendar"></div>
                                                      </div>
                                                    </div>
                                                  </div>



                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 mx-auto text-center">
                                                <button id="clearButton" class="btn btn-sm btn-outline-dark">Clear</button>
                                                <button id="saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Save</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                                <!-- Staff Form-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- calendar modal -->
<div id="modal-view-event" class="modal modal-top fade calendar-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <h4 class="modal-title"><span class="event-icon"></span><span class="event-title"></span></h4>
                <div class="event-body"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="modal-view-event-add" class="modal modal-top fade calendar-modal">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
  <form id="add-event">
    <div class="modal-body">
    <h4>Add Event Detail</h4>        
      <div class="form-group">
        <label>Event name</label>
        <input type="text" class="form-control" name="ename">
      </div>
      <div class="form-group">
        <label>Event Date</label>
        <input type='text' class="datetimepicker form-control" name="edate">
      </div>        
      <div class="form-group">
        <label>Event Description</label>
        <textarea class="form-control" name="edesc"></textarea>
      </div>
      <div class="form-group">
        <label>Event Color</label>
        <select class="form-control" name="ecolor">
          <option value="fc-bg-default">fc-bg-default</option>
          <option value="fc-bg-blue">fc-bg-blue</option>
          <option value="fc-bg-lightgreen">fc-bg-lightgreen</option>
          <option value="fc-bg-pinkred">fc-bg-pinkred</option>
          <option value="fc-bg-deepskyblue">fc-bg-deepskyblue</option>
        </select>
      </div>
      <div class="form-group">
        <label>Event Icon</label>
        <select class="form-control" name="eicon">
          <option value="circle">circle</option>
          <option value="cog">cog</option>
          <option value="group">group</option>
          <option value="suitcase">suitcase</option>
          <option value="calendar">calendar</option>
        </select>
      </div>        
  </div>
    <div class="modal-footer">
    <button type="submit" class="btn btn-primary" >Save</button>
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>        
  </div>
  </form>
</div>
</div>
</div>


@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>



<!-- Staff Start -->
<script>
    $(document).ready(function () {

        $('#saveButton2').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "{{URL::to('/admin/prorota')}}",
                type: 'POST',
                data: formData,
                async: false,
                success: function (response) {
                        swal({
                            title: "Success!",
                            text: "Staff schedule created successfully",
                            icon: "success",
                            button: "OK",
                        });
                    setTimeout(function() {
                        window.location.href = "{{ route('prorota') }}";
                    }, 2000);
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
<!-- Staff End -->



@endsection
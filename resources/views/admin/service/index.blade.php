@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <button type="button" class="btn btn-secondary my-3" id="newBtn">Add new</button>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Main content -->
<section class="content" id="addThisFormContainer">
    <div class="container-fluid">
        <div class="row justify-content-md-center">
            <!-- right column -->
            <div class="col-md-6">
                <!-- general form elements disabled -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title" id="cardTitle">Add new service</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="ermsg"></div>
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" class="form-control" id="codeid" name="codeid">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Name of the service</label>
                                        <input type="text" class="form-control" id="name" name="name">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Sub services</label>
                                        <div id="subServicesContainer">
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" name="sub_services[]">
                                                <div class="input-group-append" style="margin-left: 10px;">
                                                    <button class="btn btn-secondary add-sub-service" type="button">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    </form>

                    <div class="card-footer">
                        <button type="submit" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
                        <button type="submit" id="FormCloseBtn" class="btn btn-default">Cancel</button>
                    </div>

                </div>


                <!-- /.card-body -->

                <!-- /.card-footer -->
                <!-- /.card-body -->
            </div>
        </div>
        <!--/.col (right) -->
    </div>
    <!-- /.row -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->


<!-- Main content -->
<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- /.card -->

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">All Services</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table cell-border table-striped">
                            <thead>
                                <tr>
                                    <th style="text-align: center">Sl</th>
                                    <th style="text-align: center">Name</th>
                                    <th style="text-align: center">Sub services</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $key => $data)
                                <tr>
                                    <td style="text-align: center">{{ $key + 1 }}</td>
                                    <td style="text-align: center">{{$data->name}}</td>
                                    <td style="text-align: center">
                                        @foreach($data->subServices as $subService)
                                        {{ $subService->name }}
                                        @if(!$loop->last)
                                        ,
                                        @endif
                                        @endforeach
                                    </td>
                                    <td style="text-align: center">
                                        <a class="btn btn-link" id="EditBtn" rid="{{$data->id}}"><i class="fa fa-edit" style="font-size: 20px;"></i></a>
                                        <a class="btn btn-link d-none" id="deleteBtn" rid="{{$data->id}}"><i class="fas fa-trash" style="color: red; font-size: 20px;"></i></a>
                                    </td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<style>
    .is-invalid {
        border-color: red;
    }
</style>

@endsection
@section('script')

<script>
    $(function() {
        $("#example1").DataTable();
    });
</script>

<script>
    $(document).ready(function() {
        $("#addThisFormContainer").hide();
        $("#newBtn").click(function() {
            clearform();
            $("#newBtn").hide(100);
            $("#addThisFormContainer").show(300);

        });
        $("#FormCloseBtn").click(function() {
            $("#addThisFormContainer").hide(200);
            $("#newBtn").show(100);
            //   clearform();
            location.reload();
        });
        //header for csrf-token is must in laravel
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        //
        var url = "{{URL::to('/admin/service')}}";
        var upurl = "{{URL::to('/admin/service-update')}}";
        // console.log(url);
        $("#addBtn").click(function() {
            if ($(this).val() == 'Create') {
                var form_data = new FormData();
                form_data.append("name", $("#name").val());
                var subServiceNames = $('input[name="sub_services[]"]').map(function() {
                    return $(this).val();
                }).get();

                $.ajax({
                    url: url,
                    method: "POST",
                    contentType: false,
                    processData: false,
                    data: form_data,
                    success: function(d) {
                        if (d.status == 303) {
                        } else if (d.status == 300) {
                            var serviceId = d.service_id;

                            $.ajax({
                                url: "/admin/sub-service",
                                method: "POST",
                                data: {
                                    service_id: serviceId,
                                    sub_services: subServiceNames
                                },
                                success: function(d) {
                                    if (d.status == 303) {
        
                                    } else if (d.status == 300) {
                    
                                        toastr.success("Created successfully", "Success");

                                        window.setTimeout(function() {
                                            location.reload()
                                        }, 2000)
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error occurred: " + error);
                                    console.error(xhr.responseText);
                                }
                            });

                        }
                    },
                    error: function(d) {
                        console.log(d);
                    }
                });
            }

            if ($(this).val() == 'Update') {

                var allValid = true;
                $('input[name="sub_services[]"]').each(function() {
                    if ($(this).val().trim() === '') {
                        allValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!allValid) {
                    $(".ermsg").html("Please fill in all sub service fields.");
                    return;
                }

                var form_data = new FormData();
                form_data.append("name", $("#name").val());
                form_data.append("codeid", $("#codeid").val());

                var currentSubServiceNames = $('input[name="sub_services[]"]').map(function() {
                    return $(this).val();
                }).get();
                var currentSubServiceIds = $('input[name="sub_services_id[]"]').map(function() {
                    return $(this).val();
                }).get();

                //   console.log(currentSubServiceNames);

                currentSubServiceNames.forEach(function(name) {
                    form_data.append("current_sub_service_names[]", name);
                });
                currentSubServiceIds.forEach(function(id) {
                    form_data.append("current_sub_service_ids[]", id);
                });

                $.ajax({
                    url: upurl,
                    type: "POST",
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    success: function(d) {
                        console.log(d);
                        if (d.status == 303) {
                        } else if (d.status == 300) {
        
                            toastr.success("Updated successfully", "Success");

                            window.setTimeout(function() {
                                location.reload()
                            }, 2000)
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }
        });
        //Edit
        $("#contentContainer").on('click', '#EditBtn', function() {
            //alert("btn work");
            codeid = $(this).attr('rid');
            //console.log($codeid);
            info_url = url + '/' + codeid + '/edit';
            //console.log($info_url);
            $.get(info_url, {}, function(d) {
                // console.log(d);
                populateForm(d);
            });
        });
        //Edit  end
        //Delete 
        $("#contentContainer").on('click', '#deleteBtn', function() {
            if (!confirm('Sure?')) return;
            codeid = $(this).attr('rid');
            info_url = url + '/' + codeid;
            $.ajax({
                url: info_url,
                method: "GET",
                type: "DELETE",
                data: {},
                success: function(d) {
                    if (d.success) {
                        // alert(d.message);
                        toastr.success("Deleted successfully", "Success");

                        window.setTimeout(function() {
                            location.reload()
                        }, 2000)
                    }
                },
                error: function(xhr) {
                    let errorMessage = "An error occurred.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, "Error");

                }
            });
        });
        //Delete 

        function populateForm(data) {
            console.log(data);
            $("#name").val(data.name);
            $("#tag").val(data.tag);
            $("#codeid").val(data.id);
            $("#addBtn").val('Update');
            $("#addBtn").html('Update');
            $("#cardTitle").html('Update this service');
            $("#addThisFormContainer").show(300);
            $("#newBtn").hide(100);

            $('#subServicesContainer').empty();

            if (data.sub_services && data.sub_services.length > 0) {
                $('#subServicesContainer').show();

                (data.sub_services || []).forEach(function(subService) {
                    var inputField = '<div class="input-group mb-3">' +
                        '<input type="text" class="form-control" name="sub_services[]" value="' + subService.name + '">' +
                        '<input type="hidden" class="form-control" name="sub_services_id[]" value="' + subService.id + '">' +
                        '<div class="input-group-append">' +
                        '<button class="btn btn-secondary remove-sub-service" style="margin-left: 10px;" type="button">-</button>' +
                        '</div>' +
                        '</div>';
                    $('#subServicesContainer').append(inputField);
                });

                var addSubServiceButton = '<button class="mb-3 btn btn-secondary add-sub-service" style="margin-top: 10px;" type="button">+</button>';
                $('#subServicesContainer').append(addSubServiceButton);
            } else {
                $('#subServicesContainer').empty();
                var addSubServiceButton = '<button class="mb-3 btn btn-secondary add-sub-service" style="margin-top: 10px;" type="button">+</button>';
                $('#subServicesContainer').append(addSubServiceButton);
            }
        }

        function clearform() {
            $('#createThisForm')[0].reset();
            $("#addBtn").val('Create');
            $("#cardTitle").html('Add new service');
        }
    });
</script>

<!-- Sub service + - start -->
<script>
    $(document).ready(function() {
        $(document).on('click', '.add-sub-service', function() {
            var inputField = '<div class="input-group mb-3">' +
                '<input type="text" class="form-control" name="sub_services[]" required>' +
                '<div class="input-group-append">' +
                '<button class="btn btn-secondary remove-sub-service" style="margin-left: 10px;" type="button">-</button>' +
                '</div>' +
                '</div>';
            $('#subServicesContainer').append(inputField);
        });

        $(document).on('click', '.remove-sub-service', function() {
            var $this = $(this);
            var subServiceId = $this.closest('.input-group').find('input[name="sub_services_id[]"]').val();
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/admin/check-sub-service-assignment',
                method: 'POST',
                data: {
                    id: subServiceId,
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.assigned) {
                        toastr.error("You can't remove this sub-service as it is assigned to a client.", "Error");

                    } else {
                        $this.closest('.input-group').remove();
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Sub service + - start -->

@endsection
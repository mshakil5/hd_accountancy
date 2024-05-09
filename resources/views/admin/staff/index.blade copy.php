@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm border-theme border-2">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> All Staffs
            </p>
            <div class="row px-3">
                {{-- <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div> --}}
                <div class="col-lg-12 p-3 text-end">
                    <a href="{{ route('createStaff') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Staff</a>
                </div>
            </div>
            <p class="sub-box-header" class="">
                <i class='bx bxs-user-plus fs-4 me-2'></i>
                <span>staff details</span>
            </p>
            <div class="row">
                <div class="col-lg-6">
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-bordered table-striped" id="staffsTable">
                        <thead>
                            <tr>
                                <th scope="col">Sl</th>
                                <th scope="col">Name</th>
                                <th scope="col">Phone</th>
                                <!-- <th scope="col">Email</th> -->
                                <th scope="col">Status</th> 
                                <th scope="col">Action</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6 ps-0" style="border-left:1px solid #233969;">

                <!-- Default Tabs -->
                <ul class="border  nav nav-tabs d-flex border-theme" id="myTabjustified" role="tablist">
                <li class="nav-item flex-fill" role="presentation">
                    <button class="nav-link w-100 text-capitalize active" id="home-tab" data-bs-toggle="tab"
                    data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">job
                    profile</button>
                </li>
                <li class="nav-item flex-fill" role="presentation">
                    <button class="nav-link w-100 text-capitalize" id="profile-tab" data-bs-toggle="tab"
                    data-bs-target="#profile" type="button" role="tab" aria-controls="profile"
                    aria-selected="false">other Information</button>
                </li>

                </ul>

                <div class="tab-content pt-2" id="myTabjustifiedContent">
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                    <form action="">
                        <div class="row my-4">
                        <div class="col-lg-6 ">
                            <div class="px-2">
                            <h2 class="txt-theme">Uma Khan</h1>
                                <h4 class="txt-theme">Director, Client Service</h1>
                                <h5 class="txt-theme">072125487</h1>
                                    <h6 class="txt-theme fw-bold">Uma Khan@hd-account.com</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 text-end">
                            <div>
                            <img width="160" src="https://picsum.photos/seed/picsum/200/200"
                                class="border-theme border-2 rounded-3 me-2 mb-3">
                            </div>
                            <div class="col-12">
                            <div class="row px-3">
                                <div class="col-lg-4 d-flex align-items-center justify-content-end">
                                <small>sort by: </small>
                                </div>
                                <div class="col-lg-8">
                                <select name="" id="" class="form-control">
                                    <option value="">last 7 days</option>
                                </select>
                                </div>
                            </div>


                            </div>
                        </div>

                        </div>
                        <div class="sub-box-header txt-theme">
                        <div class="row w-100">
                            <div class="col-lg-6 d-flex">
                            <span>Task completed</span>
                            </div>
                            <div class="col-lg-6 text-end">
                            count:3
                            </div>
                        </div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 1</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 2</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 3</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>
                        <div class="sub-box-header txt-theme">
                        <div class="row w-100">
                            <div class="col-lg-6 d-flex">
                            <span>Work in Process</span>
                            </div>
                            <div class="col-lg-6 text-end">
                            count:2
                            </div>
                        </div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 1</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 2</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>      
                        <div class="sub-box-header txt-theme">
                        <div class="row w-100">
                            <div class="col-lg-6 d-flex">
                            <span>Due task</span>
                            </div>
                            <div class="col-lg-6 text-end">
                            count:1
                            </div>
                        </div>
                        </div>      
                        <div class="row offset-1 my-2 txt-theme">
                        <div class="col-lg-3">job 1</div>
                        <div class="col-lg-3">assign by</div>
                        <div class="col-lg-3">assign date</div>
                        <div class="col-lg-3">due date</div>
                        </div>
                    </form>
                    </div>
                    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row my-4">
                        <form action="">
                        <div class="row my-4">
                            <div class="col-lg-6 ">   </div>
                            <div class="col-lg-6 text-center">
                            <div class="img mb-2">
                                <img width="160" src=" https://picsum.photos/seed/picsum/200/200" class="border-theme border-2 rounded-3">
                            </div>
                            <i class="bi bi-cloud-upload"></i>
                            <label for="pic">
                                <big class="txt-theme ">Upload Logo / Photo</big>
                            </label>
                            <input type="file" name="" id="pic" class="invisible ">
                            </div>
    
                        </div>
                        <div class="sub-box-header">
                            <div class="row w-100">
                            <div class="col-lg-6 d-flex txt-theme">
                                <span>Personal Information</span>
                            </div>
                            <div class="col-lg-6 text-end">
                                <a href="#" class="txt-theme"><i class="bi bi-pencil-square"></i>  Edit</a>
                            </div>
                            </div>
                        </div>      
                        <div class="row px-3 my-2 txt-theme">
                            <div class="col-lg-6">
                            <table class="w-100 text-capitalize">
                                <tr>
                                <td><b>First name</b></td>
                                <td>:uma</td>
                                </tr>
                                <tr>
                                <td><b>Last name</b></td>
                                <td>:uma</td>
                                </tr>
                                <tr>
                                <td><b>Phone</b></td>
                                <td>:0124521</td>
                                </tr>
                                <tr>
                                <td><b>Email</b></td>
                                <td class="text-lowercase">:email@gmail.com</td>
                                </tr>
                                <tr>
                                <td><b>Phone</b></td>
                                <td>:0124521</td>
                                </tr>
                                <tr>
                                <td><b>NI number</b></td>
                                <td>:02312345</td>
                                </tr>
                            </table>
                            </div>
                            <div class="col-lg-6">
                            <table class="w-100 txt-theme text-capitalize">
                                <tr>
                                <td><b>Date of birth</b></td>
                                <td>:9/5/2024</td>
                                </tr>
                                <tr>
                                <td><b>Add 1</b></td>
                                <td>:Kngs road</td>
                                </tr>
                                <tr>
                                <td><b>Add 2</b></td>
                                <td>:0124521</td>
                                </tr>
                                
                            </table>
                            </div>
                        </div>      
                        <div class="sub-box-header">
                            <div class="row w-100">
                            <div class="col-lg-6 d-flex txt-theme">
                                <span>Job Information</span>
                            </div>
                            <div class="col-lg-6 text-end">
                                <a href="#" class="txt-theme"><i class="bi bi-pencil-square"></i>  Edit</a>
                            </div>
                            </div>
                        </div>      
                        <div class="row px-3 my-2 txt-theme">
                            <div class="col-lg-6">
                            <table class="w-100 text-capitalize">
                                <tr>
                                <td><b>Department</b></td>
                                <td>:uma</td>
                                </tr>
                                <tr>
                                <td><b>job title</b></td>
                                <td>:xyz</td>
                                </tr> 
                                <tr>
                                <td><b>emp status</b></td>
                                <td class="text-lowercase">:email@gmail.com</td>
                                </tr>
                                
                            </table>
                            </div>
                            <div class="col-lg-6">
                            <table class="w-100 txt-theme text-capitalize">
                                <tr>
                                <td><b>reporting ID</b></td>
                                <td>2263</td>
                                </tr>
                                <tr>
                                <td><b>join date</b></td>
                                <td>:4/4/24</td>
                                </tr>
                                <tr>
                                <td><b>reporting to</b></td>
                                <td>:xyz</td>
                                </tr>
                                
                            </table>
                            </div>
                        </div>      
                        
                        </form>
                    </div>
                    </div>
                </div>
                <!-- End Default Tabs -->
            </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')

<script>
  $(document).ready(function() {
    var table = $('#staffsTable').DataTable({
        serverSide: true,
        ajax: "{{ route('get.Stuffs') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {
                data: null,
                name: 'name',
                render: function(data, type, full, meta) {
                    var fullName = '';
                    if (full.first_name && full.last_name) {
                        fullName = full.first_name + ' ' + full.last_name;
                    } else if (full.first_name) {
                        fullName = full.first_name;
                    } else if (full.last_name) {
                        fullName = full.last_name;
                    }
                    return fullName;
                }
            },
            {data: 'phone', name: 'phone'},
            // {data: 'email', name: 'email'},
            {
                data: 'status',
                name: 'status',
                render: function(data, type, full, meta) {
                    var statusClass = data ? 'btn btn-secondary' : 'btn btn-danger';
                    var statusText = data ? 'Active' : 'Inactive';
                    return '<button class="' + statusClass + '" onclick="changeStatus(' + full.id + ')">' + statusText + '</button>';
                }
            },
            {
                data: 'id',
                name: 'details',
                render: function(data, type, full, meta) {
                    var editButtonHtml = '<a href="{{ url('admin/staff/details') }}/' + data + '" class="btn btn-secondary"><i class="fa fa-edit"></i></a>';
                    var deleteButtonHtml = '<a href="#" class="btn btn-danger delete-staff" data-staff-id="' + data + '" style="margin-left: 10px;"><i class="fas fa-trash"></i></a>';

                    return editButtonHtml + deleteButtonHtml;
                }
            }
        ]
    });
  });
</script>


{{-- Delete staff start --}}
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-staff', function(e) {
            e.preventDefault();
            var staffId = $(this).data('staff-id');

            if (confirm("Are you sure you want to delete this staff member?")) {
                $.ajax({
                    url: '/admin/delete-staff/' + staffId, 
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            swal({
                                title: "Success!",
                                text: "Staff deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            $('#staffsTable').DataTable().ajax.reload();
                        } else {
                                Toastify({
                                    text: "Failed to delete."
                                }).showToast();
                            }
                    },
                    error: function(xhr, status, error) {
                    
                    }
                });
            }
        });
    });
</script>
{{-- Delete staff start --}}

<!-- Staff status change start -->
<script>
    function changeStatus(userId) {
        $.ajax({
            url: "{{ route('staff.change.status') }}",
            method: "POST",
            data: {
                user_id: userId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#staffsTable').DataTable().ajax.reload();
                    Toastify({
                        text: "Status changed successfully!"
                    }).showToast();
                } else {
                    Toastify({
                        text: "Failed to change status."
                    }).showToast();
                }
            }
        });
    }
</script>
<!-- Staff status change end -->

@endsection
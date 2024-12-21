@extends('manager.layouts.manager')

@section('content')

<section class="section">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class='bx bxs-user-plus fs-4 me-2'></i> Update Profile
            </p>

            <div class="row px-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="myForm">
                                <div class="row my-4">
                                    <div class="col-lg-9">
                                    </div>
                                    <div class="col-lg-3 text-center">
                                        <div class="img mb-2">
                                            <img src="{{ $manager->image ? asset('images/manager/' . $manager->image) : asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
                                        </div>
                                        <label for="pic" class="mb-0" style="cursor: pointer;">
                                            <i class="bi bi-cloud-upload"></i>
                                            <small>Update Image</small>
                                        </label>
                                        <input type="file" id="pic" name="image" class="invisible">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">First Name</label>
                                        <input type="text" class="form-control my-2" id="first_name" name="first_name" value="{{ $manager->first_name }}" placeholder="Enter first name">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Last Name</label>
                                        <input type="text" class="form-control my-2" id="last_name" name="last_name" value="{{ $manager->last_name }}" placeholder="Enter last name">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Phone</label>
                                        <input type="number" class="form-control my-2" id="phone" name="phone" value="{{ $manager->phone }}" placeholder="Enter phone">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Email</label>
                                        <input type="email" class="form-control my-2" id="email" name="email" value="{{ $manager->email }}" placeholder="Enter email">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">NI Number</label>
                                        <input type="text" class="form-control my-2" id="ni_number" name="ni_number" value="{{ $manager->ni_number }}" placeholder="Enter NI number">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Date of Birth</label>
                                        <input type="date" class="form-control my-2" id="date_of_birth" name="date_of_birth" value="{{ $manager->date_of_birth }}" placeholder="Enter date of birth">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Address line 1</label>
                                        <input type="text" class="form-control my-2" id="address_line1" name="address_line1" value="{{ $manager->address_line1}}" placeholder="Enter address line 1">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Address line 2</label>
                                        <input type="text" class="form-control my-2" id="address_line2" name="address_line2" value="{{ $manager->address_line2}}" placeholder="Enter address line 2">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Address line 3</label>
                                        <input type="text" class="form-control my-2" id="address_line3" name="address_line3" value="{{ $manager->address_line3}}" placeholder="Enter address line 3">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Town</label>
                                        <input type="text" class="form-control my-2" id="town" name="town" value="{{ $manager->town}}" placeholder="Enter town">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Post Code</label>
                                        <input type="text" class="form-control my-2" id="postcode" name="postcode" value="{{ $manager->postcode}}" placeholder="Enter post code">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Password</label>
                                        <input type="password" class="form-control my-2" id="password" name="password" placeholder="Enter password">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="">Confirm Password</label>
                                        <input type="password" class="form-control my-2" id="confirm_password" name="confirm_password" placeholder="Enter confirm password">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-4 mx-auto text-center">
                                        <button id="saveButton" class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
                                    </div>
                                </div>
                            </form>
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
    $.ajaxSetup({ 
        headers: { 
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
        } 
    });

    document.getElementById('pic').addEventListener('change', function(event) {
        var file = event.target.files[0];
        var reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };

        reader.readAsDataURL(file);
    });

    $(document).ready(function () {
        $('#saveButton').click(function (event) {
            event.preventDefault();

            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "/manager/profile",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false
            }).done(function (response) {
                swal({
                    title: "Success!",
                    text: "Updated successfully",
                    icon: "success",
                    button: "OK",
                });
                window.setTimeout(function() {
                    location.reload();
                }, 2000);
            }).fail(function (xhr) {
                var errorMessage = "An error occurred. Please try again later.";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = "Please fix the following errors:<br>";
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        errorMessage += key + ": " + value.join(", ") + "<br>";
                    });
                }
                swal({
                    title: "Error!",
                    text: errorMessage,
                    icon: "error",
                    button: "OK",
                });
            });
        });
    });
</script>

@endsection

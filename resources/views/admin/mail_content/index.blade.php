@extends('admin.layouts.admin')

@section('content')

<style>
    table {
        border-collapse: collapse;
        border-spacing: 0;
        width: 100%;
        border: 1px solid #ddd;
    }
    th, td {
        text-align: left;
        padding: 8px;
    }
    tr:nth-child(even){background-color: #f2f2f2}
</style>

<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-dashboard"></i> Mail Content</h1>
        </div>
    </div>
    <div id="addThisFormContainer">
        <div class="row">
            <div class="col-md-3">
            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <!-- <h3>New Mail Content</h3> -->
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="ermsg">
                            </div>
                            <div class="container">
                                <form action="{{ url('admin/mail-content') }}" method="POST" id="createThisForm">
                                    <input type="hidden" name="codeid" id="codeid" value="">
                                    @csrf
                                    <div>
                                        <label for="title">Title</label>
                                        <input type="text" id="title" name="title" class="form-control">
                                    </div>
                                    <div>
                                        <label for="content">Content</label>
                                        <textarea class="form-control" id="content" name="content" rows="3" placeholder="Enter content"></textarea>
                                    </div>
                                    <hr>
                                    <input type="button" id="addBtn" value="Create" class="btn btn-primary">
                                    <input type="button" id="FormCloseBtn" value="Close" class="btn btn-warning">
                                    <div class="loader text-center" style="display: none;">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <button id="newBtn" type="button" class="btn btn-info">Add New</button> -->
    <hr>
    <div id="contentContainer">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <!-- <h3>All Mail Content</h3> -->
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover" id="example">
                            <thead>
                            <tr>
                                <th style="text-align: center">Title</th>
                                <th style="text-align: center">Content</th>
                                <th style="text-align: center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="text-align: center">{{$data->title}}</td>
                                    <td style="text-align: center">{!!$data->content!!}</td>
                                    <td style="text-align: center">
                                        <a id="EditBtn" rid="{{$data->id}}"><i class="fa fa-edit" style="color: #2196f3;font-size:16px;"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('script')
<script src="//cdn.ckeditor.com/4.13.0/standard/ckeditor.js"></script>
<script>
    CKEDITOR.config.versionCheck = false;
</script>
    <script>
        $(document).ready(function () {

            function clearForm() {
                $('#createThisForm')[0].reset();
                $("#addBtn").val('Create');
            }

            $("#addThisFormContainer").hide();

            $("#newBtn").click(function () {

                $("#content").addClass("ckeditor");
                for ( instance in CKEDITOR.instances ) {
                    CKEDITOR.instances[instance].updateElement();
                } 
                CKEDITOR.replace( 'content' );

                clearForm();
                $("#newBtn").hide(100);
                $("#addThisFormContainer").show(300);
            });

            $("#FormCloseBtn").click(function () {
                $("#addThisFormContainer").hide(200);
                $("#newBtn").show(100);
                clearForm();
            });

            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            var url = "{{ route('admin.mail-content') }}";

            $("#addBtn").click(function () {
                if ($(this).val() == 'Create') {
                    var form_data = new FormData();
                    form_data.append("title", $("#title").val());
                    form_data.append("content", $("#content").val());

                    $.ajax({
                        url: url,
                        method: "POST",
                        contentType: false,
                        processData: false,
                        data: form_data,
                        beforeSend: function() {
                            document.querySelector('.loader').style.display = 'block';
                            document.getElementById('addBtn').disabled = true;
                        },
                        success: function (d) {
                            document.querySelector('.loader').style.display = 'none';
                            document.getElementById('addBtn').disabled = false;
                            if (d.status == 303) {
                                $(".ermsg").html(d.message);
                            } else if (d.status == 300) {
                                success("Data Inserted Successfully!!");
                                window.setTimeout(function () { location.reload() }, 2000);
                            }
                        },
                        error: function (d) {
                            document.querySelector('.loader').style.display = 'none';
                            document.getElementById('addBtn').disabled = false;
                            console.log(d);
                        }
                    });
                }

                if ($(this).val() == 'Update') {

                    for ( instance in CKEDITOR.instances ) {
                    CKEDITOR.instances[instance].updateElement();
                    }

                    var form_data = new FormData();
                    form_data.append("title", $("#title").val());
                    form_data.append("content", $("#content").val());
                    form_data.append("_method", "PUT");

                    $.ajax({
                        url: url + '/' + $("#codeid").val(),
                        method: "POST",
                        contentType: false,
                        processData: false,
                        data: form_data,
                        beforeSend: function() {
                            document.querySelector('.loader').style.display = 'block';
                            document.getElementById('addBtn').disabled = true;
                        },
                        success: function (d) {
                            document.querySelector('.loader').style.display = 'none';
                            document.getElementById('addBtn').disabled = false;
                            if (d.status == 303) {
                                $(".ermsg").html(d.message);
                                pagetop();
                            } else if (d.status == 300) {
                                $(".ermsg").html(d.message);
                                window.setTimeout(function () { location.reload() }, 2000);
                            }
                        },
                        error: function (d) {
                            document.querySelector('.loader').style.display = 'none';
                            document.getElementById('addBtn').disabled = false;
                            console.log(d);
                        }
                    });
                }
            });

            $("#contentContainer").on('click', '#EditBtn', function () {
                var codeid = $(this).attr('rid');
                var info_url = url + '/' + codeid + '/edit';

                $.get(info_url, {}, function (d) {
                    for ( instance in CKEDITOR.instances ) {
                    CKEDITOR.instances[instance].updateElement();
                    } 
                $("#content").val(d.content);

                 CKEDITOR.replace( 'content' );

                    $("#title").val(d.title);
                    $("#content").val(d.content);
                    $("#codeid").val(d.id);
                    $("#addBtn").val('Update');
                    $("#addThisFormContainer").show(300);
                    $("#newBtn").hide(100);
                });
            });

        });
    </script>

@endsection
@extends('admin.layouts.admin')

@section('content')

<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h2>Today's Task Details for {{ $staffName }}</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Client Name</th>
                                    <th>Sub Service Name</th>
                                    <th>Note</th>
                                    <th>Sequence Status</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clientSubServices as $service)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ optional($service->client)->name }}</td>
                                    <td>{{ optional($service->subService)->name }}</td>
                                    <td>
                                        <span>{!! $service->note !!}</span>
                                    </td>
                                    <td>
                                        <select class="form-control" id="sequence_status">
                                            <option value="0" {{ $service->sequence_status == 0 ? 'selected' : '' }}>Processing</option>
                                            <option value="1" {{ $service->sequence_status == 1 ? 'selected' : '' }}>Work Not Started</option>
                                            <option value="2" {{ $service->sequence_status == 2 ? 'selected' : '' }}>Work Completed</option>
                                        </select>
                                    </td>
                                    <td>{{ optional($service->workTimes->first())->start_time ? \Carbon\Carbon::parse($service->workTimes->first()->start_time)->format('d. m. Y - H:i:s') : '' }}</td>
                                    <td>{{ optional($service->workTimes->first())->end_time ? \Carbon\Carbon::parse($service->workTimes->first()->end_time)->format('d. m. Y - H:i:s') : '' }}</td>
                                    <td>
                                        <button class="btn btn-primary update-btn" data-service-id="{{ $service->id }}">Update</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-lg-4 mx-auto text-center">
                                <a href="{{route('prevLogStaffs')}}" class="btn btn-sm btn-outline-dark">Back</a>
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
    $(document).ready(function() {
        $('.update-btn').click(function() {
            var clientSubServiceId = $(this).data('service-id');
             var sequenceStatus = $('#sequence_status').val();
             console.log(sequenceStatus); 

            $.ajax({
                url: '/admin/update-client-service/',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    clientSubServiceId: clientSubServiceId,
                    sequence_status: sequenceStatus
                },
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Updated successfully",
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


@endsection
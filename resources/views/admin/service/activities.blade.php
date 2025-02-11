@extends('admin.layouts.admin')

@section('content')
<section class="section">
    <div class="container-fluid">
        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="card shadow-sm border-theme border-2 pt-4">
            <div class="card-body">
                <h4>Activity Log for Service: {{ $service->name }}</h4>

                @if($serviceActivities->isNotEmpty())
                    <h5>Service Activity Log</h5>
                    @include('admin.partials.activities_table', ['activities' => $serviceActivities])
                @else
                    <p class="text-center">No activity found for this service.</p>
                @endif

                @if($subServiceActivities->isNotEmpty())
                    <h5>Sub-Service Activity Log</h5>
                    @include('admin.partials.activities_table', ['activities' => $subServiceActivities])
                @else
                    <p class="text-center">No activity found for this service's sub-services.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        $('.table-activities').DataTable();
    });
</script>
@endsection
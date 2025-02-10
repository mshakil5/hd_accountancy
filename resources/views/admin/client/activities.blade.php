@extends('admin.layouts.admin')

@section('content')
<section class="section">
    <div class="container-fluid">
        <a href="{{ route('allClient') }}" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="card shadow-sm border-theme border-2">
            <div class="card-body">

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="activityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="client-tab" data-bs-toggle="tab" data-bs-target="#client" type="button" role="tab" aria-controls="client" aria-selected="true">Client Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab" aria-controls="business" aria-selected="false">Business Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="accountancy-tab" data-bs-toggle="tab" data-bs-target="#accountancy" type="button" role="tab" aria-controls="accountancy" aria-selected="false">Accountancy Fees</button>
                    </li>
                </ul>
                <!-- Tabs end -->

                <!-- Tab Content -->
                <div class="tab-content pt-3" id="activityTabsContent">
                    <!-- Client Details Tab -->
                    <div class="tab-pane fade show active" id="client" role="tabpanel" aria-labelledby="client-tab">
                        @include('admin.client.partials.activities_table', ['activities' => $clientActivities])
                    </div>

                    <!-- Business Info Tab -->
                    <div class="tab-pane fade" id="business" role="tabpanel" aria-labelledby="business-tab">
                        @if($businessInfoActivities->isNotEmpty())
                            @include('admin.client.partials.activities_table', ['activities' => $businessInfoActivities])
                        @else
                            <p class="mt-3">No Business Info Activities found.</p>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="accountancy" role="tabpanel" aria-labelledby="accountancy-tab">
                        @if($accountancyFeeActivities->isNotEmpty())
                            @include('admin.client.partials.activities_table', ['activities' => $accountancyFeeActivities])
                        @else
                            <p class="mt-3">No Accountancy Fees Activities found.</p>
                        @endif
                    </div>
                </div>
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
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
                        <button class="nav-link" id="director-tab" data-bs-toggle="tab" data-bs-target="#director" type="button" role="tab" aria-controls="director" aria-selected="false">Director Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Contact Info</button>
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

                    <!-- Director Info Tab -->                
                    <div class="tab-pane fade" id="director" role="tabpanel" aria-labelledby="director-tab">
                        <div class="mt-1">
                            <label for="directorSelect">Select Director:</label>
                            <select class="form-control" id="directorSelect">
                                <option value="">-- Select Director --</option>
                                @foreach($directors as $director)
                                    <option value="{{ $director->id }}">{{ $director->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="directorActivitiesContainer" class="mt-1">
                            <p>Select a director to view activities.</p>
                        </div>
                    </div>

                    <!-- Contact Info Tab -->
                    <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                        <div class="mt-1">
                            <label for="contactSelect">Select Contact:</label>
                            <select class="form-control" id="contactSelect">
                                <option value="">-- Select Contact --</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="contactActivitiesContainer" class="mt-1">
                            <p>Select a contact to view activities.</p>
                        </div>
                    </div>

                    <!-- Accountancy Fees Tab -->
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

    $('#contactSelect').on('change', function () {
        const contactId = $(this).val();
        const activitiesContainer = $('#contactActivitiesContainer');

        activitiesContainer.html('');

        @foreach($contactActivities as $id => $activities)
            if (contactId == {{ $id }}) {
                @if($activities->isNotEmpty())
                    activitiesContainer.html(`{!! view('admin.client.partials.activities_table', ['activities' => $activities])->render() !!}`);
                @else
                    activitiesContainer.html('<p class="mt-3">No activities found for this contact.</p>');
                @endif
            }
        @endforeach

        if (!contactId) {
            activitiesContainer.html('<p>Select a contact to view activities.</p>');
        }
    });

    $('#directorSelect').on('change', function () {
        const directorId = $(this).val();
        const activitiesContainer = $('#directorActivitiesContainer');

        activitiesContainer.html('');

        @foreach($directorActivities as $id => $activities)
            if (directorId == {{ $id }}) {
                @if($activities->isNotEmpty())
                    activitiesContainer.html(`{!! view('admin.client.partials.activities_table', ['activities' => $activities])->render() !!}`);
                @else
                    activitiesContainer.html('<p class="mt-3">No activities found for this director.</p>');
                @endif
            }
        @endforeach

        if (!directorId) {
            activitiesContainer.html('<p>Select a director to view activities.</p>');
        }
    });

</script>
@endsection
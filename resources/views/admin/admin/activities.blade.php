@extends('admin.layouts.admin')

@section('content')
<section class="section">
    <div class="container-fluid">
      <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">
          <i class="fas fa-arrow-left"></i> Back
      </a>

        <div class="card shadow-sm border-theme border-2 pt-4">
            <div class="card-body">
                @if($userActivities->isNotEmpty())
                    @include('admin.client.partials.activities_table', ['activities' => $userActivities])
                @else
                    <p class="text-center">No activities found.</p>
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
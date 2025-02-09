@extends('admin.layouts.admin')

@section('content')

<section class="content" id="contentContainer">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <a href="{{ route('allClient') }}" class="btn btn-secondary mb-3">
          <i class="fas fa-arrow-left"></i> Back
        </a>

        <a href="{{ route('clientDetails.activities', $client->id) }}" class="btn btn-info mb-3">
          <i class="fas fa-user"></i> Client Details Activities
        </a>

        @if($client->businessInfo)

        <a href="{{ route('clientBusinessInfo.activities', $client->id) }}" class="btn btn-info mb-3">
          <i class="fas fa-briefcase"></i> Business Info Activities
        </a>

        @endif

        @if($client->accountancyFee)

        <a href="{{ route('clientAccountancyFees.activities', $client->id) }}" class="btn btn-info mb-3">
          <i class="fas fa-bank"></i> Accountancy Fee Activities
        </a>

        @endif

        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Accountancy Fee Activities for Client: {{ $client->name }}</h3>
          </div>
          <div class="card-body">
            <table id="activityTable" class="table cell-border table-striped">
              <thead>
                <tr>
                  <th>Sl</th>
                  <th>Date</th>
                  <th>Performed By</th>
                  <th>Changed Fields</th>
                </tr>
              </thead>
              <tbody>
                @foreach($activities as $index => $activity)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $activity->created_at->format('d-m-Y H:i:s') }}</td>
                  <td>{{ $activity->causer ? $activity->causer->name : 'Unknown' }}</td>
                  <td>
                    @php
                    $properties = json_decode($activity->properties, true);
                    $oldAttributes = $properties['old'] ?? [];
                    $newAttributes = $properties['attributes'] ?? [];
                    @endphp
                    <ul>
                      @foreach($oldAttributes as $key => $oldValue)
                      @if(array_key_exists($key, $newAttributes) && $oldValue != $newAttributes[$key])
                      <li>
                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                        <span class="text-danger">Old: {{ $oldValue }}</span>
                        <span class="text-success">New: {{ $newAttributes[$key] }}</span>
                      </li>
                      @endif
                      @endforeach
                    </ul>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

@endsection

@section('script')

<script>
  $(function() {
    $("#activityTable").DataTable();
  });
</script>

@endsection
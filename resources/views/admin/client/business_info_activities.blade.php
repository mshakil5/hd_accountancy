@extends('admin.layouts.admin')

@section('content')
<a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">
    <i class="fas fa-arrow-left"></i> Back
</a>

<h3>Business Info Activities for Client: {{ $client->name }}</h3>

<table id="activityTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>#</th>
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
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('#activityTable').DataTable();
    });
</script>
@endsection
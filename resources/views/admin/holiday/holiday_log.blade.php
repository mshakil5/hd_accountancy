@extends('admin.layouts.admin')

@section('content')
<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="{{ route('holiday') }}" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

                <div class="card card-secondary border-theme border-2">
                    <div class="card-header">
                        <h3 class="card-title">Holiday Log</h3>
                    </div>
                    <div class="card-body">
                        <table id="holidayLogTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Date</th>
                                    <th>Performed By</th>
                                    <th>Changes</th>
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
                                                $old = $properties['old'] ?? [];
                                                $new = $properties['attributes'] ?? [];
                                            @endphp
                                            <ul>
                                                @foreach($old as $key => $oldValue)
                                                    @if(isset($new[$key]) && $oldValue != $new[$key])
                                                        <li>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                            <span class="text-danger">Old: {{ $oldValue }}</span> → 
                                                            <span class="text-success">New: {{ $new[$key] }}</span>
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
        $('#holidayLogTable').DataTable();
    });
</script>
@endsection
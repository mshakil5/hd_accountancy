@extends('admin.layouts.admin')

@section('content')
<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="{{ url('admin/prorota') }}" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

                <!-- Prorota Log -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Prorota Log</h3>
                    </div>
                    <div class="card-body">
                        <table id="prorotaLogTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Date</th>
                                    <th>Performed By</th>
                                    <th>Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prorotaLogs as $index => $activity)
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

                <!-- Prorota Detail Log -->
                <div class="card card-info mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Prorota Detail Log</h3>
                    </div>
                    <div class="card-body">
                        <table id="prorotaDetailLogTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Date</th>
                                    <th>Performed By</th>
                                    <th>Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prorotaDetailLogs as $index => $activity)
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
        $('#prorotaLogTable').DataTable();
        $('#prorotaDetailLogTable').DataTable();
    });
</script>
@endsection
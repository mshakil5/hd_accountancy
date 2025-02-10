<table class="table table-striped table-activities">
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
                <td>{{ $activity->causer->name ?? 'Unknown' }}</td>
                <td>
                    @php
                        $properties = json_decode($activity->properties, true);
                        $oldAttributes = $properties['old'] ?? [];
                        $newAttributes = $properties['attributes'] ?? [];
                    @endphp
                    <ul>
                        @foreach($oldAttributes as $key => $oldValue)
                            @if(isset($newAttributes[$key]) && $oldValue != $newAttributes[$key])
                                <li>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                    <span class="text-danger">Old: {{ $oldValue }}</span>,
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
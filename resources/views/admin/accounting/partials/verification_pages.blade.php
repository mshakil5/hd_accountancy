<div class="hd-card">
    <h5>Page coverage</h5>
    <div class="hd-scroll">
        <table class="hd-table">
            <thead><tr><th>Page</th><th>Extracted rows</th><th>Text length</th><th>Result</th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>{{ $page->page_no }}</td>
                    <td>{{ $page->extracted_rows }}</td>
                    <td>{{ $page->text_length }}</td>
                    <td>
                        <span class="hd-pill {{ $page->status === 'verified' ? 'ok' : ($page->status === 'review' ? 'blocking' : 'warning') }}">
                            {{ ucwords(str_replace('_', ' ', $page->status)) }}
                        </span>
                        @if($page->note)<div class="hd-small">{{ $page->note }}</div>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="hd-small">No page checks recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

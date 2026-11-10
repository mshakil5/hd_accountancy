@extends('admin.layouts.admin')
@section('content')
<div class="pagetitle d-flex justify-content-between align-items-center">
    <h1>Campaign / Ad intent mapping</h1>
    <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Back to leads</a>
</div>
<section class="section">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="alert alert-info">
        Campaign intent is controlled here, <strong>not</strong> guessed from advert copy. A known Ad ID routes straight to its
        qualification path and skips the first question. An advert with no mapping is treated as a general advert: the bot asks
        <em>"What would you like help with?"</em>
    </div>

    <div class="card">
        <div class="card-body pt-3">
            <h5 class="card-title">Add a mapping</h5>
            <p class="small text-muted">A re-used Ad ID is rejected — edit the existing row below instead.</p>
            <form method="POST" action="{{ route('admin.leads.ad-intents.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Ad ID</label>
                    <input class="form-control" name="ad_id" value="{{ old('ad_id') }}" required placeholder="from Ads Manager">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Campaign ID (optional)</label>
                    <input class="form-control" name="campaign_id" value="{{ old('campaign_id') }}" placeholder="fallback when the ad is not mapped">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Intent</label>
                    <select class="form-select" name="intent" required>
                        @foreach($intents as $value => $label)
                            <option value="{{ $value }}" @selected(old('intent') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Note (optional)</label>
                    <input class="form-control" name="note" value="{{ old('note') }}">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary">Save</button>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-3">
            <h5 class="card-title">Current mappings</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Ad ID</th><th>Campaign ID</th><th>Intent</th><th>Note</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    @forelse($mappings as $mapping)
                        <tr>
                            <td><code>{{ $mapping->ad_id }}</code></td>
                            <td>{{ $mapping->campaign_id ?: '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.leads.ad-intents.update', $mapping) }}" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <input class="form-control form-control-sm" name="ad_id" value="{{ $mapping->ad_id }}" required style="max-width:140px">
                                    <input class="form-control form-control-sm" name="campaign_id" value="{{ $mapping->campaign_id }}" style="max-width:140px">
                                    <select class="form-select form-select-sm" name="intent" style="max-width:190px">
                                        @foreach($intents as $value => $label)
                                            <option value="{{ $value }}" @selected($mapping->intent === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input class="form-control form-control-sm" name="note" value="{{ $mapping->note }}" style="max-width:120px">
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($mapping->is_active)>
                                        <span class="form-check-label small">Active</span>
                                    </label>
                                    <button class="btn btn-sm btn-success">Update</button>
                                </form>
                            </td>
                            <td>{{ $mapping->note ?: '—' }}</td>
                            <td>@if($mapping->is_active)<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.leads.ad-intents.destroy', $mapping) }}" onsubmit="return confirm('Remove this mapping?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No adverts mapped. Every advert currently asks the first question.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@php

$staffs = App\Models\User::select('id', 'first_name', 'last_name', 'type')
->whereNot('id', auth()->user()->id)
->get();

@endphp

<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="chatModalLabel">Chat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" id="chatTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="message-history-tab" data-bs-toggle="tab" data-bs-target="#message-history" type="button" role="tab" aria-controls="message-history" aria-selected="true">
              Message History
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="new-message-tab" data-bs-toggle="tab" data-bs-target="#new-message" type="button" role="tab" aria-controls="new-message" aria-selected="false">
              New Message
            </button>
          </li>
        </ul>
        <div class="tab-content mt-3">
          <div class="tab-pane fade show active" id="message-history" role="tabpanel" aria-labelledby="message-history-tab">
            <div class="message-history-section p-3 border rounded bg-light">
              <div class="d-flex justify-content-between mb-2">
                <h6 class="text-primary">Messages:</h6>
                <div class="message-history-filter">
                  <label for="date-filter" class="form-label d-none">Filter by Date:</label>
                  <select id="date-filter" class="form-control" style="width: 150px;">
                    <option value="today" selected>Today</option>
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                  </select>
                </div>
              </div>
              <div id="messageList">
                <p class="text-muted">No messages available.</p>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="new-message" role="tabpanel" aria-labelledby="new-message-tab">
            <form id="newMessageForm">
              <div class="mb-3">
                <label for="recipient_id" class="form-label">Recipient: <span class="text-danger">*</span></label>
                <select id="recipient_id" class="form-control select2" style="width: 100%;">
                  <option value="">Select a recipient</option>
                  @foreach($staffs as $staff)
                  <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }} ({{ $staff->type }})</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message: <span class="text-danger">*</span></label>
                <textarea id="message" class="form-control" rows="4" placeholder="Write your message..."></textarea>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-primary btn-send-message">Send Message</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
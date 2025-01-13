<!DOCTYPE html>
<html lang="en">

@php
    $companyDetails = \App\Models\CompanyDetails::first();
@endphp

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>{{ $companyDetails->company_name ?? 'HD Accountancy' }}</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link rel="icon" href="{{ asset('images/company/' . $companyDetails->fav_icon) }}" />

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/select2/select2.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/jquery.dataTables.min.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="{{ asset('assets/vendor/datatables/buttons.dataTables.min.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/toastr/toastr.css')}}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/style.css')}}" rel="stylesheet">
  <link href="{{ asset('assets/css/customize.css')}}" rel="stylesheet">

</head>

<body>

  <!-- header -->

  @include('manager.partials.header')
  
  <!-- header -->

  <!-- Sidebar -->
  
   @include('manager.partials.sidebar')
  <!-- Sidebar-->

  <!-- Main -->
  <main id="main" class="main mt-3">

      @yield('content')

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
                    <h6 class="text-primary">Messages:</h6>
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

  </main>
  <!--Main -->

  <!--Footer-->

  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/jquery/jquery.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>
  <script src="{{ asset('assets/vendor/select2/select2.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/moment/moment.min.js')}}"></script>
  <script src="{{ asset('assets/vendor/toastr/toastr.min.js')}}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('assets/js/main.js') }}"></script>

  <script>
    function toggleSidebar() {
      document.getElementsByTagName('body').classlist.toggle('toggle-sidebar');
    }
  </script>

    <script>
    $(document).ready(function() {
      $('.select2').select2();
    });
  </script>

    <script>
        function updateDateTime() {
            var currentDate = new Date().toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            var currentTime = new Date().toLocaleTimeString(undefined, { hour12: false });
            document.getElementById('date').textContent = 'Date: ' + currentDate;
            document.getElementById('time').textContent = 'Time: ' + currentTime;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>

  @yield('script')

  <script>
    $(document).ready(function () {

      function loadMessages() {
        $.ajax({
          url: "{{ route('chats.get.manager') }}",
          method: "GET",
          success: function (response) {
            let messageHtml = '';

            if (response.length > 0) {
              response.forEach((message) => {
                const createdAt = moment(message.created_at);
                const formattedDate = createdAt.format('hh:mm:ss A D-MM-YYYY');
                messageHtml += `
                  <div class="message mb-3">
                    <p class="mb-1"><strong>${message.user?.first_name || ''} ${message.user?.last_name || ''}:</strong> ${message.message}</p>
                    <small class="text-muted">${formattedDate}</small>
                  </div>
                `;
              });
            } else {
              messageHtml = '<p class="text-muted">No messages found.</p>';
            }

            $('#messageList').html(messageHtml);
          },
          error: function () {
            $('#messageList').html('<p class="text-danger">Error fetching messages.</p>');
          }
        });
      }

      $('#chatModal').on('show.bs.modal', function () {
        loadMessages();
      });

      $('#recipient_id').select2({
        placeholder: 'Select a recipient',
        allowClear: true,
        dropdownParent: $('#chatModal')
      });

      $('.btn-send-message').on('click', function () {
        const recipientId = $('#recipient_id').val();
        const message = $('#message').val();

        if (!recipientId || !message.trim()) {
          toastr.error('Please select a recipient and enter a message.');
          return;
        }

        $.ajax({
          url: "{{ route('chats.send.manager') }}",
          method: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            recipient_id: recipientId,
            message: message
          },
          success: function (response) {
            
            toastr.success(response.message);
            $('#newMessageForm')[0].reset();
            $('#recipient_id').val(null).trigger('change');
          },
          error: function () {
            alert('Error sending message. Please try again.');
          }
        });
      });

      function loadUnreadMessages() {
        $.ajax({
          url: "{{ route('unread-messages.manager') }}",
          method: "GET",
          success: function(response) {
            const unreadCount = response.unread_count;

            if (unreadCount > 0) {
              $('#chatIcon').removeClass('bi-chat-dots').addClass('bi-chat-fill').addClass('new-message');
            } else {
              $('#chatIcon').removeClass('bi-chat-fill').removeClass('new-message').addClass('bi-chat-dots');
            }
          },
          error: function() {
            console.error("Error fetching unread message count.");
          }
        });
      }

      loadUnreadMessages();

      setInterval(loadUnreadMessages, 5000);

    });
  </script>

</body>

</html>
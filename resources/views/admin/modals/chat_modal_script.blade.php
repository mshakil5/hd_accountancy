<script>
  $(document).ready(function () {

    function loadMessages() {
      const filter = $('#date-filter').val();
      $.ajax({
        url: "{{ route('chats.get') }}",
        method: "GET",
        data: { filter: filter },
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

    $('#date-filter').change(function() {
      loadMessages();
    });

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
        url: "{{ route('chats.send') }}",
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
        url: "{{ route('unread-messages') }}",
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

    setInterval(loadUnreadMessages, 20000);

  });
</script>
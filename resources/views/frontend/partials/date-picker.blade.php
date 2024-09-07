<script>
    $(document).ready(function() {
        $('#datepicker').datepicker({
            format: 'yyyy-mm-dd',
            inline: true,
            autoclose: true,
            todayHighlight: true
        }).on('changeDate', function(e) {
            var date = e.date;
            var dateString = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
            $('#date').val(dateString);
            var formattedDate = date.toLocaleString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            $('#date-display').text(formattedDate);
        });

        $('input[name="meet"]').on('change', function() {
            var selectedMethod = $(this).val();
            $('#meeting-method').text(selectedMethod);

            var iconElement = $('#meeting-icon');
    
            if (selectedMethod === 'Phone') {
                iconElement.attr('icon', 'wpf:phone');
            } else if (selectedMethod === 'Video Conference') {
                iconElement.attr('icon', 'ic:baseline-video-call'); 
            } else if (selectedMethod === 'Face to face') {
                iconElement.attr('icon', 'mdi:face');
            }
        });

        $('input[name="timepick"]').on('change', function() {
            var startTime = $(this).data('start-time');
            var endTime = $(this).data('end-time');
            var formattedStartTime = moment(startTime, 'HH:mm').format('h:mm A');
            var formattedEndTime = moment(endTime, 'HH:mm').format('h:mm A');
            var formattedTime = formattedStartTime + ' - ' + formattedEndTime + ', UK, London Time';
            $('#time-display').text(formattedTime);

            var startMoment = moment(startTime, 'HH:mm');
            var endMoment = moment(endTime, 'HH:mm');
            var duration = moment.duration(endMoment.diff(startMoment));
            var minutes = duration.asMinutes();

            $('#time-diff-1').text(minutes + ' min');
            $('#time-diff-2').text(minutes + ' Minutes discussion on your specific topic');
        });
    });
</script>
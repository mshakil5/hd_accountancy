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
        });
    });
</script>
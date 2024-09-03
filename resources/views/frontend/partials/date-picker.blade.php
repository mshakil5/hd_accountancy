<script>
    $('#datepicker').datepicker({
        format: 'yyyy-mm-dd',
        inline: true,
        onSelect: function(dateText) {
            $('#date').val(dateText);
        }
    });
</script>
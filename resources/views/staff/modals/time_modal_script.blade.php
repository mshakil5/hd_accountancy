<!-- Active Time start -->
<script>
    function updateActiveTime() {
        var activeTimeElement = document.getElementById('activeTime');
        var currentTime = activeTimeElement.textContent;

        var timeArray = currentTime.split(':');
        var hours = parseInt(timeArray[0], 10);
        var minutes = parseInt(timeArray[1], 10);
        var seconds = parseInt(timeArray[2], 10);

        seconds++;
        if (seconds >= 60) {
            seconds = 0;
            minutes++;
        }
        if (minutes >= 60) {
            minutes = 0;
            hours++;
        }
        var formattedTime = ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2);
        activeTimeElement.textContent = formattedTime;
    }

    setInterval(updateActiveTime, 1000);
</script>
<!-- Active Time end -->

<!-- Task Check before loggin out start -->
<script>
    function checkWorkTimeStatus() {
        fetchClientSubServices();
        $.ajax({
            url: '/staff/check-work-time-status',
            type: 'GET',
            success: function(response) {
                if (response.status === 'ongoing') {
                    toastr.warning("You have an ongoing task. Please complete it before logging out!", "Warning");
                } else {
                    $('#noteModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }
</script>
<!-- Task Check before loggin out end -->

<!-- Fetch Client Sub Services start -->
<script>
    function fetchClientSubServices() {
        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: '/staff/get-completed-services-modal',
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {

                function formatDuration(seconds) {
                    var hours = Math.floor(seconds / 3600);
                    var minutes = Math.floor((seconds % 3600) / 60);
                    var remainingSeconds = seconds % 60;

                    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
                }
                var duration = moment.duration(response.login_time, 'seconds');
                var formattedLoginTime = moment.utc(duration.asMilliseconds()).format('HH:mm:ss');

                $('#loginTime').text(formattedLoginTime);
                $('#totalBreakTime').text(formatDuration(response.total_break_duration));
                $('#totalDuration').text(formatDuration(response.total_duration));


                var completedServicesHtml = '';
                $.each(response.completed_services, function(index, item) {
                    completedServicesHtml += '<tr>';
                    completedServicesHtml += '<td>' + item.client_name + '</td>';
                    completedServicesHtml += '<td>' + item.sub_service_name + '</td>';
                    completedServicesHtml += '</tr>';
                });
                $('#completedServicesTable tbody').html(completedServicesHtml);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
</script>
<!-- Fetch Client Sub Services end -->

<!-- Break Functionality Script -->
<script>
    // Break functionality
    $(document).ready(function() {
        // Check break status on page load
        checkBreakStatus();

        // Take Break Button Click
        $('#takeBreakBtn').click(function(event) {
            event.preventDefault();
            takeBreak();
        });

        // Break Out Button Click
        $('#breakOutBtn').click(function(event) {
            event.preventDefault();
            breakOut();
        });
    });

    // Take Break Function
    function takeBreak() {
        $.ajax({
            url: '/staff/take-break',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {},
            success: function(response) {
                $('#workTimeId').val(response.workTimeId);
                localStorage.setItem('workTimeId', response.workTimeId);
                localStorage.setItem('breakStartTime', new Date().toISOString());
                
                // Hide time modal and show break popup
                $('#timeModal2').modal('hide');
                $('#breakOutSection').show();
                
                // Start break timer
                startBreakTimer();
                
                toastr.success("Break started successfully!");
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                toastr.error("Error starting break. Please try again.");
            }
        });
    }

    // Break Out Function
    function breakOut() {
        var workTimeId = localStorage.getItem('workTimeId');
        if (workTimeId) {
            $.ajax({
                url: '/staff/break-out',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    workTimeId: workTimeId
                },
                success: function(response) {
                    // Clear local storage
                    localStorage.removeItem('workTimeId');
                    localStorage.removeItem('breakStartTime');
                    
                    // Hide break popup
                    $('#breakOutSection').hide();
                    
                    // Stop break timer
                    stopBreakTimer();
                    
                    toastr.success("Break ended successfully!");
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    toastr.error("Error ending break. Please try again.");
                }
            });
        }
    }

    // Check Break Status
    function checkBreakStatus() {
        var workTimeId = localStorage.getItem('workTimeId');
        if (workTimeId) {
            $.ajax({
                url: '/staff/check-break-status',
                type: 'GET',
                data: {
                    workTimeId: workTimeId
                },
                success: function(response) {
                    if (response.isBreak) {
                        // User is on break, show break popup
                        $('#breakOutSection').show();
                        startBreakTimer();
                    } else {
                        // User is not on break, hide break popup
                        $('#breakOutSection').hide();
                        stopBreakTimer();
                        localStorage.removeItem('workTimeId');
                        localStorage.removeItem('breakStartTime');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    }

    // Break Timer Functions
    var breakTimerInterval;

    function startBreakTimer() {
        var breakStartTime = localStorage.getItem('breakStartTime');
        if (!breakStartTime) {
            breakStartTime = new Date().toISOString();
            localStorage.setItem('breakStartTime', breakStartTime);
        }

        function updateBreakTimer() {
            var start = new Date(breakStartTime);
            var now = new Date();
            var diff = now - start;
            
            var hours = Math.floor(diff / 3600000);
            var minutes = Math.floor((diff % 3600000) / 60000);
            var seconds = Math.floor((diff % 60000) / 1000);
            
            var formattedTime = 
                ('0' + hours).slice(-2) + ':' + 
                ('0' + minutes).slice(-2) + ':' + 
                ('0' + seconds).slice(-2);
            
            $('#breakTimer').text(formattedTime);
        }

        // Update immediately and then every second
        updateBreakTimer();
        breakTimerInterval = setInterval(updateBreakTimer, 1000);
    }

    function stopBreakTimer() {
        if (breakTimerInterval) {
            clearInterval(breakTimerInterval);
            breakTimerInterval = null;
        }
        $('#breakTimer').text('00:00:00');
    }

    // Check break status when page loads
    $(window).on('load', function() {
        checkBreakStatus();
    });

</script>

<!-- Note and additional work start -->

@php
    $clients = \App\Models\Client::select('id', 'name')->get();
    $subServices = \App\Models\SubService::select('id', 'name')->get();
@endphp

<script>
    $(document).ready(function() {
        $('#addNoteRowBtn').click(function() {
            var newRowHtml = `
                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column">
                            <label>Client:</label>
                            <select class="form-control px-3 py-2 client-name select2" style="width: 115px;">
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Sub Service:</label>
                            <select class="form-control px-3 py-2 sub-service-name select2" style="width: 115px;">
                                @foreach($subServices as $subService)
                                    <option value="{{ $subService->id }}">{{ $subService->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Note:</label>
                            <textarea class="form-control px-3 py-2 note" rows="1" style="width: 115px;" id="subServiceNote" placeholder="Note"></textarea>
                        </div>
                        <div class="d-flex flex-column">
                            <label>Start Time:</label>
                            <input type="time" class="form-control px-3 py-2 start-time" style="width: 130px;">
                        </div>
                        <div class="d-flex flex-column">
                            <label>End Time:</label>
                            <input type="time" class="form-control px-3 py-2 end-time" style="width: 130px;">
                        </div>
                        <div class="d-flex flex-column">
                          <label class="label label-primary" style="visibility:hidden;">Action</label>
                          <button type="button" class="btn btn-danger btn-remove-note-row">-</button>
                        </div>
                    </div>
                </div>`;

            $('#additionalWorkRows').append(newRowHtml);
        });

        $(document).on('click', '.btn-remove-note-row', function() {
            $(this).closest('.mt-3').remove();
        });

        $('#saveNoteBtn').click(function(e) {
            e.preventDefault();

            var isValid = true;

            $('#additionalWorkRows').each(function() {
                var clientSelected = $(this).find('.client-name').val();
                var startTime = $(this).find('.start-time').val();
                var endTime = $(this).find('.end-time').val();

                if (clientSelected) {
                    if (!startTime) {
                        isValid = false;
                        $(this).find('.start-time').addClass('is-invalid');
                    } else {
                        $(this).find('.start-time').removeClass('is-invalid');
                    }

                    if (!endTime) {
                        isValid = false;
                        $(this).find('.end-time').addClass('is-invalid');
                    } else {
                        $(this).find('.end-time').removeClass('is-invalid');
                    }
                } else {
                    $(this).find('.start-time').removeClass('is-invalid');
                    $(this).find('.end-time').removeClass('is-invalid');
                }
            });

            if (!isValid) {
                alert('Please fill all required fields!');
                return;
            }

            var formData = {
                _token: '{{ csrf_token() }}',
                client_ids: [],
                sub_service_ids: [],
                notes: [],
                noteInput: '',
                start_times: [],
                end_times: []
            };

            $('#additionalWorkRows .client-name').each(function() {
                formData.client_ids.push($(this).val());
            });

            $('#additionalWorkRows .sub-service-name').each(function() {
                formData.sub_service_ids.push($(this).val());
            });

            $('#additionalWorkRows .note').each(function() {
                formData.notes.push($(this).val());
            });

            $('#additionalWorkRows .start-time').each(function() {
                formData.start_times.push($(this).val());
            });

            $('#additionalWorkRows .end-time').each(function() {
                formData.end_times.push($(this).val());
            });

            var noteValue = $('#noteInput').val();
            formData.noteInput = noteValue;

            $.ajax({
                url: '/staff/save-notes',
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success("Record saved and you will be logged out now!", "Success");

                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
<!-- Note and additional work end -->
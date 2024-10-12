<div class="row mt-5">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="row">
                <div class="col-lg-4 ">
                    <div class="p-4">
                        <div class="previous-btn mb-3 d-none" onclick="gotoPrev();" id="prev" class="d-none">
                            <iconify-icon icon="octicon:chevron-left-12"></iconify-icon>
                        </div>
                        <img src="{{ asset('assets/frontend/images/Ellipse 18.png') }}" class="img-curcle mb-3" alt="">
                        <div class="">
                            <h5 class="poppins-bold txt-primary">Shultan Mahmud, PhD</h5>
                            <p class="fw-normal txt-primary poppins-medium">Director, HD Accountancy LTD</p>
                        </div>
                        <div>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="uil:clock" class="fs-4"></iconify-icon> <span id="time-diff-1" class="ms-2">30 min</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex align-items-center">
                                <iconify-icon id="meeting-icon" icon="wpf:phone" class="fs-4"></iconify-icon>
                                <span id="meeting-method" class="ms-2">Phone</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="material-symbols-light:event-available-rounded" class="fs-4"></iconify-icon> <span id="date-display" class="ms-2">{{ date('l, j F Y') }}</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="fe:globe" class="fs-4"></iconify-icon> <span id="time-display" class="ms-2">10:00 AM - 10:30 AM, UK, London Time</span>
                            </p>

                            <small class="txt-primary mt-4 text-center poppins-medium d-flex  align-items-center"> <span id="time-diff-2" class="ms-2">
                                30 Minutes discussion on your specific topic
                            </span>
                            </small>
                        </div>
                    </div>
                </div>

                @php
                    use Carbon\Carbon;
                @endphp

                <div class="col-lg-8">
                    <div class="border-start border-1 h-100 p-4">
                        <div id="stepOne" class="">
                            <div class="row">
                                <div class="col-lg-6">
                                    <p class="txt-primary poppins-medium d-flex align-items-center">
                                        <span>Select A Date & Time</span>
                                    </p>
                                </div>
                                <div class="col-lg-6 text-end">
                                    <a class="navbar-brand" href="{{ route('frontend.index') }}">
                                        <img src="{{ asset('assets/frontend/images/logo.png') }}" width="90px" alt="df">
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-7">
                                    <div id="datepicker" class="datepicker"></div>
                                    <input type="hidden" id="date" name="date">
                                </div>
                                <div class="col-lg-5 d-flex flex-wrap gx-1 justify-content-center my-4">
                                    @foreach(\App\Models\TimeSlot::orderBy('start_time', 'asc')->get() as $timeSlot)
                                        <div class="mb-2">
                                            <input type="radio" class="timepick invisible" name="timepick" id="timepick-{{ $timeSlot->id }}" value="{{ $timeSlot->start_time }}" data-start-time="{{ $timeSlot->start_time }}" data-end-time="{{ $timeSlot->end_time }}">
                                            <label for="timepick-{{ $timeSlot->id }}">
                                                {{ Carbon::createFromFormat('H:i', $timeSlot->start_time)->format('h:i A') }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <h6 class="txt-primary poppins-bold">Time Zone</h6>
                                    <div class="d-flex align-items-center">
                                        <iconify-icon icon="fe:globe" class="txt-primary fw-bold"></iconify-icon>
                                        <select name="time_zone" id="time_zone" class="txt-primary poppins-medium border-0">
                                            <option value="UK, London Time">UK, London Time</option>
                                        </select>
                                    </div>
                                    <a href="{{ route('frontend.contact') }}" class="btn btn-theme-outline my-3 d-inline-block mx-auto rounded-5 fs-6">Not Working?</a>
                                </div>
                                <div class="col-lg-8 px-4">
                                    <h6 class="txt-primary poppins-bold">How would you like to meet?</h6>
                                    <div class="custom-select-container">
                                        <label class="custom-radio">
                                        <input type="radio" name="meet" value="Video Conference" class="largerRadiobox">
                                        <span>Video Conference</span>
                                        </label>
                                        <label class="custom-radio">
                                        <input type="radio" name="meet" value="Phone" class="largerRadiobox">
                                        <span>Phone</span>
                                        </label>
                                        <label class="custom-radio">
                                        <input type="radio" name="meet" value="Face to face" class="largerRadiobox">
                                        <span>Face to face</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="stepTwo" class="d-none">
                            <p class="txt-primary poppins-medium">Enter Your Details</p>
                            <div class="card p-4 pt-5 mt-4">
                                <div class="row">
                                    <div class="col-lg-6 mb-4">
                                        <input type="text" star-data="*" placeholder="First Name *" name="first_name" id="first_name" class="form-control" value="{{ Auth::check() ? Auth::user()->first_name : '' }}">
                                    </div>
                                    <div class="col-lg-6 mb-4">
                                        <input type="text" placeholder="Last Name *" name="last_name" id="last_name" class="form-control" value="{{ Auth::check() ? Auth::user()->last_name : '' }}">
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <input type="email" placeholder="Email *" name="email" id="email" class="form-control" value="{{ Auth::check() ? Auth::user()->email : '' }}">
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <input type="number" placeholder="Telephone No *" name="phone" id="phone" class="form-control" value="{{ Auth::check() ? Auth::user()->phone : '' }}">
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <label for="" class="txt-primary mb-3 poppins-medium">
                                            Please let me know what do you want to discuss <span class="text-danger"> *</span>
                                        </label>
                                        <textarea name="discussion" id="discussion" class="form-control" style="height: 160px;" placeholder="Describe your meeting agenda"></textarea>
                                    </div>

                                    <div id="loader1" class="text-center" style="display: none;">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 text-center">
                                        <button type="button" id="submitBtn" class="btn btn-theme-outline text-light py-2 px-3">Schedule your meeting</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 text-end">
                                <a onclick="gotoNext();" id="nxtBtn" class="btn mt-2 btn-theme-outline d-inline w-auto mx-auto rounded-3 fs-6 float-end">Next</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    input.largerRadiobox {
        width: 24px;
        height: 24px;
        background-color: white;
        border-radius: 5%; 
        vertical-align: middle;
        border: 2px solid #9c9999;
        appearance: none;
        -webkit-appearance: none;
        outline: none;
        cursor: pointer;
        position: relative;
        transition: all 0.1s ease;
    }

    input.largerRadiobox:checked {
        background-color: #193d5b;
        border-color: #193d5b;
    }

    input.largerRadiobox:checked::before {
        content: '\2713';
        color: white;
        font-size: 18px;
        position: absolute;
        top: -2px;
        left: 4px;
    }
</style>

<script>
    document.getElementById('submitBtn').addEventListener('click', function() {
        let date = $('#date').val();
        let time = document.querySelector('input[name="timepick"]:checked');
        let time_zone = document.getElementById('time_zone').value;
        let meet_type = document.querySelector('input[name="meet"]:checked');
        let first_name = document.querySelector('input[name="first_name"]').value;
        let last_name = document.querySelector('input[name="last_name"]').value;
        let email = document.getElementById('email').value;
        let phone = document.getElementById('phone').value;
        let discussion = document.getElementById('discussion').value;

        let phoneRegex = /^44[0-9\s-]{9,10}$/;
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        let today = new Date().toISOString().split('T')[0];

        if (!date || !time || !time_zone || !meet_type || !first_name || !last_name || !email || !phone || !discussion) {
            swal({
                icon: 'warning',
                title: 'Error',
                text: 'Please fill out all required fields.',
                button: 'OK'
            });
            return;
        }

        if (date < today) {
            swal({
                icon: 'warning',
                title: 'Invalid Date',
                text: 'The selected date must be today or a future date.',
                button: 'OK'
            });
            return;
        }

        if (!emailRegex.test(email)) {
            swal({
                icon: 'warning',
                title: 'Invalid Email',
                text: 'Please enter a valid email address.',
                button: 'OK'
            });
            return;
        }

        if (!phoneRegex.test(phone)) {
            swal({
                icon: 'warning',
                title: 'Invalid Phone Number',
                text: 'The phone number must be in UK format and start with 44, followed by 9 or 10 digits.',
                button: 'OK'
            });
            return;
        }

        let formData = {
            date: date,
            time: time.value,
            time_zone: time_zone,
            meet_type: meet_type.value,
            first_name: first_name,
            last_name: last_name,
            email: email,
            phone: phone,
            discussion: discussion,
        };

        var csrfToken = '{{ csrf_token() }}';

        $.ajax({
            url: "{{ route('schedule.meeting.store') }}",
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            beforeSend: function() {
                document.getElementById('loader1').style.display = 'block';
                document.getElementById('submitBtn').disabled = true;
            },
            success: function(response) {
                document.getElementById('loader1').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Meeting scheduled successfully!',
                    button: 'OK'
                });

                $('#date').val('');
                $('#datepicker').datepicker('update', '');
                $('input[name="timepick"]').prop('checked', false);
                $('#time_zone').val('');
                $('input[name="meet"]').prop('checked', false);
                $('#first_name').val('');
                $('#last_name').val('');
                $('#email').val('');
                $('#phone').val('');
                $('#discussion').val('');
            },
            error: function(xhr, status, error) {
                document.getElementById('loader1').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;

                // console.log(xhr.responseText);
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    button: 'OK'
                });
            }
        });
    });
</script>
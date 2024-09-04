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
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="uil:clock" class="fs-4"></iconify-icon> <span class="ms-2">30 min</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="wpf:phone" class="fs-4"></iconify-icon> <span class="ms-2"> phone</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="material-symbols-light:event-available-rounded" class="fs-4"></iconify-icon> <span class="ms-2">{{ date('l, j F Y') }}</span>
                            </p>
                            <p class="text-muted poppins-medium d-flex  align-items-center"><iconify-icon icon="fe:globe" class="fs-4"></iconify-icon> <span class="ms-2"> 3:00 PM - 3:30 PM, UK, London Time</span>
                            </p>

                            <small class="txt-primary mt-4 text-center poppins-medium d-flex  align-items-center"> <span class="ms-2"> 30 Minutes discussion on your specific topic</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="border-start border-1 h-100 p-4">
                        <div id="stepOne" class="">
                            <div class="row">
                                <div class="col-lg-6">

                                    <p class="txt-primary poppins-medium d-flex  align-items-center"> <span class="">
                                            Select A Date & Time </span>
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

                                @php
                                    use Carbon\Carbon;
                                @endphp

                                    @foreach(\App\Models\TimeSlot::all() as $timeSlot)
                                        <div class="mb-2">
                                            <input type="radio" class="timepick invisible" name="timepick" id="timepick-{{ $timeSlot->id }}">
                                            <label for="timepick-{{ $timeSlot->id }}">
                                                {{ Carbon::createFromFormat('H:i', $timeSlot->start_time)->format('h:i A') }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <h6 class="txt-primary poppins-bold">
                                        Time Zone
                                    </h6>
                                    <div class="d-flex align-items-center">
                                        <iconify-icon icon="fe:globe" class="txt-primary fw-bold"></iconify-icon>
                                        <select name="" id="" class="txt-primary poppins-medium border-0">
                                            <option value="">UK, London Time</option>
                                        </select>
                                    </div>
                                    <a href="{{ route('frontend.contact') }}" class="btn btn-theme-outline my-3 d-inline-block  mx-auto rounded-5 fs-6">Not Working? </a>
                                </div>
                                <div class="col-lg-8 px-4">
                                    <h6 class="txt-primary poppins-bold">
                                        How would you like to meet?
                                    </h6>
                                    <div class="custom-select-container">
                                        <label class="custom-radio">
                                            <input type="radio" name="meet" value="video">
                                            <span>Video Conference</span>
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="meet" value="phone">
                                            <span>Phone</span>
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="meet" value="face">
                                            <span>Face to face</span>
                                        </label>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div id="stepTwo" class="d-none">
                            <p class="txt-primary poppins-medium ">Enter Your Details</p>
                            <div class="card p-4 pt-5 mt-4">
                                <div class="row">
                                    <div class="col-lg-6 mb-4">
                                        <input type="text" star-data="*" placeholder="First Name" class="form-control">
                                    </div>
                                    <div class="col-lg-6 mb-4">
                                        <input type="text" placeholder="Last Name" class="form-control">
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <input type="email" placeholder="Email" class="form-control">
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <input type="number" placeholder="Telephone No" class="form-control">
                                    </div>

                                    <div class="col-lg-12 mb-4">
                                        <label for="" class="txt-primary mb-3  poppins-medium">
                                            Please let me know what do you want to discuss <span class="text-danger "> *</span>
                                        </label>
                                        <textarea name="" id="" class="form-control " style="height: 160px;" placeholder="Describe your meeting agenda"></textarea>
                                    </div>
                                    <div class="col-lg-12 text-center">
                                        <button type="submit" class="btn btn-theme-outline  text-light py-2 px-3">Schedule your meeting</button>
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
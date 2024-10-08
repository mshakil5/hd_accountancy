<footer class="bg-primary pt-5 pb-3">
    <div class="container pt-3">
        <div class="row text-center text-md-start">

        @php
            $companyDetails = \App\Models\CompanyDetails::first();
        @endphp

            <div class="col-lg-4 mb-3">
                <div class="info-box rounded-3 text-white p-3 mb-3">
                    <h5 class="mb-3">Subscribe to stay tuned for latest updates. Let’s do it!</h5>
                    <div class="d-flex gap-1">
                        <input type="text" placeholder="Enter your email address" class="form-control rounded-3 fs-6"> <button class="btn-theme border-0 rounded-3">submit</button>
                    </div>

                    <h6 class="fw-bold my-3">
                        Follow us on
                    </h6>
                    <div class="d-flex gap-2 follow-us align-items-center justify-content-center justify-content-md-start">
                        @if($companyDetails->facebook)
                            <a href="{{ $companyDetails->facebook }}" target="_blank">
                                <iconify-icon class="fs-3 text-white" icon="ic:baseline-facebook"></iconify-icon>
                            </a>
                        @endif
                        @if($companyDetails->instagram)
                            <a href="{{ $companyDetails->instagram }}" target="_blank">
                                <iconify-icon class="fs-3 text-white" icon="mdi:instagram"></iconify-icon>
                            </a>
                        @endif
                        @if($companyDetails->youtube)
                            <a href="{{ $companyDetails->youtube }}" target="_blank">
                                <iconify-icon class="fs-3 text-white" icon="mdi:youtube"></iconify-icon>
                            </a>
                        @endif
                        @if($companyDetails->linkedin)
                            <a href="{{ $companyDetails->linkedin }}" target="_blank">
                                <iconify-icon class="fs-3 text-white" icon="mdi:linkedin"></iconify-icon>
                            </a>
                        @endif
                    </div>

                </div>
                <div class="d-flex align-items-center justify-content-center flex-wrap">
                    @foreach(\App\Models\WeWorkWithImage::all() as $image)
                        <img src="{{ asset('images/we_work_with_images/' . $image->image) }}" alt="">
                    @endforeach
                </div>
            </div>
            <div class="col-lg-3 mb-3">
                <h4 class="fw-bold text-white fs-5 mb-4">Huddersfield Office</h4>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="ri:map-pin-fill"></iconify-icon>
                            {{ $companyDetails->address1 ?? 'Address not available' }}
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="solar:phone-bold"></iconify-icon>
                            {{ $companyDetails->phone1 ?? 'Phone not available' }}
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="mingcute:mail-fill"></iconify-icon>
                            {{ $companyDetails->email1 ?? 'Email not available' }}
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-3 mb-3">
                <h4 class="fw-bold text-white fs-5 mb-4">Manchester Office</h4>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="ri:map-pin-fill"></iconify-icon>
                            {{ $companyDetails->address2 ?? 'Address not available' }}
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="solar:phone-bold"></iconify-icon>
                            {{ $companyDetails->phone2 ?? 'Phone not available' }}
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 text-white">
                            <iconify-icon class="fs-4" icon="mingcute:mail-fill"></iconify-icon>
                            {{ $companyDetails->email2 ?? 'Email not available' }}
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-2 mb-3">
                <h4 class="fw-bold text-white fs-5 mb-4">Company</h4>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.services') }}" class="text-white link">Our Services</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.index') }}#about-us" class="text-white link">about us</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.contact') }}" class="text-white link">contact us</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.ourTeam') }}" class="text-white link">our team</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.career') }}" class="text-white link">career</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.faq') }}" class="text-white link">FAQ</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.privacyPolicy') }}" class="text-white link">Privacy policy</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('frontend.termsConditions') }}" class="text-white link">Terms & Conditions</a>
                    </li>


                </ul>
            </div>
        </div>

        <div class="mt-5 py-2 text-light border-top border-bottom text-center">
            <small>2024 | HD Accountancy Services LTD | Registered in England and Wales | Company No-05600401 | VAT No-437899234</small>
        </div>

    </div>

</footer>
<section class="pricing py-5">
    <div class="container ">
        <div class="row mt-3">
            <div class="text-center text-white text-capitalize fw-bold" style="font-size: 50px; font-weight: bold;">
    Choose your suitable pricing package
            </div>
            <div class="text-center text-white text-capitalize" style="font-size: 30px; font-weight: 400;">
            Affordable for everyone
            </div>
        </div>
        <div class="row my-5 d-flex flex-wrap">
            @foreach($packages as $package)
                <div class="col-lg-4 d-flex">
                    <div class="card text-center border-1 p-4 mb-3 h-100 d-flex flex-column package-card">
                        <h4 class="txt-primary poppins-bold">{{ $package->short_title }}</h4>
                        <small class="txt-primary my-3">
                            {!! $package->long_description !!}
                        </small>
                        <div class="mb-3">
                            <span class="txt-primary poppins-regular mb-0">From</span>
                            <h3 class="txt-primary poppins-bold mb-0">£{{ number_format($package->price, 0) }}</h3>
                            <small class="txt-primary my-3">+ VAT / Month</small>
                        </div>
                        <ul class="ms-5 text-start small txt-primary list-unstyled mb-4">
                            @php
                                $featureCount = 0;
                            @endphp

                            @foreach ($package->turnOvers as $turnOver)
                                @php
                                    $featureIds = explode(',', trim($turnOver->features, '"'));
                                    $features = \App\Models\PackageFeature::whereIn('id', $featureIds)
                                        ->orderBy('id', 'asc')->get();
                                @endphp

                                @foreach ($features as $feature)
                                    @if ($featureCount < 4)
                                        <li class="d-flex align-items-center gap-2">
                                            <iconify-icon class="txt-primary fw-bold fs-5" icon="fluent:checkmark-12-filled"></iconify-icon>
                                            {{ $feature->name }}
                                        </li>
                                        @php
                                            $featureCount++;
                                        @endphp
                                    @else
                                        @break
                                    @endif
                                @endforeach

                                @if ($featureCount >= 4)
                                    @break
                                @endif
                            @endforeach
                        </ul>

                        <a href="{{ route('frontend.pricing') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6 mt-auto">Explore</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row py-5" id="about-us">
            <div class="col-lg-6  ">
                <div class="row">
                    <h2 class="txt-primary poppins-bold mb-3">{{ $homeOurValues->short_title }} </h2>
                    <p class="txt-primary my-3 mt-2"style="text-align: justify;">
                        {!! $homeOurValues->long_title !!}
                    </p>
                </div>
                <div class="row g-3 gx-4 mt-5">
                    <div class="col-lg-6">
                        <div class="border p-3 gap-3 text-center hover-card"> 
                            <div class="poppins-medium txt-primary fw-bold fs-4">Embrace <br> Technology</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border p-3 gap-3 text-center hover-card">
                            <div class="poppins-medium txt-primary fw-bold fs-4">Together <br> Stronger</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border p-3 gap-3 text-center hover-card">
                            <div class="poppins-medium txt-primary fw-bold fs-4">Brave to face <br> the truth</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border p-3 gap-3 text-center hover-card">
                            <div class="poppins-medium txt-primary fw-bold fs-4">Humble & <br> Helpful</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/meta_image/' . $homeOurValues->meta_image) }}" class="img-fluid mt-3" alt="">
            </div>
        </div>
        <div class="row py-3">
            <div class="col-lg-6 d-flex ">
                <div>
                    {!! $homeOurValues->long_description !!} 
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="border p-3 d-flex align-items-center justify-content-center gap-3 hover-card">
                            <div class="poppins-bold display-3 txt-primary mb-0">1200</div>
                            <div class="poppins-medium txt-primary fw-semi-bold">Existing <br> Clients</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border p-3 d-flex align-items-center justify-content-center gap-3 hover-card">
                            <div class="poppins-bold display-3 txt-primary mb-0">7</div>
                            <div class="poppins-medium txt-primary fw-bold">Years in <br> Operation</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border p-3 d-flex align-items-center justify-content-center gap-3 hover-card">
                            <div class="poppins-bold display-3 txt-primary mb-0">13</div>
                            <div class="poppins-medium txt-primary fw-bold">Full time <br> Employee</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border p-3 d-flex align-items-center justify-content-center gap-3 hover-card">
                            <div class="poppins-bold display-3 txt-primary mb-0">2</div>
                            <div class="poppins-medium txt-primary fw-bold">Office <br> Nationwide</div>
                        </div>
                    </div>
                    <div class="col-12 text-center mt-4">
                        <a href="{{route('frontend.ourTeam')}}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">Meet our team</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .package-card {
        cursor: pointer;
    }

    .package-card:hover {
        background-color: #c7e968;
    }

    .hover-card {
        cursor: pointer;
        border-radius: 15px;
        border: 1px solid #8095C580;
        transition: background-color 0.2s ease;
    }

    .hover-card:hover {
        box-shadow: 0 0 0 1px #8095C580;
        background-color: #c7e968;
    }

    .fw-semi-bold {
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .mobile-col {
            width: 50%;
            flex: 0 0 50%;
        }
        .col-lg-4 {
            padding: 10px;
        }
        .package-card {
            margin: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .row {
            flex-direction: row;
        }
    }

    @media (max-width: 768px) {
        .package-card {
            margin: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .row {
            flex-direction: column;
        }
        .col-lg-4 {
            width: 100%;
        }
    }

    @media (hover: hover) and (pointer: fine) {
        .package-card:hover {
            background-color: #c7e968;
        }
    }
</style>
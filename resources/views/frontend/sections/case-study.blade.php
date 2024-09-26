<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary text-capitalize">Case Study</h2>
                <h5 class="poppins-medium txt-primary txt-primary text-capitalize">The inspiration we create</h5>
            </div>
        </div>
        <div class="row mt-5">

            <div class="caseStudy">

                @foreach ($caseStudies as $item)
                <div class="p-3">
                    <div class="row gx-3">
                        <div class="col-lg-6">
                            <img src="{{ asset($item->image) }}" class="img-fluid rounded-4 shadow" alt="" style="width: 100%; height: 250px; object-fit: cover;">
                        </div>
                        <div class="col-lg-6 d-flex align-items-stretch">
                            <div class="theme-b bg-primary rounded-4 p-3 d-flex flex-column justify-content-between" style="height: 250px;">
                                <div>
                                    <h3 class="poppins-bold text-light">{{ $item->short_title }}</h3>
                                    <small class="text-light">
                                        {{ $item->long_title }}
                                    </small>
                                </div>
                                <p class="text-end mb-0 mt-auto">
                                    <a href="{{ route('frontend.caseStudy') }}" class="poppins-medium btn-theme rounded-3" style="font-weight: bold;">See More</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>

            <div class="col-12 text-center mt-5">
                <a href="{{ route('frontend.caseStudy') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">See More Case Study</a>
            </div>

        </div>
    </div>
</section>
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
                            <img src="{{ asset($item->image) }}" class="img-fluid rounded-3 shadow" alt="">
                        </div>
                        <div class="col-lg-6 p-3">
                            <div class="theme-b bg-primary rounded-4 p-3">
                                <h3 class="poppins-bold text-light">{{ $item->short_title }}</h3>
                                <small class="text-light">
                                    {{ $item->long_title }}
                                </small>
                                <p class="text-end mb-0">
                                    <a href="{{ route('frontend.caseStudy') }}" class=" poppins-medium btn-theme rounded-3">See More</a>
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
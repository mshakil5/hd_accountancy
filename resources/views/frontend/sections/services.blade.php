<section>
    <div class="  mb-5  position-relative">
        <div class="section-title text-center">
            <div class="txt-primary position-relative h2 lh-1 text-center poppins-bold  bg-white mx-auto d-inline">“WE DON'T JUST BALANCE BOOKS

                <div class="txt-secondary position-relative text-center poppins-bold   mx-auto d-block"> WE BALANCE YOUR SUCCESS"</div>
            </div>
        </div>
    </div>
</section>

<section class="services position-relative bg-primary">
    <div class="container-fluid bg-primary py-5">
        <div class="container">
            <div class="row">
                <h2 class="text-white text-center fw-bold text-capitalize ">What are you looking for?</h2>
            </div>
            <div class="row my-5">
                @foreach($businessServices as $businessService)
                    <div class="col-lg-4 text-center text-white">
                        <div class="bg-white rounded-3 d-inline  w-auto p-3 mx-auto d-inline-flex align-items-center justify-content-center">
                            <img src="{{ asset('/' . $businessService->image) }}" width="150">
                        </div>
                        <h5 class="my-4 fw-bold text-white">{{ $businessService->short_title }}</h5>
                        <p class="mb-5">
                            {!! $businessService->short_description !!}
                        </p>
                        <a href="{{ route('frontend.businessServices', ['slug' => $businessService->slug]) }}" class="bg-light border-0 py-2 px-5 link rounded-2 fw-bold txt-primary">See More</a>
                    </div>
                @endforeach
            </div>
        </div>
        <br>

    </div>
    <div class="row section-title-single px-0 bg-primary ">
        <h2 style="z-index: 1;" class="text-white text-center poppins-bold text-capitalize w-auto mx-auto bg-primary ">The Value you Get</h2>
    </div>
    <div class="container mt-5">
        <div class="row py-5">
            @foreach($businessValues as $businessValue)
                <div class="col-lg-3 text-center text-white mb-3">
                    <div class="mt-3 bg-white d-inline-block border w-auto  border-1 py-4 px-3  rounded-3 fw-bold position-relative">
                        <div class="rounded-circle bg-white rounded-3 d-inline  w-auto p-3 border border-1 border-primary mx-auto d-inline-flex align-items-center justify-content-center position-absolute top-0 start-50 translate-middle">
                            <img src="{{ asset('/' . $businessValue->image) }}" width="70">
                        </div>
                        <br>
                        <br>
                        <h5 class="d-block txt-primary "><b>{{ $businessValue->short_title }} </b></h5>
                        <small class="txt-primary my-3">
                            {!! $businessValue->long_description !!}
                        </small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
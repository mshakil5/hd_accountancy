<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5" id="accounting-solution">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $accountingSolution->short_title }}</h1>
                        <h4 class="text-light poppins-bold">{{ $accountingSolution->long_title }} <span class="txt-secondary">{{ $accountingSolution->short_description }}</span></h4>
                    </div>

                     <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $accountingSolution->long_description !!}
                    </p>
                    <div class="text-center mt-5">
                        <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class=" poppins-medium btn-theme rounded-3 fs-5">Book your Appointment</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3">
            <div class="col-lg-12 text-center">
                <h1 class="txt-primary text-center text-capitalize poppins-medium mt-2" style="font-weight: 600;">
                    The Solutions We offer
                </h1>

            </div>
        </div>
        <div class="row my-4 gy-4 d-flex flex-wrap justify-content-center">

            @foreach($accountingSolutions as $solution)
                <div class="col-lg-4 col-md-6 d-flex">
                    <div class="card flex-fill text-center p-4">
                        <img src="{{ asset('/' . $solution->image) }}" width="100" class="mb-3 mx-auto" alt="{{ $solution->short_title }}">

                        <h6 class="txt-primary poppins-bold">{{ $solution->short_title }}</h6>

                        <p class="txt-primary justify">
                            {!! $solution->long_description !!}
                        </p>

                        <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                    </div>
                </div>
            @endforeach

            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Thinking about setting up new Business?</h2>
                <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class="poppins-medium btn-theme rounded-3 fs-5">Book your Appointment</a>
            </div>
        </div>
    </div>
</section>

<section class="py-4 bg-primary">
    <div class="container">
        <div class="row py-5" id="tax-solution">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $taxSolution->short_title }}</h1>
                        <h4 class="text-light poppins-bold justify"><span class="txt-secondary justify">{{ $taxSolution->long_title }}</span> {{ $taxSolution->short_description }}</h4>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $taxSolution->long_description !!}
                    </p>

                    <div class="text-center mt-5">
                        <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class=" poppins-medium btn-theme rounded-3 fs-5">Save Tax Now</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>


<section class="py-5 bg-light">
    <div class="container">
        <div class="row my-4 g-3">
            @foreach($taxSolutions as $solution)
                <div class="col-lg-4 text-center">
                    <div class="card p-4 text-center">
                        <h4 class="txt-primary poppins-bold">{{ $solution->short_title }}</h4>
                        <small class="txt-primary">
                            {!! $solution->long_description !!}
                        </small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>


<section class="py-4 bg-primary">
    <div class="container">
        <div class="row py-5" id="other-solution">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">{{ $otherSolution->short_title }}</h1>
                        <h4 class="text-light poppins-bold"><span class="txt-secondary">{{ $otherSolution->long_title }}</span> {{ $otherSolution->short_description }}</h4>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto justify">
                        {!! $otherSolution->long_description !!}
                    </p>

                    <div class="text-center mt-5">
                        <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class=" poppins-medium btn-theme rounded-3 fs-5">Take To us Now</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>


<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 px-4">
                <h4 class="txt-primary border border-1 p-3 poppins-bold text-center">{{ $businessStartUp->short_title }}</h4>
            </div>
        </div>
        <div class="row py-2 col-lg-12 mx-auto py-3 mt-4">
            <div class="col-lg-6">
                {!! $businessStartUp->long_description !!}
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/meta_image/' . $businessStartUp->meta_image) }}" class="img-fluid w-100" alt="">
            </div>

        </div>
        <div class="row">
            <div class="col-lg-12 px-4">
                <h4 class="txt-primary border border-1 p-3 poppins-bold text-center">{{ $companySecretarial->short_title }}</h4>
            </div>
        </div>
        <div class="row py-2 col-lg-12 mx-auto py-3 mt-4">
            <div class="col-lg-6">
                {!! $companySecretarial->long_description !!}
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/meta_image/' . $companySecretarial->meta_image) }}" class="img-fluid w-100" alt="">
            </div>

        </div>
        <div class="row mt-4">
            <div class="col-lg-12 px-4">
                <h4 class="txt-primary border border-1 p-3 poppins-bold text-center text-capitalize">{{ $bankruptcyLiquidation->short_title }}</h4>
            </div>
        </div>
        <div class="row py-2 col-lg-12 mx-auto py-3 mt-4">
            <div class="col-lg-6">
                <p class="txt-primary">
                    {!! $bankruptcyLiquidation->long_description !!}
                </p>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/meta_image/' . $bankruptcyLiquidation->meta_image) }}" class="img-fluid w-100" alt="">
            </div>

        </div>
    </div>
</section>
<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Year End Account</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                    Accounts preparation is one of the most frequently used services we provide. HD Accountancy Services Ltd., guarantee you to produce high quality business accounts complying with all relevant regulations and up to date disclosure requirements. We ensure that we do everything we can to meet all your deadlines. We can prepare accounts from whatever accounting system you use, whether that is accounting software, excel spreadsheets or manual records.
                    </p>

                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3">
            <h1 class="txt-primary text-center text-capitalize poppins-bold mb-5">Our team can assist you in</h1>
            <div class="col-lg-6">
                <p class="txt-primary">
                Our team will guide you all compliance deadlines to avoid any late filing, so that you can focus on running your business. Our team will also guide you how to improve your accounting systems implementing quick and cost saving measure.
                </p>
                <ul class="list-theme ps-4 mt-4">
                    <li>

                        <p class="txt-primary">
                        Prepare profit and loss and Balance sheet for your business
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Arrange meeting to discuss the accounts
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Submitting the accounts to the relevant regulators.
                        </p>
                    </li> 
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/frontend/images/Rectangle 29 (4).png') }}" class="img-fluid" alt="">
            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-3">See what our clients says </h2>
            </div>
            <div class="row ">

            <div class="caseStudy">

            @php
                $caseStudies = \App\Models\CaseStudy::orderBy('id', 'desc')->get();
            @endphp

            @foreach ($caseStudies as $item)
            <div class="p-3">
                <div class="row gx-3">
                    <div class="col-lg-6">
                        <img src="{{ asset($item->image) }}" class="img-fluid rounded-3 shadow" alt="">
                    </div>
                    <div class="col-lg-6">
                        <div class="theme-b bg-primary rounded-4 p-3">
                            <h3 class="poppins-bold text-light">{{ $item->short_title }}</h3>
                            <small class="text-light">
                                {{ $item->long_title }}
                            </small>
                            <p class="text-end mb-0">
                                <a href="{{ route('frontend.caseStudy') }}" class="btn bg-white mt-2 poppins-bold txt-primary"style="border: none;">See More</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            </div>


            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Take Control of Your Finances</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;">Book your Appointment</a>

            </div>
        </div>
    </div>
</section>

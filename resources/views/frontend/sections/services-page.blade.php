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
                        <a href="" class=" poppins-medium btn-theme rounded-3 fs-5">Book your Appointment</a>
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
                <h1 class="txt-primary text-center text-capitalize poppins-medium mt-2">
                    The Solutions We offer
                </h1>

            </div>
        </div>
        <div class="row my-4 gy-4 d-flex flex-wrap justify-content-center">

            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <img src="{{ asset('assets/frontend/images/icon4.png') }}" width="100" class="mb-3 mx-auto" alt="">
                    <h6 class="txt-primary poppins-bold">Digital Bookkeeping Service</h6>
                    <p class="txt-primary">
                        Using our paperless system you can track your daily transaction of your business and performance
                    </p>
                    <a href="" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                </div>
            </div>

            <div class="col-lg-4 text-center icon-box">
                <div class="card p-4 text-center">
                    <img src="{{ asset('assets/frontend/images/icon5.png') }}" width="100" class="mb-3 mx-auto" alt="">
                    <h6 class="txt-primary poppins-bold">Cloud Accounting</h6>
                    <p class="txt-primary">
                        All of your data will be securely stored in the cloud accounting system. Access your data anytime
                    </p>
                    <a href="" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                </div>
            </div>
            <div class="col-lg-4 text-center icon-box">
                <div class="card p-4 text-center">
                    <img src="{{ asset('assets/frontend/images/icon6.png') }}" width="100" class="mb-3 mx-auto" alt="">
                    <h6 class="txt-primary poppins-bold">Payroll Service</h6>
                    <p class="txt-primary">
                        We can ensure your payroll is processed timely and accurately by following UpToDate regulation.
                    </p>
                    <a href="" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                </div>
            </div>
            <div class="col-lg-4 text-center icon-box">
                <div class="card p-4 text-center">
                    <img src="{{ asset('assets/frontend/images/icon7.png') }}" width="100" class="mb-3 mx-auto" alt="">
                    <h6 class="txt-primary poppins-bold">Monthly Account Management</h6>
                    <p class="txt-primary">
                        We will handle your numbers in monthly basis according to your requirements
                    </p>
                    <a href="" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                </div>
            </div>
            <div class="col-lg-4 text-center icon-box">
                <div class="card p-4 text-center">
                    <img src="{{ asset('assets/frontend/images/icon8.png') }}" width="100" class="mb-3 mx-auto" alt="">
                    <h6 class="txt-primary poppins-bold">Year End Account</h6>
                    <p class="txt-primary">
                        We will Prepare business accounts and Submitting the accounts to the relevant regulators.
                    </p>
                    <a href="" class="mx-auto d-inline bg-primary text-light py-1 px-1 w-75 rounded-3">Get Started</a>
                </div>
            </div>

            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Thinking about setting up new Business?</h2>
                <a href="{{ route('frontend.getQuotation') }}#get-qoutation" class="btn bg-primary py-2 px-5 poppins-bold text-white"> Book your Appointment</a>

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
                        <h4 class="text-light poppins-bold"><span class="txt-secondary">{{ $taxSolution->long_title }}</span> {{ $taxSolution->short_description }}</h4>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $taxSolution->long_description !!}
                    </p>

                    <div class="text-center mt-5">
                        <a href="" class=" poppins-medium btn-theme rounded-3 fs-5">Save Tax Now</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>


<section class="py-5 bg-light">
    <div class="container">
        <div class="row my-4 g-3">
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">Personal Tax</h4>
                    <small class="txt-primary">
                        Let us handle your personal tax return, ensuring accuracy, efficiency, and maximum deductions. We'll identify all allowable tax reliefs and credits to minimize your personal tax burden
                    </small>

                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">Corporation Tax </h4>
                    <small class="txt-primary">
                        Don't just pay the minimum – optimize your corporation tax strategy. We'll ensure your corporation tax is compliant while utilizing effective tax planning strategies to reduce your tax bill.
                    </small>

                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">Capital Gains Tax </h4>
                    <small class="txt-primary">
                        Selling assets can be a smart financial move, but capital gains taxes can eat into your profits. We'll help you minimize your capital gains tax liability within legal boundaries, maximizing your overall profit.
                    </small>

                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">Tax Investigation </h4>
                    <small class="txt-primary">
                        Don't miss out on valuable tax breaks! We can investigate potential tax credits you may be eligible for, reducing your overall tax burden<br> and putting more money back in your pocket.
                    </small>

                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">CIS Tax Solutions </h4>
                    <small class="txt-primary">
                        If you are work in construction industry, you need to register under HMRC’s Construction Industry Scheme (CIS). We will handle your registration, Filling, deduction, payment reports and reclaim
                    </small>

                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="card p-4 text-center">
                    <h4 class="txt-primary poppins-bold">Value Added TAX (VAT) </h4>
                    <small class="txt-primary">
                        We can help in VAT registration, Choosing appropriate VAT Scheme, VAT planning and administration, Preparing and Submitting VAT returns, Resolve any VAT issue with HMRC, Handling VAT investigation
                    </small>

                </div>
            </div>


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
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        {!! $otherSolution->long_description !!}
                    </p>

                    <div class="text-center mt-5">
                        <a href="" class=" poppins-medium btn-theme rounded-3 fs-5">Take To us Now</a>
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
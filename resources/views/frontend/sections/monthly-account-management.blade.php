<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Monthly Account Management</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        Running a business requires constant attention to detail, and your finances are no exception. At HD Accountancy Services, we understand the importance of proactive financial management to achieve your business goals.
                        That's why we offer monthly account management services, providing you with ongoing support and personalized guidance throughout the year
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
                By partnering with HD Accountancy Services for monthly account management, you gain a dedicated experienced accountant who goes beyond just processing numbers. We are invested in your success and will work tirelessly to  optimize your financial performance
                </p>
                <ul class="list-theme ps-4 mt-4">
                    <li>

                        <p class="txt-primary">
                        Setting up your new accounting management system with latest cloud based accounting solutions
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Monthly Financial Reporting & Analysis
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Tax Planning Strategies
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Tax Deadline Reminders & Filings
                        </p>
                    </li>
                    <li>

                        <p class="txt-primary">
                        Ongoing Communication & Consultation
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
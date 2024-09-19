<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Cloud Accounting</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        Cloud accounting utilizes secure online software to manage your financial data. This eliminates the need for bulky software installations and local data storage, offering a range of benefits.
                    </p>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        today's digital age, paper-based accounting feels antiquated. At HD Accountancy Services, we believe in embracing technology to simplify your financial life. That's why we offer cloud accounting solutions, transforming the way you manage your business finances
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
                    We work with several leading accounting software including Quickbooks, Sage, Kashflow, Xero, FreeAgent and Receiptbank. However, we appreciate if you have any other preference for the accounting software.
                </p>
                <ul class="list-theme ps-4 mt-4">
                    <li>
                        <p class="txt-primary">
                            Setting-up your own cloud accounting software
                        </p>
                    </li>
                    <li>
                        <p class="txt-primary">
                            Provide you initial software training
                        </p>
                    </li>
                    <li>
                        <p class="txt-primary">
                            Regular updating financial reports
                        </p>
                    </li>
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/frontend/images/Rectangle 29 (2).png') }}" class="img-fluid mb-3" alt="">
            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-1">See what our clients says </h2>
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
                                <a href="{{ route('frontend.caseStudy') }}" class="btn bg-white mt-2 poppins-bold txt-primary">See More</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            </div>

               

            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Focus on Growth, We'll Handle the Numbers</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white">Book your Appointment</a>

            </div>
        </div>
    </div>
</section>
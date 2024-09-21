<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Digital Bookkeeping Service</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                    HD Accountancy Services Ltd., offer customised bookkeeping service that perfectly fit with your business requirements. We use latest cloud technology, that allow both the client and accountant to work in the same account simultaneously means you can have real time report in hand 24/7. 
                    </p>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                    Our team members are highly skilled and certified member in using majority of the renowned book-keeping software including Sage, Xero, Quickbook, FreeAgent, Zoho, FreshBook, Receiptbank, Irish, Digita, VT Transaction etc. 
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
                Our digital bookkeeping service goes beyond basic data entry. We utilize advanced reporting tools to analyze your financial information, providing you with valuable insights into your business performance
                </p>
                <ul class="list-theme ps-4 mt-4">
                    <li>
                         
                        <p class="txt-primary">
                        Assessing your book-keeping requirements
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Setting up appropriate book-keeping technique
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Train you up cloud bookkeeping software where necessary
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Updating day to day bookkeeping using appropriate technology
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Overseeing and advising bookkeeping activities if you have separate bookkeeper
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Generate real time report for management decision making
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Make appropriate adjustment as and when required
                        </p>
                    </li>
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/frontend/images/Rectangle 29 (1).png') }}" class="img-fluid" alt="">
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
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Say Goodbye to Manual Bookkeeping</h2>
                <a href="{{ route('frontend.contact') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;">Contact Us Today</a>
                 
            </div>
        </div>
    </div>
</section>
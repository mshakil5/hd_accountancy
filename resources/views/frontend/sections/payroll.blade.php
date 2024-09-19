<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Payroll Service</h1>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                    Payroll is one of the most frequently changed compliance work employer face. Payroll processing can be a complex and time-consuming task, diverting your focus from core business activities It is a big challenge to keep up-to-date with continuous changing HMRC’s requirement. Our dedicated team aims to free you from all sort of regulatory bindings by making sure your payroll is processed timely and accurately. 
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
                we understand the importance of  accurate and efficient payroll management.  That's why we offer comprehensive payroll services designed to  simplify your workflow, ensure compliance, and free up your valuable time.
                </p>
                <ul class="list-theme ps-4 mt-4">
                    <li>
                         
                        <p class="txt-primary">
                        planning to start your payroll, PAYE registration
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        processing payslips and summary report, submitting RTI, payment to HMRC
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        Train you up cloud bookkeeping software where necessary
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        SSP, SMP and SAP processing
                        </p>
                    </li>
                    <li>
                         
                        <p class="txt-primary">
                        dealing with any payroll issues with HMRC.
                        </p>
                    </li>
                    
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/frontend/images/Rectangle 29 (3).png') }}" class="img-fluid" alt="">
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
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">Focus on Your Employees: We'll Handle Payroll Hassles</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white">Book your Appointment</a>
                 
            </div>
        </div>
    </div>
</section>
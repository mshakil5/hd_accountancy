<section class="py-5 bg-primary">
    <div class="container p-0 pt-md-5">
        <div class="row py-5">
            <div class="col-md-12 py-5">
                <div class="text-center pb-2">
                    <h1 class="text-white  py-1 text-capitalize poppins-bold">{{ $caseStudy->short_title }}</h1>
                    <h2 class="text-white text-capitalize poppins-medium">{{ $caseStudy->long_title }}</h2>
                </div>
            </div>
        </div>
    </div>
</section>

@foreach ($caseStudies as $caseStudy)
<section class="mb-5">
    <div class="container mb-5 ">
        {!!$caseStudy->long_description!!}
        <div class="col-md-12">
            <div class="text-center ">
                <a href="{{ route('frontend.getQuotation') }}" class=" mt-4 btn bg-primary py-2 px-5 poppins-bold text-white"style="border: none;"> Book your Appointment</a>                
            </div>
        </div>
    </div>
</section>
@if (!($loop->last))
<section class="py-3">
    <div class="container-fluid my-5 bg-primary  ">
        <div class="row py-5">
            <div class="col-lg-12 py-3"></div>
        </div>
    </div>
</section>
@endif
@endforeach
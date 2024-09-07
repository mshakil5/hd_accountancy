<section class="intro bg-white ">
    <div class="container ">
        <div class="row  ">
            <div class="col-lg-6 d-flex align-items-center">
                <div class="py-5">
                    <h1 class="text-center text-md-start fw-bold  txt-primary poppins-bold">
                         {{ $homePageIntro->short_title }}
                         <br>
                         {{ $homePageIntro->long_title }}
                    </h1>
                    <p class="text-center text-md-start txt-primary ">
                    {!! $homePageIntro->long_description !!}
                    </p>
                    <div class="text-center text-md-start">
                    <a href="{{ route('frontend.contact') }}#contactForm" class=" poppins-medium btn-theme rounded-3 fs-5">Talk to us Now</a>
                    </div>
                    <div class="mt-5 mb-4 align-items-center gap-3 justify-content-center  justify-content-md-start">
                        <div class="mt-5 mb-4 align-items-center gap-3 justify-content-center  justify-content-md-start">
                            @foreach($weWorkWithImages as $image)
                                <img src="{{ asset('images/we_work_with_images/' . $image->image) }}" alt="">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 overflow-hidden position-relative  ">
                <img src="{{ asset('images/meta_image/' . $homePageIntro->meta_image) }}" class="mt-5 img-fluid image-control" alt="">
            </div>
        </div>
    </div>
</section>
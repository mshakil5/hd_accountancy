<section class="py-5 bg-primary">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-12">
                <div class="text-center w-100  mx-auto">
                    <div class=" px-5">
                        <h1 class="text-light poppins-bold">Clients Testimonials</h1>
                        <h5 class="text-light poppins-bold">Your Voice is Our Inspiration</h5>
                    </div>
                    <p class="text-light w-75 text-center my-3 mx-auto">
                        We understand the importance of transparency and trust. That's why we're proud to share real client experiences through these video testimonials. Listen directly to how HD Accountancy Services has made a positive impact on their businesses
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-2 bg-light">
    <div class="container">
        <div class="row py-2 col-lg-12 mx-auto py-3 d-flex flex-wrap justify-content-center">
            <div class="testimonial">
                @foreach($data as $testimonial)
                    @if($testimonial->video)
                        <div class="p-3">
                            <video controls width="320" height="240" class="rounded-4" poster="{{ asset($testimonial->thumbnail) }}">
                                <source src="{{ asset($testimonial->video) }}" type="video/mp4">
                            </video>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="col-lg-12 text-center mt-5">
                <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">If you want to grow your business</h2>
                <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white"> Book your Appointment</a>

            </div>
        </div>
    </div>
</section>

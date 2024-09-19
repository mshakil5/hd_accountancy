<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary text-capitalize">clients testimonials</h2>
                <h5 class="poppins-medium txt-primary txt-primary text-capitalize">Your voice, Our Inspirations</h5>
            </div>
        </div>
        <div>

            <div class="testimonial">
                @foreach($clientTestimonial as $testimonial)
                    @if($testimonial->video)
                        <div class="p-3">
                            <video controls width="320" height="240" class="rounded-4" poster="{{ asset($testimonial->thumbnail) }}">
                                <source src="{{ asset($testimonial->video) }}" type="video/mp4">
                            </video>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="col-12 text-center my-3">
                <a href="{{ route('frontend.clientTestimonials') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">See More  </a>
            </div>

        </div>
    </div>
</section>
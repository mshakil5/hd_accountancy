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
            @foreach($clientTestimonials as $testimonial)
                @if($testimonial->video)
                    <div class="p-3">
                        <img src="{{ asset($testimonial->thumbnail) }}" 
                            class="rounded-4 video-thumbnail lazy-video-thumbnail" 
                            data-video-src="{{ asset($testimonial->video) }}" 
                            alt="Client Testimonial Thumbnail" 
                            width="320" height="240" 
                            style="object-fit: cover; width: 100%; height: 100%; border-radius: 0.5rem;">
                    </div>
                @endif
            @endforeach
        </div>

            <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body p-2 pb-0">
                            <div id="modalVideoContainer" class="text-center">
                                <video id="modalVideoPlayer" controls width="100%" height="auto" class="rounded-2">
                                    <source src="" type="video/mp4">
                                </video>
                                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" style="background-color: #fff; border-radius: 50%; padding: 0.5rem;" aria-label="Close" data-bs-dismiss="modal"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-center my-3">
                <a href="{{ route('frontend.clientTestimonials') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">See More  </a>
            </div>

        </div>
    </div>
</section>
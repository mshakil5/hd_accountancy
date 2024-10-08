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

<section class="py-5 bg-light">
    <div class="container">
        <div class="row py-2 d-flex flex-wrap justify-content-center">
            @foreach($data as $testimonial)
                @if($testimonial->video)
                    <div class="col-md-6 col-lg-4 p-3">
                        <img src="{{ asset($testimonial->thumbnail) }}" 
                             class="rounded-4 video-thumbnail lazy-video-thumbnail" 
                             data-video-src="{{ asset($testimonial->video) }}" 
                             alt="Client Testimonial Thumbnail" 
                             width="320" height="240" 
                             style="object-fit: cover; width: 100%; height: auto; border-radius: 0.5rem;">
                        <h5 class="txt-primary poppins-medium my-2 text-center">{{ $testimonial->description }}</h5>
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

        <div class="col-lg-12 text-center mt-5">
            <h2 class="txt-primary text-center text-capitalize poppins-bold mb-4">If you want to grow your business</h2>
            <a href="{{ route('frontend.getQuotation') }}" class="btn bg-primary py-2 px-5 poppins-bold text-white" style="border: none;">Book your Appointment</a>
        </div>
        
    </div>
</section>
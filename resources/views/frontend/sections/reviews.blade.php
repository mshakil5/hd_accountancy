<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary text-capitalize">60+ happy client reviews</h2>
                <h5 class="poppins-medium txt-primary txt-primary text-capitalize">see what they say</h5>
            </div>
        </div>
        <div class="row text-center mt-3">
            <p class="txt-primary w-75 mx-auto">
                We are incredibly grateful to our clients for their unwavering trust, loyalty, and confidence over the years. But don’t just take our word for it—check out some of our client testimonials and 60+ five-star Google reviews and see what they say
            </p>
        </div>
        <div class="row mt-5">

            <div class="testimonial">
                @foreach($googleReviews as $review)
                    <div class="p-3">
                        <div class="card rounded-5 text-center p-4 position-relative border-theme">
                        <img src="{{ asset($review->image ?? 'assets/frontend/images/male.png') }}" class="position-absolute top-0 start-50 translate-middle border border-1 border-secondary rounded-circle" width="120" height="120" class="rounded-circle" alt="">
                            <p style="color:#233969B2;" class="mt-5 text-italic text-capitalize poppins-medium fst-italic">
                                "{!! $review->message !!}"
                            </p>
                            <h6 class="txt-primary poppins-bold mb-2 mt-3">
                                {{ $review->name }}
                            </h6>
                            <h6 class="text-muted">
                                {{ $review->position }}
                            </h6>
                        </div>
                    </div>
                @endforeach
            </div>

            <p align="center" class="mt-4">
                <a href="" class="mx-auto">
                    <img src="{{ asset('assets/frontend/images/Google Review.png') }}" alt="">
                </a>
            </p>

        </div>
    </div>
</section>
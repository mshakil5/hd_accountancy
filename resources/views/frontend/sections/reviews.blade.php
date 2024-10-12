<section class="py-5 position-relative">
    <div class="container mx-auto">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary text-capitalize">60+ happy client reviews</h2>

            </div>
            <div class="col-8 mx-auto mt-3 mb-4">
                <p class="mt-3 txt-primary text-capitalize text-justify-sm" style="font-size: 20px; font-weight: 400;">We are incredibly grateful to our clients for their unwavering trust, loyalty, and confidence over the years. But don’t just take our word for it—check out some of our client testimonials and 60+ five-star Google reviews and see what they say</p>
            </div>
            

        <div class="testimonial">
            @foreach($googleReviews as $review)
                <div class="p-4">
                    <div class="card rounded-5 text-center p-4 position-relative border-theme">
                    <img src="{{ asset($review->image ?? 'assets/frontend/images/male.png') }}" class="position-absolute top-0 start-50 translate-middle border border-1 border-secondary rounded-circle" width="120" height="120" @if(!$review->image ) style="object-fit: contain; padding: 7px;" @endif alt="">
                        <p style="color:#233969B2;" class="mt-5 text-italic text-capitalize poppins-medium fst-italic">
                        {!! \Illuminate\Support\Str::limit($review->message, 200) !!}
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



            <a href="https://maps.app.goo.gl/YdwshR7HabGbGg9N9" class="mx-auto" target="_blank">
                <img src="{{ asset('assets/frontend/images/Google Review.png') }}" alt="">
            </a>
        </p>

        <div class="col-12 text-center mt-3">
            <a href="{{ route('frontend.reviews') }}" class="btn btn-theme-outline d-inline w-50 mx-auto rounded-3 fs-6">See More Reviews</a>
        </div>
    </div>
</section>

<style>
    @media (max-width: 576px) {
        .text-justify-sm {
            font-size: 15px;
        }
        .col-8 {
            width: 83.3333%;
        }
    }
</style>
@extends('frontend.layouts.frontend')

@section('content')
<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title">
                <h2 class="poppins-bold txt-primary text-capitalize">clients testimonials</h2>
                <h5 class="poppins-medium txt-primary txt-primary text-capitalize">Your voice, Our Inspirations</h5>
            </div>
        </div>
        <div class="row mt-5">

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
        </div>
    </div>
</section>
@endSection
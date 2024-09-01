@extends('frontend.layouts.frontend')

@section('content')

<!-- intro sections -->
@include('frontend.sections.intro')

<!-- pricing-packeages sections -->
@include('frontend.sections.pricing-package')

<!-- services sections -->
@include('frontend.sections.services')

<!-- Client Testimonial -->
@include('frontend.sections.testimonial')

<!-- Client Reviews -->
@include('frontend.sections.reviews')

<!-- Case Study -->
@include('frontend.sections.case-study')

<!-- Business Goals -->
 <section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title">
                <h2 class="poppins-bold txt-primary">Let's Discuss Your Business Goals</h2>
                <h5 class="poppins-medium txt-primary txt-primary">Schedule Your Free Consultation</h5>
            </div>
        </div>
        @include('frontend.sections.book-consulation')
    </div>
</section>
<!-- Latest Insight -->
@include('frontend.sections.blog')

@endsection
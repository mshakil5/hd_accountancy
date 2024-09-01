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
@include('frontend.sections.business-goals')

<!-- Latest Insight -->
@include('frontend.sections.blog')

@endsection
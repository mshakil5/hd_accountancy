@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title)
@section('meta_description', $meta->meta_description)
@section('meta_image', asset('images/meta_image/'.$meta->meta_image))

@section('content')

<!-- Contact heading -->
@include('frontend.sections.contact-heading')

<!-- Contact form -->
@include('frontend.sections.contact-form')

<!-- Book Consultation -->
 <section class="py-5 position-relative bg-light mb-5">
    <div class="container">
        <div class="row text-center">
             <h2 class="poppins-bold txt-primary">Book Your Free Consultation</h2> 
        </div>
        @include('frontend.sections.book-consulation')
    </div>
</section>
@endsection
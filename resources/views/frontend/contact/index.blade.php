@extends('frontend.layouts.frontend')

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
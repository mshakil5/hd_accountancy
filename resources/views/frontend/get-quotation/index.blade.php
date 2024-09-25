@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title)
@section('meta_description', $meta->meta_description)
@section('meta_image', asset('images/meta_image/'.$meta->meta_image))

@section('content')

<!-- Get Qoutation form -->
@include('frontend.sections.get-qoutation')

<!-- Book Consultation -->
 <section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
             <h2 class="poppins-bold txt-primary">Book Your Free Consultation</h2> 
        </div>
        @include('frontend.sections.book-consulation')
    </div>
</section>

@endsection
@extends('frontend.layouts.frontend')
@section('meta_title', $meta->meta_title ?? 'HD Accountancy')
@section('meta_description', $meta->meta_description ?? 'HD Accountancy')
@section('meta_keywords', $meta->meta_keywords ?? 'HD Accountancy')
@section('meta_image', $meta->meta_image ? asset('images/meta_image/' . $meta->meta_image) : '')

@section('content')

<!-- Booking-->
 <section class="py-5 position-relative bg-light mb-5">
    <div class="container">
        <div class="row text-center">
             <h2 class="poppins-bold txt-primary">Booking Now</h2> 
        </div>
        @include('frontend.sections.book-consulation')
    </div>
</section>
@endsection
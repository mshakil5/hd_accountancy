@extends('frontend.layouts.frontend')

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
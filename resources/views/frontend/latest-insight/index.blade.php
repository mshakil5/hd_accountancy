@extends('frontend.layouts.frontend')

@section('meta_title', 'HD Accountancy | Latest Insights')
@section('meta_description', 'HD Accountancy | Level Up Your Knowledge')
@section('meta_keywords', 'HD Accountancy, Latest Insights, Knowledge, Accounting, Finance')
@section('meta_image', $meta->meta_image ? asset('images/meta_image/' . $meta->meta_image) : '')


@section('content')

<!-- latest insight-->
@if(count($data) > 0)
<section class="py-5 position-relative">
    <div class="container">
        <div class="row text-center">
            <div class="title-with-sub-title" style="background-color: #f7f8fe;">
                <h2 class="poppins-bold txt-primary">Latest Insights</h2>
                <h5 class="poppins-medium txt-primary txt-primary">Level Up Your Knowledge</h5>
            </div>
        </div>
        <div class="row mt-5">
            @foreach($data as $item)
                <div class="col-lg-4">
                    <a href="{{ route('latest-insights.show', $item->slug) }}">
                        <img src="{{ asset($item->image) }}" class="img-fluid">
                        <h5 class="poppins-medium txt-primary my-3">
                            {{ $item->short_title }}
                        </h5>
                    </a>
                    <br>
                    <p class="txt-primary">
                        {{ $item->short_description }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
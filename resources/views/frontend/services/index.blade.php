@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title)
@section('meta_description', $meta->meta_description)
@section('meta_image', asset('images/meta_image/'.$meta->meta_image))

@section('content')

<!-- Services -->
@include('frontend.sections.services-page')

@endsection
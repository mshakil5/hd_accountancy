@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title ?? 'HD Accountancy')
@section('meta_description', $meta->meta_description ?? 'HD Accountancy')
@section('meta_image', $meta->meta_image ? asset('images/meta_image/' . $meta->meta_image) : '')

@section('content')

<!--  Our team -->
@include('frontend.sections.our-team')

@endsection
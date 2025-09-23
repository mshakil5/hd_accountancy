@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title ?? 'HD Accountancy')
@section('meta_description', $meta->meta_description ?? 'HD Accountancy')
@section('meta_keywords', $meta->meta_keywords ?? 'HD Accountancy')
@section('meta_image', $meta->meta_image ? asset('images/meta_image/' . $meta->meta_image) : '')

@section('content')

<!-- Career heading-->
@include('frontend.sections.career-heading')

<!-- Career form -->
@include('frontend.sections.career-form')

@endsection
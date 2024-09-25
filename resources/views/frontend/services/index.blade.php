@extends('frontend.layouts.frontend')

@section('meta_title', $meta->meta_title ?? 'HD Accountancy')
@section('meta_description', $meta->meta_description ?? 'HD Accountancy')
@section('meta_image', $meta->meta_image ? asset('images/meta_image/' . $meta->meta_image) : '')

@section('content')

<!-- Services -->
@include('frontend.sections.services-page')

@endsection
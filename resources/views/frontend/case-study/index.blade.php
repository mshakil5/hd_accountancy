@extends('frontend.layouts.frontend')

@section('meta_title', $caseStudy->meta_title ?? 'HD Accountancy')
@section('meta_description', $caseStudy->meta_description ?? 'HD Accountancy')
@section('meta_keywords', $caseStudy->meta_keywords ?? 'HD Accountancy')
@section('meta_image', $caseStudy->meta_image ? asset('images/meta_image/' . $caseStudy->meta_image) : '')

@section('content')

<!-- Case Study-->
@include('frontend.sections.case-study-page')

@endsection
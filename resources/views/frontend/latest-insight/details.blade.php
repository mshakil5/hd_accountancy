@extends('frontend.layouts.frontend')


@section('meta_title', $latestInsight->short_title ?? 'HD Accountancy')
@section('meta_description', $latestInsight->long_description ?? 'HD Accountancy')
@section('meta_keywords', 'HD Accountancy, Latest Insights, Knowledge, Accounting, Finance,'. $latestInsight->short_title ?? '')
@section('meta_image', $latestInsight->image ? asset('images/meta_image/' . $latestInsight->image) : '')

@section('content')

<!-- Career heading-->
@include('frontend.sections.latest-insight-details')

@endsection
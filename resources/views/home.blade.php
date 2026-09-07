@extends('layouts.app')

@section('title', ($settings['storeName'] ?? 'ATELIER') . ' — Precision Crafted Luxury Accessories & Bespoke EDC')

@section('meta_description', $settings['homeHeroSubtitle'] ?? 'Handcrafted full-grain Italian leather, aerospace titanium hardware, and minimalist RFID architecture.')

@section('content')
    <!-- 0. Top Admin-Controlled Promotional Banner -->
    @include('partials.promo-banner')

    <!-- 1. Product Scroll Showcase -->
    @include('partials.showcase')

    <!-- 2. Craftsmanship & Heritage Guarantee Banner -->
    @include('partials.banner')
@endsection

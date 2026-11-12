@extends('errors.layout')

@php
    // Kembali ke halaman form asal, tetapi hanya jika asalnya dari situs ini sendiri
    $referer = (string) request()->headers->get('referer');
    $back = str_starts_with($referer, url('/').'/') ? $referer : url('/');
@endphp

@section('code', '419')
@section('title', 'Halaman kedaluwarsa')
@section('message', 'Halaman ini terlalu lama terbuka sehingga form-nya tidak berlaku lagi. Muat ulang halaman, lalu kirim kembali.')

@section('actions')
    <a href="{{ $back }}" class="btn btn-primary">Muat ulang halaman</a>
    <a href="{{ url('/') }}" class="btn btn-outline">Kembali ke beranda</a>
@endsection

@extends('layouts.app')

@section('link')
    <script src="{{ asset('js/app.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/chat.css')}}">
@endsection

@section('content')
    <private-chat :user="{{ Auth::user() }}" :f="{{$friends}}" :u="{{$users}}" :r="{{$requests}}"></private-chat>
@endsection
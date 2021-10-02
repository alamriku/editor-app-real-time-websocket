@extends('layouts.app')

@section('content')
<div class="container">
   <editor :user="{{auth()->user()}}"></editor>
</div>
@endsection

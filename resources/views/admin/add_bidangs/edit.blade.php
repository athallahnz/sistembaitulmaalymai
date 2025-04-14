@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Bidang</h1>
    <form action="{{ route('add_bidangs.update', $bidang->id) }}" method="POST">
        @method('PUT')
        @include('admin.add_bidangs.form')
    </form>
</div>
@endsection

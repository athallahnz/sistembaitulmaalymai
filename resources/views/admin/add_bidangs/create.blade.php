@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tambah Bidang</h1>
    <form action="{{ route('add_bidangs.store') }}" method="POST">
        @include('admin.add_bidangs.form')
    </form>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="container">
        <h2 class="mb-4">Dashboard</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Welcome Back, {{ Auth::user()->name }}!! </h5>
                        <p class="card-text">This is your dashboard where you can manage everything.</p>
                    </div>
                </div>
            </div>
        </div>
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
@endsection
@section('scripts')
@endsection

@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <h3>Welcome, {{ Auth::user()->name }}!</h3>
                        <p>You are logged in as an {{ Auth::user()->role }}!</p>

                        <!-- Example content for admin dashboard -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">{{ __('Manage Users') }}</div>
                                    <div class="card-body">
                                        {{-- <a href="{{ route('users.index') }}" class="btn btn-primary">View Users</a> --}}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">{{ __('Reports') }}</div>
                                    <div class="card-body">
                                        {{-- <a href="{{ route('reports.index') }}" class="btn btn-primary">View Reports</a> --}}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">{{ __('Settings') }}</div>
                                    <div class="card-body">
                                        {{-- <a href="{{ route('settings.index') }}" class="btn btn-primary">App Settings</a> --}}
                                    </div>
                                </div>
                            </div>
                            <a href="#"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

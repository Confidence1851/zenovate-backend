@extends('auth.layouts.app', [
    'title' => 'Reset Password',
    'sub_title' => '',
])

@section('content')
    @if (session('status'))
        <div class="alert alert-success text-center text-green-500 mb-4">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Field -->
        <div class="space-y-2">
            <label class="form-label @error('email') text-red-500 @enderror" for="email">{{ __('Email Address') }}</label>
            <div class="relative">
                <input type="email"
                    class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('email') border-red-500 @enderror"
                    name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end mt-6">
            <button type="submit" class="w-full bg-black text-white h-10 rounded-md flex justify-center items-center">
                {{ __('Send Password Reset Link') }}
            </button>
        </div>
    </form>
@endsection

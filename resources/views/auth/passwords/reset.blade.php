@extends('auth.layouts.app', [
    'title' => 'Reset Password',
    'sub_title' => '',
])

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <!-- Email Field -->
        <div class="space-y-2">
            <label class="form-label @error('email') text-red-500 @enderror" for="email">{{ __('Email Address') }}</label>
            <div class="relative">
                <input type="email"
                    class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('email') border-red-500 @enderror"
                    name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Password Field -->
        <div class="space-y-2 mt-4">
            <label class="form-label @error('password') text-red-500 @enderror" for="password">{{ __('Password') }}</label>
            <div class="relative">
                <input type="password"
                    class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('password') border-red-500 @enderror"
                    name="password" required autocomplete="new-password">
                @error('password')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Confirm Password Field -->
        <div class="space-y-2 mt-4">
            <label class="form-label" for="password-confirm">{{ __('Confirm Password') }}</label>
            <div class="relative">
                <input type="password"
                    class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input"
                    name="password_confirmation" required autocomplete="new-password">
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end mt-6">
            <button type="submit" class="w-full bg-black text-white h-10 rounded-md flex justify-center items-center">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
@endsection

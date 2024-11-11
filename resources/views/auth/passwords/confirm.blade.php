@extends('auth.layouts.app', [
    'title' => 'Confirm Password',
    'sub_title' => 'Please confirm your password before continuing.',
])

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <!-- Password Field -->
        <div class="space-y-2">
            <label class="form-label @error('password') text-red-500 @enderror" for="password">{{ __('Password') }}</label>
            <div class="relative">
                <input type="password"
                    class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('password') border-red-500 @enderror"
                    name="password" required autocomplete="current-password">
                @error('password')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end mt-6">
            <button type="submit" class="w-full bg-black text-white h-10 rounded-md flex justify-center items-center">
                {{ __('Confirm Password') }}
            </button>
        </div>

        <!-- Forgot Password Link -->
        @if (Route::has('password.request'))
            <div class="text-center mt-4">
                <a href="{{ route('password.request') }}" class="text-green-500 text-sm font-semibold">
                    {{ __('Forgot Your Password?') }}
                </a>
            </div>
        @endif
    </form>
@endsection

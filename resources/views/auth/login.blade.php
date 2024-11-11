@extends('auth.layouts.app', [
    'title' => 'Welcome Back!',
    'sub_title' => 'Sign in to continue your health journey with us',
])

@section('content')
    <form class="space-y-8 py-4" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="flex flex-col justify-end gap-4">
            <!-- Email Field -->
            <div class="space-y-2">
                <p class="form-label @error('email') text-red-500 @enderror">Email address</p>
                <div class="relative">
                    <input type="email"
                        class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('email') border-red-500 @enderror"
                        placeholder="email@domain.com" name="email" value="{{ old('email') }}">
                    @error('email')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <p class="form-label @error('password') text-red-500 @enderror">Password</p>
                <div class="relative">
                    <input type="password"
                        class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm placeholder-gray-400 pl-[18px] pr-[18px] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 form-input @error('password') border-red-500 @enderror"
                        placeholder="*******************" name="password">
                    <div class="absolute rounded-r-lg top-0 flex items-center h-full pl-1 right-2 bg-transparent">
                        <button
                            class="inline-flex items-center justify-center text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-800 p-0 shadow-none"
                            type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                                fill="none">
                                <path
                                    d="M21.25 9.15C18.94 5.52 15.56 3.43 12 3.43c-1.78 0-3.51.52-5.09 1.49-1.58.98-3 2.41-4.16 4.23-1 1.57-1 4.12 0 5.69 2.31 3.64 5.69 5.72 9.25 5.72 1.78 0 3.51-.52 5.09-1.49 1.58-.98 3-2.41 4.16-4.23 1-1.56 1-4.12 0-5.69ZM12 16.04c-2.24 0-4.04-1.81-4.04-4.04S9.76 7.96 12 7.96s4.04 1.81 4.04 4.04-1.8 4.04-4.04 4.04Z"
                                    fill="#162c15"></path>
                                <path
                                    d="M11.998 9.14a2.855 2.855 0 0 0 0 5.71c1.57 0 2.86-1.28 2.86-2.85s-1.29-2.86-2.86-2.86Z"
                                    fill="#162c15"></path>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <input type="checkbox"
                    class="w-4 h-4 rounded-md border-gray-300 focus-visible:ring-1 focus-visible:ring-gray-800"
                    id="remember-device">
                <label for="remember-device" class="text-gray-500">Remember this device</label>
            </div>
            <a class="text-green-500 text-sm md:text-base font-semibold" href="{{ route('password.request') }}">Forgot your
                password?</a>
        </div>
        <button type="submit" class="w-full bg-black text-white h-10 flex justify-between items-center p-4">
            <span class="uppercase">Sign in</span>
            <svg class="text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                    stroke-width="1.5" d="M14.43 5.93L20.5 12l-6.07 6.07M3.5 12h16.83">
                </path>
            </svg>
        </button>
        {{-- <p class="text-center">Don't have an account? <a class="text-green-500 text-sm md:text-base font-semibold"
                href="{{ route('register') }}">Sign
                up</a></p> --}}
    </form>
@endsection

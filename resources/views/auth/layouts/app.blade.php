<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Login Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .form-label {
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            --tw-text-opacity: 1;
            color: rgb(22 44 21 / var(--tw-text-opacity)) !important;
        }

        .text-green-500 {
            --tw-text-opacity: 1;
            color: rgb(22 44 21 / var(--tw-text-opacity)) !important;
        }

        .form-input {
            height: 2.75rem !important;
            border-radius: 0px !important;
            border-width: 1px !important;
            font-size: 1rem !important;
            line-height: 1.5rem !important;
            --tw-text-opacity: 1;
            color: rgb(2 6 23 / var(--tw-text-opacity)) !important;
            --tw-ring-color: hsl(var(--primary));
        }
    </style>
</head>

<body class="bg-white font-sans">
    <main class="lg:flex h-[100vh]">
        <!-- Login Form Section -->
        <div class="w-full lg:w-2/3 flex justify-center items-center flex-col h-full p-4">
            <div class="space-y-8 w-full max-w-[600px] mx-auto">
                <div class="space-y-2 text-center lg:text-left">
                    <h1 class="text-2xl md:text-4xl font-bold text-black uppercase">{{ $title ?? '' }}</h1>
                    <h2 class="prose-lg font-semibold text-gray-500 uppercase">{{ $sub_title ?? '' }}</h2>
                </div>
                <div>
                    @yield('content')
                </div>
                <a href="/" class="block text-center">
                    <h4 class="text-2xl font-bold capitalize">Zenovate</h4>
                </a>
            </div>
        </div>

        <!-- Side Image Section -->
        <div class="hidden lg:block w-1/3 lg:flex-1 h-full p-4 lg:min-w-[600px] xl:min-w-[800px] max-w-[1000px]">
            <div
                class="h-full w-full bg-[url('{{ url('assets/images/auth-image.jpg') }}')] bg-no-repeat bg-cover rounded-sm bg-right">
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('button[type="button"]');
            const passwordInput = document.querySelector('input[name="password"]');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
            });
        });
    </script>

</body>

</html>

<!doctype html>
<html lang="en">

<!-- Head -->

<head>
    <!-- Page Meta Tags-->
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $metadataService = new App\Services\General\MetadataService();
        $metadata = $metadataService->createMetadata([
            'title' => $title ?? null,
            'description' => $description ?? null,
            'openGraph' => [
                'title' => $title ?? null,
                'description' => $description ?? null,
            ],
        ]);
    @endphp
    {!! $metadataService->renderMetaTags($metadata) !!}

    <!-- Google Font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <!-- Vendor CSS -->
    <link rel="stylesheet" href="{{ $admin_assets }}/css/libs.bundle.css" />

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ $admin_assets }}/css/theme.bundle.css" />

    <!-- Fix for custom scrollbar if JS is disabled-->
    {{-- <noscript> --}}
    <style>
        /**
          * Reinstate scrolling for non-JS clients
          */
        .simplebar-content-wrapper {
            overflow: auto;
        }

        .logo-text {
            font-weight: 900;
            color: white
        }

        .bg-theme,
        .bg-primary {
            background-color: #162c15;
        }


        .text-theme,
        .text-primary {
            color: #162c15 !important
        }

        .text-grey {
            color: grey;
        }
    </style>
    {{-- </noscript> --}}

</head>

<body class="">
    @include('admin.layout.includes.navbar')
    @yield('content')
    @include('admin.layout.includes.aside')
    <!-- Theme JS -->
    <!-- Vendor JS -->
    <script src="{{ $admin_assets }}/js/vendor.bundle.js"></script>

    <!-- Theme JS -->
    <script src="{{ $admin_assets }}/js/theme.bundle.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/tinymce.min.js"></script>

    <script>
        if (jQuery('.tinymceEditor').length > 0) {
            tinymce.init({
                selector: '.tinymceEditor',
                height: 200,
                theme: 'modern',
                plugins: [' link image print preview hr anchor pagebreak emoticons',
                    'searchreplace wordcount visualblocks visualchars code fullscreen',
                    'insertdatetime nonbreaking save table contextmenu directionality'
                ],
                toolbar1: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
                toolbar2: 'print preview media | forecolor backcolor emoticons | codesample help',
                image_advtab: true,
            });
        }
    </script>
    @stack('scripts')
</body>

</html>

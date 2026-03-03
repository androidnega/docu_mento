<!DOCTYPE html>
<html lang="en" class="dm-public">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1F3A">
    <title>@yield('title', 'Docu Mento')</title>
    {{-- Shared favicon + fonts for public/landing pages --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- Tailwind CDN – Docu Mento palette only; no gradients --}}
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        'dm-bg': '#F8F9F6',
                        'dm-primary': '#0B1F3A',
                        'dm-secondary': '#2E7D5B',
                        'dm-text': '#1E1E1E',
                        'dm-border': '#E5E7EB',
                        'dm-card': '#FFFFFF',
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/docu-mento-public.css') }}">
    @stack('styles')
</head>
<body class="font-sans antialiased bg-dm-bg text-dm-text">
    @yield('content')
    @stack('scripts')
</body>
</html>

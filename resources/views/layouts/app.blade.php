<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Roomaster' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    {{-- Font Plus Jakarta Sans: 400 (Regular), 500 (Medium), 600 (SemiBold), 800 (ExtraBold) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;800&display=swap" rel="stylesheet">

    {{-- CSS (Tailwind) dan JS dari Vite. Ikon tidak butuh library: <x-icon> sudah berisi SVG. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-grey-200 h-dvh overflow-hidden flex items-center justify-center p-0 md:p-6 font-sans antialiased select-none">

    {{-- Bingkai layar HP (390 x 844). Isi yang lebih tinggi bisa di-scroll vertikal. --}}
    <main class="no-scrollbar w-full max-w-[390px] h-dvh md:h-[844px] bg-grey-50 shadow-2xl rounded-none md:rounded-[40px] overflow-y-auto overflow-x-hidden relative border border-grey-300">
        @yield('content')
    </main>

</body>
</html>

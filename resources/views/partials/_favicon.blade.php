{{-- The flower mark, everywhere the browser asks for an icon. /favicon.ico is
     auto-probed as the fallback and carries the same 32px art. --}}
<link rel="icon" href="{{ asset('favicon-32.png') }}?v={{ filemtime(public_path('favicon-32.png')) }}" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('favicon-180.png') }}?v={{ filemtime(public_path('favicon-180.png')) }}" sizes="180x180">

<meta charset="utf-8" />
<meta http-equiv="x-ua-compatible" content="ie=edge" />
<title>IP Shark - @yield('title')</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link rel="stylesheet" href="{{ elixir('css/main.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/fontawesome-v4.5.0/css/font-awesome.min.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/remodal-v1.0.6/remodal.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/remodal-v1.0.6/remodal-default-theme.css') }}" />
<link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.0.0-beta.15/dist/vue-multiselect.min.css" />
<script src="{{ asset('vendor/jquery-v2.2.1/jquery-2.2.1.min.js') }}"></script>
<script src="{{ asset('vendor/remodal-v1.0.6/remodal.min.js') }}"></script>
@stack('scripts')

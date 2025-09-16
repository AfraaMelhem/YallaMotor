<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">
<head>
    @include('includes.head')
    @stack('styles')
</head>
<body>
    <!-- LOADER -->
    <div id="loader">
        <img src="{{ asset('build/assets/images/media/loader.svg') }}" alt="Loading ....">
    </div>
    <!-- END LOADER -->
    <!-- PAGE -->
    <div class="page">
        @include('includes.navbar')
        @include('includes.sidebar')
        @yield('content')
    </div>
    <!-- END PAGE-->
    @include('includes.scripts')
    @stack('scripts')
</body>
</html>

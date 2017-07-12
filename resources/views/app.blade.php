<!DOCTYPE html>
<html>
    <head>
        @include('partials.head')
    </head>
    <body>
        <div id="app">
            <router-view :auth="auth">
                <div class="loading-placeholder">
                    <div class="loading-placeholder__inner">
                        <i class="fa fa-cog fa-spin"></i>
                        <div class="loading-placeholder__logo"></div>
                    </div>
                </div>
            </router-view>
        </div>
        <script src="{{ elixir('js/app.js') }}"></script>
    </body>
</html>

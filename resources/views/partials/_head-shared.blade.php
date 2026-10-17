{{--
    Included in every layout's <head>: the tab icon, and the first-language hint.

    First language: the server picks Khmer for visitors in Cambodia (see
    App\Http\Middleware\SetLocale). The browser's time zone is the most
    reliable private signal for that, and only the browser knows it, so it is
    reported once in the pd_tz cookie. If it says Cambodia and this page came
    out in English only because nothing was known yet, the page reloads once
    in Khmer. Never when the visitor has chosen a language, never twice, and
    never when cookies are off. No location, no third party.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<script>
    (function () {
        var auto = @js(request()->attributes->get('locale_source') === 'auto');
        var lang = @js(app()->getLocale());
        try {
            if (document.cookie.indexOf('pd_tz=') !== -1) return;
            var zone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            if (!zone) return;
            document.cookie = 'pd_tz=' + encodeURIComponent(zone) + '; path=/; max-age=31536000; samesite=lax';
            if (auto && lang !== 'km' && zone === 'Asia/Phnom_Penh'
                && document.cookie.indexOf('pd_tz=') !== -1
                && !sessionStorage.getItem('pd-tz-reload')) {
                sessionStorage.setItem('pd-tz-reload', '1');
                location.reload();
            }
        } catch (e) {}
    })();
</script>

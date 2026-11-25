{{--
    Full-screen shell for admin guest pages (login).
    Props:
        title  Page title.
--}}
@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-admin.head :title="$title" />
</head>
<body class="admin-body">
    <div class="admin-auth-language"><x-language-switcher /></div>
    <main class="admin-auth">
        {{ $slot }}
    </main>

    <div class="toast-root" id="toast-root" aria-live="polite"></div>
    <x-flash-toasts />
</body>
</html>

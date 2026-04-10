<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session expired</title>
</head>
<body>
<p>Your session expired. Redirecting to login…</p>

<script>
{{--if (!sessionStorage.getItem('redirected_419')) {--}}
{{--    sessionStorage.setItem('redirected_419', 'true');--}}
{{--    window.location.replace('{{ route('login') }}');--}}
{{--}--}}
{{--window.location.replace('{{ route('login') }}');--}}
{{--window.top.location.href = {{ route('login') }};--}}
    window.top.location.href = "{{ route('login') }}";
</script>
</body>
</html>

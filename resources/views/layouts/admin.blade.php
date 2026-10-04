<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>IT Admin</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@yield('content')
<script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>
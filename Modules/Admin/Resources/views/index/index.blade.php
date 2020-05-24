<!doctype html>
<html lang="{{ app()->getLocale() }}">
<link rel="icon" href="favicon.png">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理后台</title>
</head>
<body>
<div id="app"></div>
<script src="{{ mix('js/main.js') }}"></script>
</body>
</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>
		{{ config('app.name') }}
	</title>

	<link rel="stylesheet" href="{{ mix('css/app.css') }}">
	@yield('head')
</head>
<body>
	@yield('content')

	<footer>
		<div>
			Ponygon
		</div>

		<a href="https://github.com/drweissbrot/ponygon">
			GitHub
		</a>

		<a href="https://drweissbrot.net">
			<img src="https://drweissbrot.net/img/dr-prot.png">
			drweissbrot
		</a>
	</footer>
</body>
</html>

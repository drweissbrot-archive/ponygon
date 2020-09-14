@extends('layouts.app')

@section('head')
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<script src="{{ mix('js/app.js') }}" async defer></script>
@endsection

@section('content')
	<div id="app">
		<ponygon-app>
			Loading...
		</ponygon-app>

		<noscript>
			Ponygon requires JavaScript.
			Please enable JavaScript in your browser.
		</noscript>
	</div>
@endsection

@extends('layouts.parent')
@section('title', 'foo')
@section('sidebar')
	@parent
	appended
@endsection
@section('body')
	foobar
@endsection


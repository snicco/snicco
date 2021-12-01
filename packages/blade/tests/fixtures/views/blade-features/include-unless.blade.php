@php
	$match = $foo === 'foo'
@endphp

@includeUnless($match, 'blade-features.child', ['name' => 'Calvin', 'greeting' => $greeting])

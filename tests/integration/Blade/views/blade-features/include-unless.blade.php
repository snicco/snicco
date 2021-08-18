@php
	$match = $foo === 'foo'
@endphp

@includeUnless($match, 'child', ['name' => 'Calvin', 'greeting' => $greeting])

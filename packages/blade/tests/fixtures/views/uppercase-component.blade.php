<x-uppercase>
	<x-slot name="title">CALVIN</x-slot>
	{{$content}}

	@if(isset($scoped))
		<x-slot name="scoped">
			{{$component->toUpper($scoped)}}
		</x-slot>
	@endif

</x-uppercase>


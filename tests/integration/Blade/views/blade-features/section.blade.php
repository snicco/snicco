@hasSection('foo')
	@yield('foo')
@endif
@sectionMissing('bar')
	BAZ
@endif

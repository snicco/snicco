Name:@yield('title'),
SIDEBAR:
@section('sidebar')
	parent_sidebar.
@show
,BODY:@yield('body'),
FOOTER:@yield('footer', 'default_footer')

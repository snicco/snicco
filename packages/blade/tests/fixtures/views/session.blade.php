@if(isset($session) && $session instanceof \Snicco\Session\Session)
	view has session
@else
	view does not have session
@endif



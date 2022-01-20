@if(isset($session) && $session instanceof \Snicco\Component\Session\SessionInterface)
	view has session
@else
	view does not have session
@endif



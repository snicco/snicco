<?php


	declare( strict_types = 1 );
	global $view_rendered;
$view_rendered = 'parent';
?>
foo<?php \App::layoutContent(); ?>

<?php

declare(strict_types=1);

/** @var \Snicco\View\Contracts\ViewFactoryInterface $view_factory */

/** @var string $view */

use Snicco\View\Contracts\ViewFactoryInterface;

?>

<html <?php language_attributes() ?> >
	<head>
		
		<meta charset="utf-8">
		<title><?= $title ?? 'Authentication' ?></title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
		<link
				rel="stylesheet"
				href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css"
		>
	</head>
	<body>
		
		<style>
			
			body {
				background: #f1f1f1;
			}
			
			.underlined {
				text-decoration: underline;
			}
			
			.hide {
				display: none !important;
			}
			
			.button.submit {
				background: #2271b1;
				border-color: #2271b1;
				display: inline-block;
				width: 100%;
				color: white;
			}
			
			#login_button {
				
				background: #2271b1;
				border-color: #2271b1;
				display: inline-block;
				width: 100%;
				color: white;
			}
			
			#logo {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				text-align: center;
				transition-property: border, background, color;
				transition-duration: .05s;
				transition-timing-function: ease-in-out;
				background-image: none, url(/wp-admin/images/wordpress-logo.svg);
				background-size: 84px;
				background-position: center top;
				background-repeat: no-repeat;
				color: #3c434a;
				height: 84px;
				font-size: 20px;
				font-weight: 400;
				line-height: 1.3;
				margin: 0 auto 25px;
				padding: 0;
				text-decoration: none;
				width: 84px;
				text-indent: -9999px;
				outline: 0;
				overflow: hidden;
				display: block;
			}
			
			.hero-body .label {
				font-weight: 400;
			}
		
		</style>
		
		<section class="hero is-fullheight">
			<div class="hero-body">
				<div class="container">
					
					<a href="https://wordpress.org/" id="logo"></a>
					
					<div class="columns is-centered">
						<div class="column is-5-tablet is-4-desktop is-3-widescreen">
							
							<?= $__content ?>
						
						</div>
					</div>
				
				</div>
			</div>
		</section>
	</body>
</html>



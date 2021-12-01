<?php

declare(strict_types=1);

$site_name = \Snicco\Support\WP::siteName();

?>

<html <?php language_attributes() ?> >
	<head>
		<meta charset="utf-8">
		<title><?= $title ?? 'You are being redirected' ?></title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
		<link
				rel="stylesheet"
				href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
		>
	</head>
	
	<body>
		
		<section class="hero">
			<div class="hero-body">
				<div class="container is-max-desktop ">
					<div class="notification is-danger is-light">
                  <span class="icon has-text-danger is-large">
                  <i class="fas fa-exclamation-triangle fa-3x"></i>
             </span>
						<p class="is-size-2 mt-2 mb-2">Proceed with caution!</p>
						<p class="is-size-4 mb-2"> You are being redirect to <b
									class="has-text-info"
							> <?= $untrusted_url ?>.</b> This site is out of our control. <br> Dont
						                           enter any credentials for your account
						                           at <?= esc_html($site_name) ?>
						                           on this site.</p>
					</div>
					<div class="p-2">
						<p class="is-size-4 mb-6"> If you did not intend to visit that site <b> DO
						                                                                        NOT
						                                                                        PROCEED.</b>
						</p>
						<a href="<?= esc_url($home_url) ?>" class="button is-info is-size-5 mr-2">Go
						                                                                          back
						                                                                          to
						                                                                          the
						                                                                          homepage
						                                                                          of <?= esc_html(
									$site_name
							) ?></a>
						<a
								href="<?= esc_url($untrusted_url) ?>"
								class="button is-danger is-light is-size-5"
						>Go to <?= $untrusted_url ?></a>
					</div>
				
				</div>
			</div>
		</section>
	
	</body>
</html>



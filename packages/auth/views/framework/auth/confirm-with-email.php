<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

/** @var Snicco\Support\ViewErrorBag $errors */
/** @var Snicco\Session\Session $session */

?>

<div class="box">
	
	<!--    Authentication has not been confirmed yet -->
	<?php if ( ! $session->hasValidAuthConfirmToken()) : ?>
		
		<div class="notification is-info is-light">
			<p class="is-size-5">
				This page is part of the secure area of the application!
			</p>
			<p class="is-size-5 mt-2 mb-2">
				You need to confirm your access before you can proceed.
			</p>
		</div>
	
	<?php endif; ?>
	
	<!--   There have been errors flashed by the auth confirmation -->
	<?php if ($errors->has('auth.confirmation')) : ?>
		
		<div class="notification is-danger is-light mt-2">
			<p class="is-size-5">
				Authentication failed.
			</p>
			<p class="is-size-5 mt-2">
				Your confirmation link was invalid or expired. Please request a new one.
			</p>
		</div>
	
	<?php endif; ?>
	
	<!--   Check if user can request another email -->
	<?php if (time() > $session->get('auth.confirm.email.next', 0)) : ?>
		
		<form id="send" class="mt-4 box " action="<?= esc_attr($post_to) ?>" method="POST">
			
			<?= $csrf->asHtml() ?>
			<button
					type="submit"
					class="button submit mb-3"
			>
				Send me a confirmation link
			
			</button>
		
		</form>
	
	<?php else : ?>
		
		<p class="is-size-5">
			You can request a new confirmation email in <?= $session->get(
					'auth.confirm.email.period'
			) ?> seconds by refreshing this page.
		</p>
	
	<?php endif; ?>
	
	<!-- Check if we sent an email on the last request-->
	<?php if ($session->get('auth.confirm.email.sent')) : ?>
		
		<div class="notification is-success is-light">
			<p class="is-size-6">
				We have sent a confirmation link to the email address linked with this account. <br>
				<br> By clicking the confirmation link you can continue where you left of.
			</p>
		</div>
	
	<?php endif; ?>

</div>

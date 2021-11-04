<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

?>

<form id="send" class="mt-4 box " action="<?= esc_attr($post_to) ?>" method="POST">
	
	<?php if ($session->has('login.link.processed')) : ?>
		
		<div class="notification is-success is-light">
			We have sent an email to the linked account if it exists! <br> If you did not receive
			your email feel free to request a new one.
		</div>
	
	<?php elseif ($errors->has('login')): ?>
		
		<div class="notification is-danger is-light">
			
			Your magic link is either invalid or expired. Please request a new one.
		
		</div>
	
	<?php else : ?>
		
		<div class="notification is-info is-light">
			Our Application works without passwords! <br> Enter your username or account email and
			you will receive a secure, one-time link to log in.
		</div>
	
	<?php endif; ?>
	
	<div class="field">
		<label for="" class="label">Username or email</label>
		
		<div class="control has-icons-left">
			
			<input
					name="login" type="text" placeholder="e.g. bobsmith@gmail.com"
					value="<?= esc_attr($session->getOldInput('username', '')) ?>"
					class="input <?= $errors->count() ? 'is-danger' : '' ?>" required
			>
			
			<span class="icon is-small is-left">
                                      <i class="fa fa-envelope"></i>
                                 </span>
		
		</div>
	</div>
	
	<?= $csrf->asHtml() ?>
	<button
			type="submit"
			class="button submit mb-3"
	>
		Send me a login link
	
	</button>
	
	<?php if (isset($register_url)) : ?>
		
		<a href="<?= esc_url($register_url) ?>" class="text-sm-left underlined"> Register</a>
	
	<?php endif; ?>

</form>


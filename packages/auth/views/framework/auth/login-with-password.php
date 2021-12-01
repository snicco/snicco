<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

use Snicco\Session\Session;
use Snicco\Support\ViewErrorBag;

/** @var ViewErrorBag $errors */
/** @var Session $session */

?>

<?php
if ($session->boolean('is_interim_login') === true) : ?>
	
	<div class="notification is-info is-light">
		Your session has expired. Please log back in to continue.
	</div>

<?php
endif; ?>

<form method="POST" action="<?= esc_attr($post_url) ?>" class="box">
	
	<?php
	if ($errors->has('login')) : ?>
		
		<div class="notification is-danger is-light">
			Either your username or password is not correct.
		</div>
	
	<?php
	endif; ?>
	
	<!--CSRF field-->
	<?= $csrf->asHtml() ?>
	
	<!--Username-->
	<div class="field">
		<label for="" class="label">Username or email</label>
		
		<div class="control has-icons-left">
			
			<input
					name="log" type="text" placeholder="e.g. bobsmith@gmail.com"
					value="<?= esc_attr($session->getOldInput('log', '')) ?>"
					class="input <?= $errors->count()
							? 'is-danger'
							: '' ?>" required
					autocomplete="username"
			>
			
			<span class="icon is-small is-left">
                                      <i class="fa fa-envelope"></i>
            </span>
		
		</div>
	</div>
	
	<!--Password-->
	<div class="field">
		<label for="" class="label">Password</label>
		<div class="control has-icons-left">
			<input
					name="pwd" type="password" placeholder="*******"
					class="input <?= $errors->count()
							? 'is-danger'
							: '' ?>" required
					autocomplete="current-password"
			>
			<span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
            </span>
		</div>
	</div>
	
	<!--Remember me-->
	<?php
	if ($allow_remember) : ?>
		<div class="field">
			<label
					for=""
					class="checkbox"
			> <input
						name="remember_me"
						type="checkbox" <?= $session->getOldInput('remember_me', 'off') === 'on'
						? 'checked'
						: '' ?>> Remember me </label>
		</div>
	<?php
	endif; ?>
	
	<!--    Submit Button -->
	<div class="field">
		<button id="login_button" class="button">
			Login
		</button>
	</div>
	<?php
	
	if ($allow_password_reset) : ?>
		
		<a href="<?= esc_url($forgot_password_url) ?>" class="text-sm-left underlined">
			Forgot password?
		</a>
	
	<?php
	endif; ?>
	
	<?php
	if ($allow_registration) : ?>
		<a href="<?= esc_url($register_url) ?>" class="text-sm-right ml-4 underlined"> Register</a>
	<?php
	endif; ?>

</form>






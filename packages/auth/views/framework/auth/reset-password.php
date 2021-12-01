<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

/** @var ViewErrorBag $errors */

/** @var Session $session */

use Snicco\Session\Session;
use Snicco\Support\ViewErrorBag;

?>

<?php if ($session->get('_password_reset.success', false)) : ?>
	
	<div class="box">
		<div class="notification is-success is-light">
			You have successfully reset your password. You can now log-in with your new credentials.
		</div>
		<a href="<?= wp_login_url() ?>" class="is-link"> Proceed to login</a>
	</div>
<?php else : ?>
	
	<form id="reset-password" method="POST" action="<?= esc_attr($post_to) ?>" class="box">
		
		<?= $method_field ?>
		<?php if ($errors->count()): ?>
			
			<div class="notification is-danger is-light">
				<?= esc_html($errors->first('password')) ?>
				<?php if ($errors->has('reason')) : ?>
					<br>
					Reason: <?= esc_html($errors->first('reason')) ?>
				<?php endif; ?>
				<?php if ($errors->has('password_confirmation')) : ?>
					<br>
					<?= esc_html($errors->first('password_confirmation')) ?>
				<?php endif; ?>
			</div>
			
			<?php if ($errors->has('suggestions')) : ?>
				<div class='notification is-info is-light'>
					Suggestions:
					<ul style="list-style: inherit">
						<?php foreach ($errors->get('suggestions') as $error): ?>
							
							<li> <?= esc_html($error) ?></li>
						
						<?php endforeach; ?>
					</ul>
				
				</div>
			<?php endif; ?>
		
		<?php else : ?>
			
			<div class="notification is-info is-light">
				<p> Choose and confirm your new password. </p>
			</div>
		<?php endif; ?>
		
		<?= $csrf->asHtml() ?>
		
		<div class="field">
			<label for="" class="label">Password</label>
			<div class="control has-icons-left">
				
				<input
						id="password"
						
						name="password" type="password"
						placeholder="Enter your new password"
						class="input <?= $errors->count() ? 'is-danger' : '' ?>"
						value="<?= esc_attr($session->getOldInput('password', '')) ?>"
						required
						autocomplete="new-password"
				
				>
				
				<span class="icon is-small is-left" style="cursor:pointer;">
                  <i class="fa fa-lock"></i>
             </span>
				<p
						id="toggle" onclick="togglePassword()"
						class="has-text-info is-size-6 has-text-weight-light mt-2 has-text-left is-clickable"
				>
					Show password</p>
			
			</div>
		</div>
		<div class="field">
			<label for="" class="label">Confirmation</label>
			<div class="control has-icons-left">
				<input
						
						name="password_confirmation"
						type="password"
						placeholder="Confirm your password"
						class="input <?= $errors->count() ? 'is-danger' : '' ?>"
						required
						autocomplete="new-password"
				
				>
				<span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
                 </span>
			</div>
		</div>
		<div class="field">
			<button id="login_button" class="button ">
				Update password
			</button>
		</div>
	
	</form>
<?php endif; ?>

<script>
	
	function togglePassword() {
		let el = document.getElementById("password");
		let toggle = document.getElementById("toggle");
		if (el.type === 'password') {
			document.getElementById("password").type = "text";
			toggle.innerText = 'Hide password'
		} else {
			document.getElementById("password").type = "password";
			toggle.innerText = 'Show password'
		}
	}
	
	let btn = document.querySelector('.button');
	btn.addEventListener('click', function (e) {
		
		let el = document.getElementById("password");
		el.type = "password";
		
	});

</script>


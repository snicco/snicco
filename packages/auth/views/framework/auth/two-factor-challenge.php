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

<form id="one-time-code" method="POST" action="<?= esc_attr($post_to) ?>" class="box">
	
	<div class="notification is-info is-light">
		Please confirm access to your account by entering the authentication code provided by your
		phone's authenticator application.
	</div>
	
	<?php if ($errors->has('login')) : ?>
		
		<div class="notification is-danger is-light">
			Invalid one-time-code provided.
		</div>
	
	<?php endif; ?>
	
	<!--CSRF field-->
	<?= $csrf->asHtml() ?>
	
	<!--One time password-->
	<div class="field">
		<label for="" class="label">Code</label>
		<div class="control has-icons-left">
			
			<input
					type="text"
					name="one-time-code"
					id="one-time-code"
					inputmode="numeric"
					pattern="[0-9]*"
					autocomplete="one-time-code"
					class="input <?= $errors->count() ? 'is-danger' : '' ?>"
			/>
			
			<span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
            </span>
		</div>
	</div>
	
	<p
			onclick="useRecoveryCode()" id="use-recovery-code"
			class="has-text-dark is-clickable underlined has-text-weight-light mb-4"
	> Use Recovery Code</p>
	
	<!--    Submit Button -->
	<div class="field">
		<button class="button submit">
			Login
		</button>
	</div>

</form>

<form id="recovery-code" method="POST" action="<?= esc_attr($post_to) ?>" class="box hide">
	
	<div class="notification is-info is-light">
		Enter one of your recovery codes that you received on activating two factor authentication.
	</div>
	
	<?php if ($errors->count()): ?>
		
		<div class="notification is-danger is-light">
			Invalid code provided.
		</div>
	
	<?php endif; ?>
	
	<!--CSRF field-->
	<?= $csrf->asHtml() ?>
	
	<!--Recovery code-->
	<div class="field">
		<label for="" class="label">Recovery Code</label>
		<div class="control has-icons-left">
			
			<input
					type="text"
					name="recovery-code"
					id="recovery-code"
					class="input <?= $errors->count() ? 'is-danger' : '' ?>"
					placeholder="eg. 1234563-123234"
			/>
			
			<span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
            </span>
		</div>
	</div>
	
	<p
			onclick="useAuthCode()" id="use-auth-code"
			class="has-text-dark is-clickable underlined has-text-weight-light mb-4"
	> Use Authentication Code</p>
	
	<!--    Submit Button -->
	<div class="field">
		<button class="button submit">
			Login
		</button>
	</div>

</form>

<script>
	
	function useRecoveryCode() {
		
		let one_time_form = document.getElementById('one-time-code');
		one_time_form.classList.add('hide');
		
		let recover_code_form = document.getElementById('recovery-code');
		recover_code_form.classList.remove('hide');
		
	}
	
	function useAuthCode() {
		
		let one_time_form = document.getElementById('one-time-code');
		let recover_code_form = document.getElementById('recovery-code');
		
		recover_code_form.classList.add('hide');
		one_time_form.classList.remove('hide');
		
	}

</script>






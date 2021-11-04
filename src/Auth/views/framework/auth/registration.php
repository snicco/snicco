<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

?>

<form id="send" class="mt-4 box " action="<?= esc_attr($post_to) ?>" method="POST">
	
	<?php if ($session->has('registration.link.success')) : ?>
		<div class="notification is-success is-light">
			We have have sent you a link to confirm and finish your registration. <br> If you did
			not receive an email feel free to request a new one.
		</div>
	
	<?php else : ?>
		
		<div class="notification is-info is-light">
			Provide your favorite email address to register at <?= esc_html(
					\Snicco\Support\WP::siteName()
			) ?>. <br> We will sent you a confirmation email with a link to finish you registration.
		</div>
	<?php endif; ?>
	
	<div class="field">
		
		<?php if ($errors->has('email')) : ?>
			
			<div class="notification is-danger is-light">
				<?= esc_html($errors->first('email')) ?>
			</div>
		
		<?php endif; ?>
		
		<label for="" class="label">Email</label>
		
		<div class="control has-icons-left">
			
			<input
					name="email" type="email" placeholder="e.g. bobsmith@gmail.com"
					value="<?= esc_attr($session->getOldInput('email', '')) ?>"
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
			class="button submit"
	>
		Send me a registration link
	
	</button>

</form>


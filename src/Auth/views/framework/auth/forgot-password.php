<?php

/*
 * Extends: framework.auth.layout
 */

declare(strict_types=1);

use Snicco\Session\Session;
use Snicco\Support\ViewErrorBag;

/** @var ViewErrorBag $errors */

/** @var Session $session */

$processed = $session->get('password.reset.processed', false);

?>

<form method="POST" action="<?= esc_attr($post_to) ?>" class="box">
	
	<div class="notification <?= $processed ? 'is-success' : 'is-info' ?> is-light">
		
		<?php if ($processed) : ?>
			We sent an email to the provided account if it exists.
		<?php else : ?>
			Enter your username or account email and we will sent you an email with instructions to reset your password.
		<?php endif; ?>
	</div>
	
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
	<div class="field">
		<button id="login_button" class="button ">
			Request new password
		</button>
	</div>
	
	<?= $csrf->asHtml() ?>

</form>




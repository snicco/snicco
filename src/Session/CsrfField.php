<?php

declare(strict_types=1);

namespace Snicco\Session;

use RuntimeException;
use Snicco\Session\Middleware\VerifyCsrfToken;

class CsrfField
{
	
	private Session $session;
	
	public function __construct(Session $session)
	{
		$this->session = $session;
	}
	
	public function asStringToken() :string
	{
		return VerifyCsrfToken::TOKEN_KEY.'='.$this->getToken();
	}
	
	public function asMeta() :string
	{
		ob_start();
		
		?>
		<meta name="<?= esc_attr(VerifyCsrfToken::TOKEN_KEY); ?>"
		      content="<?= esc_attr($this->getToken()); ?>"
		><?php
		
		return ob_get_clean();
	}
	
	public function asHtml() :string
	{
		ob_start();
		
		?><input type="hidden" name="<?= esc_attr(VerifyCsrfToken::TOKEN_KEY); ?>"
		         value="<?= esc_attr($this->getToken()); ?>">
		<?php
		
		return ob_get_clean();
	}
	
	private function getToken()
	{
		if ( ! $this->session->has(VerifyCsrfToken::TOKEN_KEY)) {
			throw new RuntimeException("The user session has no csrf token.");
		}
		
		return $this->session->csrfToken();
	}
	
}


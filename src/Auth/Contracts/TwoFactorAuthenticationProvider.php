<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    interface TwoFactorAuthenticationProvider
    {
	
	    public function generateSecretKey($length = 16, $prefix = '') :string;
	
	    public function qrCodeUrl(string $company_name, string $user_identifier, string $secret);
	
	    /**
	     * @param string $secret The secret that was generated initially for the user. Has to
	     *                       decrypt.
	     * @param string $code
	     *
	     * @return bool
	     */
	    public function verifyOneTimeCode(string $secret, string $code) :bool;
	
	    public function renderQrCode() :string;
	
    }
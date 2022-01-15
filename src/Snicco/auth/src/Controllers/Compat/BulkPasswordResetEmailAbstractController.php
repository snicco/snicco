<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers\Compat;

use Snicco\Mail\MailBuilder;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\Core\Utils\WP;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\HttpRouting\Http\AbstractController;
use Snicco\Auth\Traits\SendsPasswordResetMails;
use Snicco\Component\Core\ExceptionHandling\Exceptions\AuthorizationException;

use function get_userdata;
use function check_admin_referer;

/**
 * This Controller performs the same logic that happens inside user.php
 * when password reset emails ar sent from the admin interface.
 * This Controller sends password reset emails that are compatible with the Auth Package by
 * creating a signed link to the password reset route.
 */
class BulkPasswordResetEmailAbstractController extends AbstractController
{
    
    use SendsPasswordResetMails;
    
    protected int       $lifetime      = 300;
    protected string    $error_message = 'Sorry, you are not allowed to perform this action';
    private MailBuilder $mail;
    
    public function __construct(MailBuilder $mail)
    {
        $this->mail = $mail;
    }
    
    public function store(Request $request) :Response
    {
        if ($request->query('action') !== 'resetpassword') {
            return $this->response_factory->null();
        }
        
        check_admin_referer('bulk-users');
        
        if ( ! WP::currentUserCan('edit_users')) {
            throw new AuthorizationException($this->error_message);
        }
        
        if ( ! $request->has('users')) {
            return $this->response_factory->redirect()->back();
        }
        
        $users = array_map('intval', Arr::toArray($request->input('users')));
        
        $reset_count = 0;
        
        foreach ($users as $id) {
            if ( ! WP::currentUserCan('edit_user', $id)) {
                throw new AuthorizationException($this->error_message);
            }
            
            // Don't send reset email to user performing the action
            if ($id === $request->userId()) {
                continue;
            }
            
            $success = $this->sendResetMail(get_userdata($id));
            
            if ($success) {
                $reset_count++;
            }
        }
        
        return $this->response_factory->redirect()->to($request->path(), 302, [
            'reset_count' => $reset_count,
            'update' => 'resetpassword',
        ]);
    }
    
}
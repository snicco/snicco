<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use WP_User;
use ZxcvbnPhp\Zxcvbn;
use Snicco\Http\Controller;
use Snicco\Http\MethodField;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Respect\Validation\Validator as v;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Validation\Exceptions\ValidationException;

class ResetPasswordController extends Controller
{
    
    use ResolvesUser;
    
    protected ?string $success_message;
    protected array   $rules = [];
    protected int     $min_strength = 3;
    protected int     $min_length = 12;
    protected int     $max_length = 64;
    
    public function __construct(string $success_message = null)
    {
        $this->success_message = $success_message
                                 ??
                                 'You have successfully reset your password. You can now log-in with your new credentials';
    }
    
    public function create(Request $request, MethodField $method_field)
    {
        return $this->response_factory->view('framework.auth.reset-password', [
            'post_to' => $request->fullPath(),
            'method_field' => $method_field->html('PUT'),
        ])->withHeader('Referrer-Policy', 'strict-origin');
    }
    
    public function update(Request $request) :RedirectResponse
    {
        $user = $this->getUserById($request->query('id', 0));
        
        if ( ! $user || $user->ID === 0) {
            return $this->redirectBackFailure();
        }
        
        $rules = array_merge([
            'password' => v::noWhitespace()->length($this->min_length, $this->max_length),
            '*password_confirmation' => [
                v::sameAs('password'),
                'The provided passwords do not match.',
            ],
        ], $this->rules);
        
        $validated = $request->validate($rules);
        
        $this->checkPasswordStrength($validated, $user);
        
        reset_password($user, $validated['password']);
        
        return $this->response_factory->redirect()
                                      ->refresh()
                                      ->with('_password_reset.success', true);
    }
    
    protected function provideMessages(array $result) :array
    {
        $messages = [
            'password' => [
                'Your password is too weak and can be easily guessed by a computer.',
            ],
        ];
        
        if (isset($result['feedback']['warning'])) {
            $messages['reason'][] = trim($result['feedback']['warning'], '.').'.';
        }
        
        foreach ($result['feedback']['suggestions'] ?? [] as $suggestion) {
            $messages['suggestions'][] = $suggestion;
        }
        
        return $messages;
    }
    
    private function redirectBackFailure() :RedirectResponse
    {
        return $this->response_factory->redirect()
                                      ->refresh()
                                      ->withErrors(
                                          ['failure' => 'We could not reset your password.']
                                      );
    }
    
    private function checkPasswordStrength(array $validated, WP_User $user)
    {
        $user_data = [
            $user->user_login,
            $user->user_email,
        ];
        
        $password_evaluator = new Zxcvbn();
        $result = $password_evaluator->passwordStrength($validated['password'], $user_data);
        
        if ($result['score'] < $this->min_strength) {
            $messages = $this->provideMessages($result);
            
            throw ValidationException::withMessages($messages);
        }
    }
    
}
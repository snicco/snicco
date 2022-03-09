<?php

declare(strict_types=1);

use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;

return [
    /*
     * A list of root directories where templates are located.
     * Nested subdirectories can be referenced by "." notation.
     *
     * Example: If "templates" one root directory:
     *   - $view->make('login') will try to load the /templates/login.php template.
     *   - $view->make('auth.login') will try to load the /templates/auth/login.php template.
     */
    TemplatingOption::DIRECTORIES => [
        //        dirname(__DIR__).'/templates'
    ],

    // A list of different factories that will be used in the given order to load templates.
    TemplatingOption::VIEW_FACTORIES => [
        PHPViewFactory::class,
    ],

    /*
     * View composers can be used to add variables to certain views without needing to
     * pass them explicitly every time the view is created.
     *
     * "*" can be used as a wildcard for view names.
     */
    TemplatingOption::VIEW_COMPOSERS => [
        //        This will apply the "MyViewComposer1" class every time the "foo" or "bar" view is rendered
        //        '\MyPlugin\MyViewComposer1' => ['foo', 'bar']

        //        This will apply the "MyViewComposer1" class every a view in the users' subdirectory is rendered
        //        '\MyPlugin\UserViewComposer' => ['users.*']
    ],
];

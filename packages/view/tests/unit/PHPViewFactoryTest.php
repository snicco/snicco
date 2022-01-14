<?php

declare(strict_types=1);

namespace Tests\View\unit;

use Tests\Codeception\shared\UnitTest;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Implementations\PHPView;
use Snicco\View\Implementations\PHPViewFinder;
use Snicco\View\Implementations\PHPViewFactory;

use const DS;
use const SHARED_FIXTURES_DIR;

final class PHPViewFactoryTest extends UnitTest
{
    
    private string $view_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->view_dir = SHARED_FIXTURES_DIR.DS.'views';
    }
    
    /** @test */
    public function a_view_can_be_created_from_an_absolute_path()
    {
        $php_view_factory = new PHPViewFactory(
            new PHPViewFinder([$this->view_dir]),
            new ViewComposerCollection()
        );
        
        $path = realpath($this->view_dir.'/view.php');
        
        $view = $php_view_factory->make([$path]);
        $this->assertInstanceOf(PHPView::class, $view);
        $this->assertSame($path, $view->path());
    }
    
}
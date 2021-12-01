<?php

namespace Snicco\ExceptionHandling;

use Snicco\Support\Str;
use Whoops\Exception\Frame;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Exception\FrameCollection;

class FilterablePrettyPageHandler extends PrettyPageHandler
{
    
    private array $filter_frames;
    
    public function __construct(array $filter_frames = [])
    {
        parent::__construct();
        $this->filter_frames = $filter_frames;
    }
    
    protected function getExceptionFrames() :FrameCollection
    {
        $frames = parent::getExceptionFrames();
        $exception = $this->getException();
        
        foreach ($this->filter_frames as $class) {
            $class_path = str_replace('\\', '/', $class);
            
            if (Str::contains($exception->getLine(), $class_path)) {
                continue;
            }
            
            $frames->filter(function (Frame $frame) use ($class, $class_path) {
                return ! Str::contains($frame->getFile(), $class_path)
                       && ! Str::contains($frame->getClass(), $class);
            });
        }
        
        return $frames;
    }
    
}
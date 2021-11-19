<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

use InvalidArgumentException;

final class Attachment
{
    
    /**
     * @var string
     */
    private $file_path;
    
    /**
     * @var string
     */
    private $file_name;
    
    public function __construct(string $file_path, string $file_name = '')
    {
        if ( ! is_file($file_path)) {
            throw new InvalidArgumentException(
                "Invalid file path [$file_path] provided for attachment."
            );
        }
        $this->file_path = $file_path;
        $this->file_name = $file_name;
    }
    
    /**
     * @return string
     * @api
     */
    public function getName() :string
    {
        return $this->file_name;
    }
    
    /**
     * @return string
     * @api
     */
    public function getPath() :string
    {
        return $this->file_path;
    }
    
}
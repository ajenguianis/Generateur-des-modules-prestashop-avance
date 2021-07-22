<?php

namespace Evogroup\Module\Moduleclass\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;

class LoggerHandlerFactory
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var int
     */
    private $maxFiles;

    /**
     * @var int
     */
    private $loggerLevel;

    /**
     * @param string $directory
     * @param string $filename
     * @param int $maxFiles
     * @param int $loggerLevel
     */
    public function __construct($directory, $filename, $maxFiles, $loggerLevel)
    {
        $this->directory = $directory;
        $this->filename = $filename;
        $this->maxFiles = $maxFiles;
        $this->loggerLevel = $loggerLevel;
    }

    /**
     * @return HandlerInterface
     */
    public function build()
    {
        return new RotatingFileHandler(
            $this->directory . $this->filename,
            $this->maxFiles,
            $this->loggerLevel
        );
    }
}

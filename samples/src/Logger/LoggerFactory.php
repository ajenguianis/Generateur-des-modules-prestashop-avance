<?php
namespace EvoGroup\Module\Moduleclass\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for create Logger instance.
 */
class LoggerFactory
{
    const MODULE_CLASS_LOGGER_MAX_FILES = 'MODULE_CLASS_LOGGER_MAX_FILES';
    const MODULE_CLASS_LOGGER_LEVEL = 'MODULE_CLASS_LOGGER_LEVEL';
    const MODULE_CLASS_LOGGER_HTTP = 'MODULE_CLASS_LOGGER_HTTP';
    const MODULE_CLASS_LOGGER_HTTP_FORMAT = 'MODULE_CLASS_LOGGER_HTTP_FORMAT';

    /**
     * @var string
     */
    private $name;

    /**
     * @var HandlerInterface
     */
    private $loggerHandler;

    /**
     * @param string $name
     * @param HandlerInterface $loggerHandler
     *
     * @throws Exception
     */
    public function __construct($name, HandlerInterface $loggerHandler)
    {
        $this->assertNameIsValid($name);
        $this->name = $name;
        $this->loggerHandler = $loggerHandler;
    }

    /**
     * @return LoggerInterface
     */
    public function build()
    {
        return new Logger(
            $this->name,
            [
                $this->loggerHandler,
            ],
            [
                new PsrLogMessageProcessor(),
            ]
        );
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    private function assertNameIsValid($name)
    {
        if (empty($name)) {
            throw new \Exception('Logger name cannot be empty.');
        }

        if (!is_string($name)) {
            throw new \Exception('Logger name should be a string.');
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
            throw new \Exception('Logger name is invalid.');
        }
    }
}

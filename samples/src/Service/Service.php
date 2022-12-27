<?php

declare(strict_types=1);

namespace EvoGroup\Module\Moduleclass\Service;

use Psr\Log\LoggerInterface;

/**
 * Class Service
 */
class ServiceName
{
    /**
     * @var LoggerInterface
     */
    private $_logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }
}
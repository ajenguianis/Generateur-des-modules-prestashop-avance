<?php
namespace Evogroup\Module\Moduleclass\Logger;

use Monolog\Logger;
use Evogroup\Module\Moduleclass\Configuration\PrestaShopConfiguration;

/**
 * Class responsible for returning logger settings
 */
class LoggerConfiguration
{
    const MAX_FILES = 15;

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @param PrestaShopConfiguration $configuration
     */
    public function __construct(PrestaShopConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return int
     */
    public function getMaxFiles()
    {
        return (int) $this->configuration->get(
            'MODULE_CLASS_LOGGER_MAX_FILES',
            [
                'default' => static::MAX_FILES,
                'global' => true,
            ]
        );
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return (int) $this->configuration->get(
            'MODULE_CLASS_LOGGER_LEVEL',
            [
                'default' => Logger::ERROR,
                'global' => true,
            ]
        );
    }
}

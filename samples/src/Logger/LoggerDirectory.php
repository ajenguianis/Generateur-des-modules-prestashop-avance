<?php

namespace \Module\Moduleclass\Logger;

/**
 * Class responsible for returning log directory path.
 */
class LoggerDirectory
{
    /** @var int access rights of created folders (octal) */
    protected static $access_rights = 0775;
    /**
     * @var string PrestaShop path
     */
    private $psPath;
    /**
     * @var Module
     */
    private $module;

    /**
     * LoggerDirectory constructor.
     * @param $psPath
     * @param $module
     */
    public function __construct($psPath, $module)
    {
        $this->psPath = $psPath;
        $this->module=$module;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if (!file_exists($logDirectory=$this->psPath . '/var/logs/'.$this->module->name)) {
            if (!mkdir($logDirectory, self::$access_rights, true) && !is_dir($logDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDirectory));
            }
        }
        @chmod($logDirectory, self::$access_rights);
        return $this->psPath . '/var/logs/'.$this->module->name.'/';
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return is_writable($this->getPath());
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return is_readable($this->getPath());
    }
}

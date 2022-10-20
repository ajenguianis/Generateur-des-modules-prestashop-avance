<?php

namespace EvoGroup\Module\Moduleclass\Logger;


/**
 * Class responsible for returning log filename.
 */
class LoggerFilename
{
    /**
     * @var string Base filename
     */
    private $filename;

    /**
     * @var int Shop identifier
     */
    private $identifier;

    /**
     * @param string $filename
     * @param int $identifier
     *
     * @throws Exception
     */
    public function __construct($filename, $identifier)
    {
        $this->assertNameIsValid($filename);
        $this->filename = $filename;
        $this->identifier = (int) $identifier;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->filename . '-' . $this->identifier;
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    private function assertNameIsValid($name)
    {
        if (empty($name)) {
            throw new \Exception('Logger filename cannot be empty.');
        }

        if (!is_string($name)) {
            throw new \Exception('Logger filename should be a string.');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new \Exception('Logger filename is invalid.');
        }
    }
}

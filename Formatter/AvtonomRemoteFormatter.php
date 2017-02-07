<?php
namespace Avtonom\RemoteLoggerBundle\Formatter;

use Monolog\Formatter\JsonFormatter;

class AvtonomRemoteFormatter extends JsonFormatter
{
    /**
     * @param string
     */
    protected $service = '';

    /**
     * @param string
     */
    protected $appname = '';

    /**
     * @var string
     */
    protected $environment = '';

    /**
     * Set hostname
     *
     * @param string $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * Set appname
     *
     * @param string $appname
     */
    public function setAppname($appname)
    {
        $this->appname = $appname;
    }

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param array $record
     * @return mixed
     *
     * Appends the 'hostname' and 'appname' parameter for indexing
     *
     * @see \Monolog\Formatter\JsonFormatter::format()
     */
    public function format(array $record)
    {
        if (!empty($this->service)) {
            $record['service'] = $this->service;
        }
        if (!empty($this->appname)) {
            $record['appname'] = $this->appname;
        }
        if (!empty($this->environment)) {
            $record['env'] = $this->environment;
        }
        return parent::format($record);
    }
}

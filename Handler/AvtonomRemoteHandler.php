<?php

namespace Avtonom\RemoteLoggerBundle\Handler;

use Avtonom\RemoteLoggerBundle\Formatter\AvtonomRemoteFormatter;
use Monolog\Logger;
use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\SocketHandler;
use Monolog\Formatter\JsonFormatter;
use Psr\Log\LoggerAwareTrait;

class AvtonomRemoteHandler extends SocketHandler
{
    use LoggerAwareTrait;

    const REMOTE_URI = '/log';
    const REMOTE_URI_BATCH = '/log/batch';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $service;

    /**
     * @var string
     */
    protected $appName;

    /**
     * @var string
     */
    protected $remoteHost;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $isBatch = false;

    /**
     * @param string     $token    Log token supplied by RemoteLogger.
     * @param string     $remoteHost The  server hostname.
     * @param bool       $useSSL   Whether or not SSL encryption should be used.
     * @param int|string $level    The minimum logging level to trigger this handler.
     * @param bool       $bubble   Whether or not messages that are handled should bubble up the stack.
     *
     * @throws MissingExtensionException If SSL encryption is set to true and OpenSSL is missing
     */
    public function __construct($token, $remoteHost, $useSSL = true, $level = Logger::DEBUG, $bubble = true)
    {
        if ($useSSL && !extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use SSL encrypted connection for RemoteLoggerHandler');
        }

        $endpoint = $useSSL ? 'ssl://'.$remoteHost.':443' : $remoteHost.':80';

        parent::__construct($endpoint, $level, $bubble);

        if($token && strlen($token) != 32){
            throw new MissingExtensionException('Incorrect RemoteLogger token');
        }
        $this->token = $token;
        $this->remoteHost = $remoteHost;
    }

    /**
     * @param string     $service Host name supplied by RemoteLogger.
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @param string     $appName  Application name supplied by RemoteLogger.
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateDataStream($record)
    {
        $content = ($this->token) ? $this->token . ' ' . $record['formatted'] : $record['formatted'];
        return $this->buildHeader($content) . $content;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        $formatter = new AvtonomRemoteFormatter();

        if (!empty($this->service)) {
            $formatter->setService($this->service);
        }
        if (!empty($this->appName)) {
            $formatter->setAppname($this->appName);
        }
        if (!empty($this->environment)) {
            $formatter->setEnvironment($this->environment);
        }

        return $formatter;
    }

    /**
     * Handles a set of records at once.
     *
     * @param array $records The records to handle (an array of record arrays)
     */
    public function handleBatch(array $records)
    {
        foreach ($records as $k => $record) {
            if (!$this->isHandling($record)) {
                unset($records[$k]);
                continue;
            }
            $record = $this->processRecord($record);
//            $record['formatted'] = $this->getFormatter()->format($record);
            $records[$k] = $record;
            break;
        }
        $records['formatted'] = $this->getFormatter()->formatBatch($records);
        $this->isBatch = true;
        $this->write($records);
        $this->isBatch = false;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        try {
            parent::write($record);
            /*$res = $this->getResource();
            if (is_resource($res)) {
                @fread($res, 2048);
            }*/
            $this->closeSocket();
            $this->logger->debug('w '.($this->isBatch ? 'b' : 's').': '.sizeof($record));
        } catch (\UnexpectedValueException $e) {
            $this->logger->error(__METHOD__.' Exception: '.$e->getMessage());
        }
    }

    private function buildHeader($content)
    {
        $strlen = strlen($content);
        if($this->isBatch){
            $header = "POST ".self::REMOTE_URI_BATCH." HTTP/1.1\r\n";
        } else {
            $header = "POST ".self::REMOTE_URI." HTTP/1.1\r\n";
        }
        $header .= "Host: ".$this->remoteHost."\r\n";
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . $strlen . "\r\n";
//        $header .= "Connection: close\r\n\r\n";
        $header .= "\r\n";

        $this->logger->debug('h '.($this->isBatch ? 'b' : 's').': '.(strlen($header) + $strlen).' to '.$this->remoteHost);

        return $header;
    }
}
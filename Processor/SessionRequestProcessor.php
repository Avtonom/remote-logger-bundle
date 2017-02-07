<?php
namespace Avtonom\RemoteLoggerBundle\Processor;

use Symfony\Component\HttpFoundation\Session\Session;

class SessionRequestProcessor
{
    private $session;
    private $sessionId;
    private $requestId;
    private $_server;
    private $_get;
    private $_post;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function processRecord(array $record)
    {
        if (null === $this->requestId) {
            if ('cli' === php_sapi_name()) {
                $this->sessionId = getmypid();
            } else {
                try {
                    $this->session->start();
                    $this->sessionId = $this->session->getId();
                } catch (\RuntimeException $e) {
                    $this->sessionId = '????????';
                }
            }
            $this->requestId = substr(uniqid(), -8);
            $this->_server = array(
                'request' => (@$_SERVER['HTTP_HOST']) . '/' . (@$_SERVER['REQUEST_URI']),
                'verb' => @$_SERVER['REQUEST_METHOD'],
                'agent' => @$_SERVER['HTTP_USER_AGENT'],
                'referer' => @$_SERVER['HTTP_REFERER'],
                'fwd_for' => @$_SERVER['HTTP_X_FORWARDED_FOR']
            );
            $this->_post = $this->clean($_POST);
            $this->_get = $this->clean($_GET);
        }
        if(!array_key_exists('extra', $record)){
            $record['extra'] = array();
        }
        $record['requestId'] = $this->requestId;
        $record['sessionId'] = $this->sessionId;
        $record['extra']['request'] = $this->_server['request'];
        $record['extra']['verb'] = $this->_server['verb'];
        $record['extra']['agent'] = $this->_server['agent'];
        $record['extra']['referer'] = $this->_server['referer'];
        $record['extra']['fwd_for'] = $this->_server['fwd_for'];

        return $record;
    }

    protected function clean($array)
    {
        $toReturn = array();
        foreach (array_keys($array) as $key) {
            if (false !== strpos($key, 'password')) {
                //Do not add
            } else if (false !== strpos($key, 'csrf_token')) {
                //Do not add
            } else {
                $toReturn[$key] = $array[$key];
            }
        }

        return $toReturn;
    }
}
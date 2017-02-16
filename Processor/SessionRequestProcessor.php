<?php
namespace Avtonom\RemoteLoggerBundle\Processor;

use Symfony\Component\HttpFoundation\Session\Session;

class SessionRequestProcessor
{
    private $session;
    private $sessionId;
    private $requestId;
    private $_server = array();
    private $_get;
    private $_post;

    /**
     * @var array
     */
    protected $extraFields = array(
        'request_uri' => 'REQUEST_URI',
        'request_host' => 'HTTP_HOST',
        'verb' => 'REQUEST_METHOD',
        'agent' => 'HTTP_USER_AGENT',
        'http_referer' => 'HTTP_REFERER',
        'fwd_for' => 'HTTP_X_FORWARDED_FOR',
    );

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
                    $this->sessionId = substr($this->session->getId(), 0, 8);
                } catch (\RuntimeException $e) {
                    $this->sessionId = '????????';
                }
                $this->_server = $this->getExtraValue();
            }
            $this->requestId = substr(uniqid(), -8);
        }

//        $this->_post = $this->clean($_POST);
//        $this->_get = $this->clean($_GET);

        if(!array_key_exists('extra', $record)){
            $record['extra'] = array();
        }
        $record['requestId'] = $this->requestId;
        $record['sessionId'] = $this->sessionId;
        $record['extra'] = array_merge($record['extra'], $this->_server);
        return $record;
    }

    /**
     * @return array
     */
    protected function getExtraValue()
    {
        $extra = array();
        $serverData = $_SERVER;
        foreach($this->extraFields as $extraName => $serverName) {
            if(!empty($serverData[$serverName])){
                $extra[$extraName] = $serverData[$serverName];
            }
        }
        return $extra;
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
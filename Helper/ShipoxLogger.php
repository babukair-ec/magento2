<?php

namespace Delivery\Shipox\Helper;


/**
 * Class Logger
 */
class ShipoxLogger
{

    /**
     * @var string
     */
    protected $filename = 'shipox';
    protected $logger = null;

    /**
     * Logger constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $filename
     */
    public function setFileName($filename) {
        $this->filename = $filename;
    }

    /**
     * @param $message
     * @param $type
     */
    public function message($message, $type = 'info') {

        $content = print_r($message, true);

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $this->filename . '.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);

        switch ($type) {
            case 'emergency':
                $this->logger->emergency($content);
                break;
            case 'alert':
                $this->logger->alert($content);
                break;
            case 'critical':
                $this->logger->critical($content);
                break;
            case 'error':
                $this->logger->error($content);
                break;
            case 'warning':
                $this->logger->warning($content);
                break;
            case 'notice':
                $this->logger->notice($content);
                break;
            case 'debug':
                $this->logger->debug($content);
                break;
            default:
                $this->logger->info($content);
                break;
        }
    }

}
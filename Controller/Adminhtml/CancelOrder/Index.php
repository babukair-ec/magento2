<?php
/**
 * Index
 *
 * @copyright Copyright © 2019 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Delivery\Shipox\Controller\Adminhtml\CancelOrder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Delivery\Shipox\Helper\Client;
use Delivery\Shipox\Helper\Data;
use Delivery\Shipox\Helper\Dbclient;
use Delivery\Shipox\Model\Carrier;


class Index extends Action
{

    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $response;
    protected $client;
    protected $date;
    protected $writer;
    protected $_dbClient;
    protected $_carrier;
    protected $_order;
    protected $_data;

    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        Client $client,
        Http $response,
        TimezoneInterface $date,
        WriterInterface $writer,
        Dbclient $dbClient,
        Carrier $carrier,
        Order $order,
        Data $data,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
        $this->client = $client;
        $this->response = $response;
        $this->date = $date;
        $this->writer = $writer;
        $this->_dbClient = $dbClient;
        $this->_carrier = $carrier;
        $this->_order = $order;
        $this->_data = $data;
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $response = $this->_response->setHeader('content-type', 'application/json; charset=utf-8');
        $shipoxHelper = $this->_data;
        $shipoxDBClient = $this->_dbClient;
        $responseData = array(
            'status' => false,
            'message' => 'Oops, there is some error with the Server'
        );
        if ($params['orderId'] > 0) {
            $order = $this->_order->load($params['orderId']);
            if (!is_null($order->getData())) {
                $responseData = $shipoxHelper->cancelShipoxOrder($order, $params['cancelReason']);
                if(!is_null($responseData) && $responseData['status']) {
                    $shipoxDataTable = $shipoxDBClient->getData($order->getQuoteId());
                    $shipoxDataTable->setIsCompleted(0);
                    $shipoxDataTable->save();
                }
            } else {
                $responseData['message'] = "Cannot find Order Details";
            }
        } else {
            $responseData['message'] = "Cannot find Order Details";
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $resultJson->setData($responseData);
    }
}
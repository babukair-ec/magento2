<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.08.2019
 * Time: 10:07
 */

namespace Delivery\Shipox\Controller\Adminhtml\CreateOrder;
use Delivery\Shipox\Helper\ShipoxLogger;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\Order;
use \Delivery\Shipox\Helper\Client;
use \Magento\Framework\App\Response\Http;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use Delivery\Shipox\Helper\Data;
use Delivery\Shipox\Helper\Dbclient;
use Delivery\Shipox\Model\Carrier;

class Index extends Action
{
    protected $code = 'delivery';
    protected $resultJsonFactory;
    protected $_logFile = 'shipox_admin_create_order.log';
    protected $scopeConfig;
    protected $response;
    protected $client;
    protected $date;
    protected $writer;
    protected $_dbClient;
    protected $_carrier;
    protected $_order;
    protected $_data;
    protected $_shipoxLogger;

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
        ScopeConfigInterface $scopeConfig,
        ShipoxLogger $shipoxLogger
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
        $this->_shipoxLogger = $shipoxLogger;

    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $response = $this->response->setHeader('content-type', 'application/json; charset=utf-8');

//        $this->_shipoxLogger->setFileName($this->_logFile);
//        $this->_shipoxLogger->message("Shipping Method Admin Order Create");

        $db = $this->_dbClient;
        $shipoxCarrier = $this->_carrier;

        $responseData = array(
            'status' => false,
            'message' => 'Oops, there is some error with the Server'
        );

        if ($params['menuId'] > 0 && $params['packageName'] && $params['orderId'] > 0) {
            $order = $this->_order->load($params['orderId']);
            $orderData = $order->getData();

            if (!empty($orderData)) {
                $shipoxHelper = $this->_data;

                $shippingAddress = $order->getShippingAddress();
                $shippingMethod = base64_decode($params['packageName']);
                $packageId = $shipoxHelper->getPackageIdFromString($shippingMethod);
                $customerLatLonAddress = $shipoxHelper->getAddressLocation($shippingAddress);

                if ($packageId && !empty($customerLatLonAddress)) {

                    $data = array(
                        'quote_id' => $order->getQuoteId(),
                        'shipox_menu_id' => $params['menuId'],
                        'destination' => $shipoxHelper->getFullDestination($shippingAddress),
                        'destination_latlon' => $customerLatLonAddress['lat'] . "," . $customerLatLonAddress['lon'],
                        'shipox_package_id' => $packageId,
                        'order_id' => $order->getId(),
                        'package_note' => $params['packageNote'],
                    );

                    $responseOrder = $shipoxHelper->pushShipoxOrder($order, $packageId, $customerLatLonAddress, $data);

                    if (!empty($responseOrder)) {
                        $data['shipox_order_id'] = $responseOrder['id'];
                        $data['shipox_order_number'] = $responseOrder['order_number'];
                        $data['shipox_order_status'] = $responseOrder['status'];
                        $data['completed_at'] = $this->date->date();
                        $data['is_completed'] = 1;
                        $data['is_active_order'] = 1;

                        if ($db->insertData($data)) {
//                            if($this->scopeConfig->getValue('carriers/' . $this->code . '/is_create_shipment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                                $shipoxCarrier->setShipmentAndTrackingNumberOnShipmentV2($order, $responseOrder['order_number']);
//                            }
                        }

                        $responseData['status'] = true;
                        $responseData['message'] = 'Order has been successfully created';
                    }
                }

            }
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $resultJson->setData($responseData);
        //$response->setBody(json_encode($responseData));
    }
}